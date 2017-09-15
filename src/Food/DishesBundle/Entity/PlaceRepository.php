<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Food\OrderBundle\Service\OrderService;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAware;

class PlaceRepository extends EntityRepository
{
    private static $_getNearCache = [];

    private static $_citiesCache = [];

    /**
     * @param array $kitchens
     * @param array $filters
     * @param bool $recommended
     * @param array $locationData
     * @param Container $container
     *
     * @return array
     */
    public function magicFindByKitchensIds($kitchens, $filters = [], $locationData = [], $container = null)
    {
        $currTime = date('H:i:s');
        $lat = $locationData['latitude'];
        $lon = $locationData['longitude'];

        if (!$lat || !$lon) {
            return [];
        }

        $rushHour = false;
        $pickup = (isset($filters['delivery_type']) && $filters['delivery_type'] == Place::OPT_ONLY_PICKUP);

        if ($pickup && !isset($locationData['city_id'])) {
            return [];
        }


        if ($pickup) {
            $subQuery = "SELECT id FROM place_point pps WHERE active=1 AND deleted_at IS NULL AND place = p.id AND pps.city_id = ".$locationData['city_id']." GROUP BY pps.place";
        } else {
            if ($container) {
                if ($locationData['city_id']) {
                    $container->get('food.zavalas_service')->isRushHourAtCityById($locationData['city_id']);
                } else {
                    $rushHour = $container->get('food.zavalas_service')->isRushHourEnabled();
                }
            }
            /**
             * $container->getParameter('default_delivery_distance')
             *  This stuff needs to be deprecated. And parameter removed.
             */

            $maxDistance = "SELECT MAX(ppdz.distance) FROM `place_point_delivery_zones` ppdz WHERE ppdz.deleted_at IS NULL AND ppdz.active=1 AND ppdz.place_point=pps.id AND ((time_from <= '" . $currTime . "' AND '" . $currTime . "' <= time_to) OR (time_from IS NULL AND time_to IS NULL ))" . ($rushHour ? ' AND ppdz.active_on_zaval = 1' : '');

            $subQuery = "SELECT id FROM place_point pps WHERE active=1 AND deleted_at IS NULL AND place = p.id
            AND ((6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pps.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pps.lat) * pi()/180) * POWER(SIN(($lon - pps.lon) * pi()/180 / 2), 2) ))) <= ($maxDistance))
            AND pps.delivery=1
            ORDER BY fast DESC,
            (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pps.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pps.lat) * pi()/180) * POWER(SIN(($lon - pps.lon) * pi()/180 / 2), 2) ))) ASC LIMIT 1";
        }

        // Place filters
        $placeFilter = '';

        $endOfFilter = '';

        if (!empty($filters) && is_array($filters)) {

            foreach ($filters as $filterName => $filterValue) {

                switch ($filterName) {
                    case 'keyword':
                        if (!empty($filterValue)) {
                            $placeFilter .= ' AND p.name LIKE "%' . $filterValue . '%"';
                        }
                        break;

                    case 'delivery_type':

                        if (!empty($filterValue)) {
                            switch ($filterValue) {
                                case OrderService::$deliveryBoth:
                                    $placeFilter .= ' AND p.delivery_options = "delivery_and_pickup"';
                                    break;
                                case 'delivery':
                                    $placeFilter .= ' AND p.delivery_options IN ("delivery_and_pickup", "delivery")';
                                    break;
                                case OrderService::$deliveryDeliver:
                                    $placeFilter .= ' AND p.delivery_options IN ("delivery_and_pickup", "delivery")';
                                    break;
                                case OrderService::$deliveryPickup:
                                    $placeFilter .= ' AND p.delivery_options IN ("delivery_and_pickup", "pickup")';
                                    break;
                                case OrderService::$deliveryPedestrian:
                                    $placeFilter .= ' AND p.delivery_options IN ("pedestrian")';
                                default:
                                    // Do nothing ;)
                            }
                        }
                        break;
                    case 'limit':
                        $endOfFilter .= ' LIMIT '.intval($filterValue);
                        break;
                    case 'offset':
                        if($endOfFilter != '') {
                            $endOfFilter .= ' OFFSET ' . intval($filterValue);
                        }
                        break;

                    default:

                        break;
                }
            }
        }

        $kitchensQuery = "";
        if (!empty($kitchens)) {
            $kitchensQuery = " AND p.id IN (SELECT place_id FROM place_kitchen WHERE kitchen_id IN(" . implode(",", $kitchens) . "))";
        }

        $otherFilters = '';
        // 21:30 isjungiame alkoholiku rodyma :)
        $hour = date("H");
        if ($hour > '21' || ($hour == '21' && date('i') > '30') || $hour < 6) {
            $otherFilters .= ' AND p.only_alcohol != 1';
        }

        $ppCounter = "SELECT COUNT(*) FROM place_point ppc WHERE ppc.active=1 AND ppc.deleted_at IS NULL AND ppc.place = p.id";
        $query = "SELECT p.id as place_id, pp.id as point_id, pp.address, (" . $ppCounter . ") as pp_count, p.priority, p.navision FROM place p, place_point pp WHERE pp.place = p.id AND p.active=1 AND pp.active = 1 AND pp.deleted_at IS NULL ". $placeFilter . $otherFilters . " AND pp.id = (" . $subQuery . ") " . $kitchensQuery . " ORDER BY p.priority DESC, RAND()". $endOfFilter;

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        $places = $stmt->fetchAll();

        $dh = date("H");
        $dm = date("i");
        $wd = date('w');
        if ($wd == 0) $wd = 7;

        foreach ($places as $pkey => &$place) {
            //var_dump($place['pp_count']);
            $place['place'] = $this->find($place['place_id']);

            $placePointQuery = "
                SELECT pps.id
                    FROM place_point pps,
                        place_point_work_time ppwt,
                        place p
                    WHERE
                        p.id = pps.place
                        AND pps.id = ppwt.place_point
                        AND pps.active=1
                        AND pps.deleted_at is NULL
                        AND pps.place = " . $place['place']->getId() . "

                        AND ppwt.week_day = " . $wd . "
                        AND (
                        (start_hour = 0 OR start_hour < ' . $dh . ' OR
                            (start_hour <= ' . $dh . ' AND start_min <= ' . $dm . ')
                        ) AND (
                        (end_hour >= ' . $dh . ' AND end_min >= ' . $dm . ') OR
                            end_hour > ' . $dh . ' OR end_hour = 0))
                        AND pps.delivery=1
                        AND (
            (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pps.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pps.lat) * pi()/180) * POWER(SIN(($lon - pps.lon) * pi()/180 / 2), 2) ))) <= 7

            )
            ORDER BY fast DESC, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pps.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pps.lat) * pi()/180) * POWER(SIN(($lon - pps.lon) * pi()/180 / 2), 2) ))) ASC LIMIT 1";



            $defaultPlacePoint = true;
            if (empty($lat) || empty($lon)) {
                $defaultPlacePoint = false;
            }

            if ($defaultPlacePoint) {
                $stmt = $this->getEntityManager()->getConnection()->prepare($placePointQuery);
                $stmt->execute();
                $placesPInfo = $stmt->fetchColumn(0);

                if ($placesPInfo) {

                    $place['point'] = $this->getEntityManager()->getRepository('FoodDishesBundle:PlacePoint')->find($placesPInfo);
                } else {
                    $place['point'] = $this->getEntityManager()->getRepository('FoodDishesBundle:PlacePoint')->find($place['point_id']);
                }
            } else {
                $place['point'] = $this->getEntityManager()->getRepository('FoodDishesBundle:PlacePoint')->find($place['point_id']);
            }

        }


        return $places;
    }

    /**
     * @deprecated
     * Required for neighbourhood logic in Iran
     */
    public function simpleFindByNeighbourhood($kitchens, $filters = [], $locationData = null, $container = null)
    {
        $neighbourhoodId = $locationData['neighbourhood_id'];
        $kitchensQuery = "";

        if (!empty($kitchens)) {
            $kitchensQuery = " AND p.id IN (SELECT place_id FROM place_kitchen WHERE kitchen_id IN(" . implode(",", $kitchens) . "))";
        }

        // Place filters
        $placeFilter = '';
        if (is_array($filters) && !empty($filters)) {
            foreach ($filters as $filterName => $filterValue) {
                switch ($filterName) {
                    case 'keyword':
                        if (!empty($filterValue)) {
                            $placeFilter .= ' AND p.name LIKE "%' . $filterValue . '%"';
                        }
                        break;

                    case 'delivery_type':
                        if (!empty($filterValue)) {
                            switch ($filterValue) {
//                                case 'delivery_and_pickup':
//                                    $placeFilter .= ' AND p.delivery_options = "delivery_and_pickup"';
//                                    break;
                                case 'delivery':
                                    $placeFilter .= ' AND p.delivery_options IN ("delivery_and_pickup", "delivery")';
                                    break;
                                case 'pickup':
                                    $placeFilter .= ' AND p.delivery_options IN ("delivery_and_pickup", "pickup")';
                                    break;
                                default:
                                    // Do nothing ;)
                            }
                        }
                        break;

                    default:
                }
            }
        }

        $otherFilters = '';
        // 21:30 isjungiame alkoholiku rodyma :)
        $hour = date("H");
        if ($hour > '21' || ($hour == '21' && date('i') > '30')) {
            $otherFilters .= ' AND p.only_alcohol != 1';
        }


        $ppCounter = "SELECT COUNT(*)
                          FROM place_point ppc
                          INNER JOIN neighbourhood_place_point npp ON npp.place_point_id = ppc.id
                          WHERE ppc.active=1
                            AND ppc.deleted_at IS NULL
                            AND npp.neighbourhood_id='" . $neighbourhoodId . "'
                            AND ppc.place = p.id";

        $query = "SELECT p.id as place_id, pp.id as point_id, pp.address, (" . $ppCounter . ") as pp_count, p.priority, p.navision
                        FROM place p, place_point pp, neighbourhood_place_point npp
                        WHERE pp.place = p.id
                            AND pp.id = npp.place_point_id
                            AND p.active=1
                            AND pp.active = 1
                            AND pp.deleted_at IS NULL
                            AND npp.neighbourhood_id='" . $neighbourhoodId . "' " . $placeFilter . $otherFilters . $kitchensQuery . "
                            GROUP BY p.id";


        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        $places = $stmt->fetchAll();

        foreach ($places as $pkey => &$place) {
            $place['place'] = $this->find($place['place_id']);

            $place['point'] = $this->getEntityManager()->getRepository('FoodDishesBundle:PlacePoint')->find($place['point_id']);

        }

        return $places;
    }


    public function getDeliveryPriceForPlacePoint(Place $place, PlacePoint $placePoint, $locationData, $noneWorking = false, $fututeDate = false)
    {
        $cityRepo = $this->getEntityManager()->getRepository('FoodAppBundle:City');
        $city = $cityRepo->find($locationData['city_id']);

        $rushHour = ' ';
        if($city->getZavalasOn()){
            $rushHour = ' AND active_on_zaval = 1';
        }

        $data = $this->getPlacePointNearWithDistance($place->getId(), $locationData, false, false, $noneWorking, $fututeDate);
        $currTime = date('H:i:s');
        $deliveryPrice = "SELECT price 
                          FROM `place_point_delivery_zones` 
                          WHERE place_point=" . (int)$data['id'] . " 
                          AND active=1 
                          AND distance >= " . (float)$data['distance'] . " 
                          AND (time_from <= '" . $currTime . "' AND '" . $currTime . "' <= time_to) 
                          AND deleted_at IS NULL 
                          ".$rushHour
            ." ORDER BY distance ASC LIMIT 1";
        $stmt = $this->getEntityManager()->getConnection()->prepare($deliveryPrice);
        $stmt->execute();
        $result = $stmt->fetchColumn();

        if (empty($result)) {
            $deliveryPrice = "SELECT price
                              FROM `place_point_delivery_zones`
                              WHERE place_point = " . (int)$data['id'] . "
                              AND active = 1
                              AND distance >= " . (float)$data['distance']."
                              AND time_from IS NULL
                              AND time_to IS NULL
                              AND deleted_at IS NULL
                             ".$rushHour .
                " ORDER BY distance ASC LIMIT 1"
            ;
            $stmt = $this->getEntityManager()->getConnection()->prepare($deliveryPrice);
            $stmt->execute();
            $result = $stmt->fetchColumn();
        }

        return $result;
    }

    public function getMinimumCartForPlacePoint(Place $place, PlacePoint $placePoint, $locationData)
    {
        $data = $this->getPlacePointNearWithDistance($place->getId(), $locationData);
        $deliveryPrice = "SELECT cart_size FROM `place_point_delivery_zones` WHERE place_point=" . (int)$data['id'] . " AND active=1 AND distance >= " . (float)$data['distance'] . " ORDER BY distance ASC LIMIT 1";
        $stmt = $this->getEntityManager()->getConnection()->prepare($deliveryPrice);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    /**
     * @param int $placeId
     * @param array|null $locationData
     * @param bool $ignoreSelfDelivery
     *
     * @param bool $ignoreWorkTime
     * @param bool $noneWorking
     * @return PlacePoint|null
     */
    public function getPlacePointNearWithDistance($placeId, $locationData, $ignoreSelfDelivery = false, $ignoreWorkTime = false, $noneWorking = false, $futureTime = false)
    {
        if (empty($locationData['city_id']) || empty($locationData['latitude'])) {
            return null;
        }

        $cityString = "AND pp.city_id='" . $locationData['city_id'] . "'";
        $cityId = $locationData['city_id'];

        if($cityId){
            $cityObj = $this->getEntityManager()->getRepository('FoodAppBundle:City')->find($cityId);
            if($cityObj && !$cityObj->getActive()){
                $cityString = " ";
            }
        }

        $lat = $locationData['latitude'];
        $lon = $locationData['longitude'];

        if ($futureTime) {
            $dh = date("H", strtotime($futureTime));
            $dm = date("i", strtotime($futureTime));
            $wd = date('w', strtotime($futureTime));

        } else {
            $dh = date("H");
            $dm = date("i");
            $wd = date('w');
        }

        $deliveryTime = date('H:i:s');

        if ($wd == 0) $wd = 7;

        if (!$noneWorking) {
            $limitQuery = "LIMIT 1";
            $hours = '';
        } else {
            $limitQuery = '';
            $hours = ', ppwt.start_hour, ppwt.start_min';
        }


        $defaultZone = "SELECT MAX(ppdzd.distance) FROM `place_point_delivery_zones` ppdzd WHERE ppdzd.deleted_at IS NULL AND ppdzd.active=1 AND ppdzd.place_point = pp.id";
        $maxDistance = "SELECT MAX(ppdz.distance) FROM `place_point_delivery_zones` ppdz WHERE ppdz.deleted_at IS NULL AND ppdz.active=1 AND ppdz.place_point=pp.id AND ((time_from <= '" . $deliveryTime . "' AND '" . $deliveryTime . "' <= time_to) OR time_from IS NULL AND time_to IS NULL)";

        $subQuery = "SELECT pp.id, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) as distance " . $hours . "
                    FROM place_point pp, place p " . ((!$ignoreWorkTime) ? ", place_point_work_time ppwt" : "") . "
                    WHERE p.id = pp.place
                    " . ((!$ignoreWorkTime) ? "AND pp.id = ppwt.place_point" : "") . "
                        AND pp.active=1
                        AND pp.deleted_at IS NULL
                        AND p.active=1
                        ".$cityString."
                        AND pp.place = $placeId
            AND (
                (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) <=
                IF(($maxDistance) IS NULL, ($defaultZone), ($maxDistance))
                " . (!$ignoreSelfDelivery ? "" : "") . "
            ) ";


        if (!$ignoreWorkTime) {

            $subQuery .= " AND ppwt.week_day = " . $wd;
            if (!$noneWorking) {
                $subQuery .= "
                      AND (
                        (ppwt.start_hour = 0 OR ppwt.start_hour < $dh OR
                          (ppwt.start_hour <= $dh AND ppwt.start_min <= $dm)
                        ) AND
                        ((ppwt.end_hour >= $dh AND ppwt.end_min >= $dm) OR
                            ppwt.end_hour > $dh OR ppwt.end_hour = 0)
                      )";
            }
        }

        $subQuery .= " AND delivery=1 ORDER BY fast DESC, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) ASC " . $limitQuery . "";

        $stmt = $this->getEntityManager()->getConnection()->prepare($subQuery);

        $stmt->execute();
        $places = $stmt->fetchAll();

        if (!empty($places[0])) {

            if ($noneWorking) {

                $sort = array();
                foreach ($places as $k => $v) {
                    $sort['start_hour'][$k] = $v['start_hour'];
                    $sort['start_min'][$k] = $v['start_min'];
                }

                array_multisort($sort['start_hour'], SORT_ASC, $sort['start_min'], SORT_ASC, $places);

                return $places[0];

            } else {

                if (!empty($places[0])) {
                    return $places[0];
                }

            }
        }
        return null;
    }

    /**
     * @param int $placeId
     * @param array|null $locationData
     * @param bool $ignoreSelfDelivery
     *
     * @return int|null
     *
     * @todo ar dar naudojamas shitas?
     */
    public function getPlacePointNear($placeId, $locationData, $ignoreSelfDelivery = false, $futureTime = false)
    {


        $response = null;
        $cacheKey = $placeId . serialize($locationData) . (int)$ignoreSelfDelivery;

        if (!isset(self::$_getNearCache[$cacheKey])) {
            if (!empty($locationData['latitude'])) {

                $lat = $locationData['latitude'];
                $lon = $locationData['longitude'];

                if (!$futureTime) {
                    $futureTime = date("Y-m-d H:i:s");
                }
                $dh = date("H", strtotime($futureTime));
                $dm = date("i", strtotime($futureTime));
                $wd = date('w', strtotime($futureTime));
                $deliveryTime = date("H:i:s", strtotime($futureTime));

                if ($wd == 0) $wd = 7;
                /**
                 * @todo check the need of self delivery
                 */


                $maxDistance = "SELECT MAX(ppdz.distance) FROM `place_point_delivery_zones` ppdz WHERE ppdz.deleted_at IS NULL AND ppdz.active=1 AND ppdz.place_point=pp.id AND ((time_from <= '" . $deliveryTime . "' AND '" . $deliveryTime . "' <= time_to) OR (time_from IS NULL AND time_to IS NULL))";

                $subQuery = "SELECT pp.id, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) )))
                    FROM place_point pp, place p, place_point_work_time ppwt
                    WHERE p.id = pp.place
                      AND pp.id = ppwt.place_point
                      AND pp.active=1
                      AND pp.deleted_at IS NULL
                      AND p.active=1
                      AND pp.place = $placeId
                      AND (
                        (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) <=
                      ($maxDistance)
                        " . (!$ignoreSelfDelivery ? "" : "") . "
                    )
                      AND ppwt.week_day = " . $wd . "
                      AND (
                        (ppwt.start_hour = 0 OR ppwt.start_hour < " . $dh . " OR
                          (ppwt.start_hour <= " . $dh . " AND ppwt.start_min <= " . $dm . ")
                        ) AND
                        ((ppwt.end_hour >= " . $dh . " AND ppwt.end_min >= " . $dm . ") OR
                            ppwt.end_hour > " . $dh . " OR ppwt.end_hour = 0)
                      )
                      AND delivery=1
                    ORDER BY fast DESC, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) ASC LIMIT 1";

                $stmt = $this->getEntityManager()->getConnection()->prepare($subQuery);

                $stmt->execute();

                $places = $stmt->fetchAll();
                if (!empty($places) && !empty($places[0])) {
                    $response = (int)$places[0]['id'];
                } else {
                    //@mail('karolis.m@foodout.lt', 'DEBUG LOG getPlacePointNear', $lat . ' ' . $lon . ' ' . $placeId . ' ' . $city . "\n\n\n" . $subQuery . "\n\n\n" . print_r(debug_backtrace(2), true), "FROM: info@foodout.lt");
                }
            }
            self::$_getNearCache[$cacheKey] = $response;
        }

        return self::$_getNearCache[$cacheKey];
    }

    public function getPlacePointNearWithWorkCheck($placeId, $locationData)
    {
        $response = null;
        $cacheKey = $placeId . serialize($locationData) . 1;
        if (!isset(self::$_getNearCache[$cacheKey])) {
            if (!empty($locationData['latitude'])) {
                $lat = $locationData['latitude'];
                $lon = $locationData['longitude'];
                $wd = date('w') == 0 ? 7 : date("w");

                /**
                 * @todo check the need of self delivery
                 */

                $defaultZone = "SELECT MAX(ppdzd.distance) FROM `place_point_delivery_zones` ppdzd WHERE ppdzd.deleted_at IS NULL AND ppdzd.active=1 AND ppdzd.place_point IS NULL AND ppdzd.place IS NULL";
                $maxDistance = "SELECT MAX(ppdz.distance) FROM `place_point_delivery_zones` ppdz WHERE ppdz.deleted_at IS NULL AND ppdz.active=1 AND ppdz.place_point=pp.id";

                $subQuery = "SELECT pp.id, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) )))
                    FROM place_point pp, place p, place_point_work_time ppwt
                    WHERE p.id = pp.place
                      AND pp.id = ppwt.place_point
                      AND pp.active=1
                      AND pp.deleted_at IS NULL
                      AND p.active=1
                      AND pp.place = $placeId
                      AND (
                        (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) <=
                        IF(($maxDistance) IS NULL, ($defaultZone), ($maxDistance))
                       )
                      AND ppwt.week_day = " . $wd . "
                      AND ppwt.start_hour != 0
                      AND delivery=1
                    ORDER BY fast DESC, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) ASC LIMIT 1";


                $stmt = $this->getEntityManager()->getConnection()->prepare($subQuery);

                $stmt->execute();
                $places = $stmt->fetchAll();
                if (!empty($places) && !empty($places[0])) {
                    $response = (int)$places[0]['id'];
                } else {
                    //@mail('karolis.m@foodout.lt', 'DEBUG LOG getPlacePointNear', $lat . ' ' . $lon . ' ' . $placeId . ' ' . $city . "\n\n\n" . $subQuery . "\n\n\n" . print_r(debug_backtrace(2), true), "FROM: info@foodout.lt");
                }
            }
            self::$_getNearCache[$cacheKey] = $response;
        }

        return self::$_getNearCache[$cacheKey];
    }


    /**
     * @return Place[]
     */
    public function getRecommendedForTitle($city = null)
    {
        $otherFilters = '';
        // 21:30 isjungiame alkoholiku rodyma :)
        $hour = date("H");
        if ($hour > '21' || ($hour == '21' && date('i') > '30')) {
            $otherFilters .= ' AND p.only_alcohol != 1';
        }

        $join = '';
        $where = '';
        if ($city) {
            $join = ' INNER JOIN place_point pp ON pp.place = p.id ';
            $where = ' AND pp.active = 1 AND pp.city = "' . $city . '" AND pp.deleted_at IS NULL ';
        }


        $query = "SELECT p.id
                FROM place p
                {$join}
                WHERE
                    p.active = 1
                    AND p.recommended = 1
                    AND p.deleted_at IS NULL
                    {$otherFilters}
                    {$where}
                GROUP BY p.id
                ORDER BY p.navision DESC, RAND()
                LIMIT 5";


        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        $placesIds = $stmt->fetchAll();
        shuffle($placesIds);
        $places = [];
        foreach ($placesIds as $placeRow) {
            $places[] = $this->find($placeRow['id']);
        }

        return $places;


    }

    public function getMinDeliveryPrice($placeId)
    {
        $minPrice = "SELECT MIN(price) AS price FROM `place_point_delivery_zones` WHERE deleted_at IS NULL AND active=1 AND place=" . (int)$placeId;
        $stmt = $this->getEntityManager()->getConnection()->prepare($minPrice);
        $stmt->execute();

        return $stmt->fetchColumn(0);
    }

    public function getMaxDeliveryPrice($placeId)
    {
        $minPrice = "SELECT MAX(price) AS price FROM `place_point_delivery_zones` WHERE deleted_at IS NULL AND active=1 AND place=" . (int)$placeId;
        $stmt = $this->getEntityManager()->getConnection()->prepare($minPrice);
        $stmt->execute();

        return $stmt->fetchColumn(0);
    }

    public function getMinCartSize($placeId)
    {
        $minPrice = "SELECT MIN(cart_size) AS price FROM `place_point_delivery_zones` WHERE deleted_at IS NULL AND active=1 AND place=" . (int)$placeId;
        $stmt = $this->getEntityManager()->getConnection()->prepare($minPrice);
        $stmt->execute();

        return $stmt->fetchColumn(0);
    }

    public function getMaxCartSize($placeId)
    {
        $minPrice = "SELECT MAX(cart_size) AS price FROM `place_point_delivery_zones` WHERE deleted_at IS NULL AND active=1 AND place=" . (int)$placeId;
        $stmt = $this->getEntityManager()->getConnection()->prepare($minPrice);
        $stmt->execute();

        return $stmt->fetchColumn(0);
    }

    public function isPlacePointWorks(PlacePoint $placePoint, $ts = null)
    {
        if (!$ts) {
            $ts = time();
        }
        $wd = date('w', $ts);
        if ($wd == 0) $wd = 7;
        $totalH = date("H", $ts);
        $totalM = date("i", $ts);
        $count = 'SELECT count(id)
                  FROM `place_point_work_time`
                  WHERE week_day = ' . $wd . '
                    AND (
                        (start_hour < ' . $totalH . ' OR
                            (start_hour <= ' . $totalH . ' AND start_min <= ' . $totalM . ')
                        ) AND (
                        (end_hour >= ' . $totalH . ' AND end_min >= ' . $totalM . ') OR
                            end_hour > ' . $totalH . '))
                    AND `place_point` = ' . $placePoint->getId() . '
                    LIMIT 1';

        $stmt = $this->getEntityManager()->getConnection()->prepare($count);
        $stmt->execute();

        return (boolean)$stmt->fetchColumn(0);
    }


    /**
     * @param Place $place
     * @return mixed
     * @description metodas dubliuotas nes keiciamas jo pavadinimas :) tas kitas zemiau deprecated :)
     */
    public function getCityCollectionByPlace(Place $place)
    {
        if (empty(self::$_citiesCache[$place->getId()])) {
            self::$_citiesCache[$place->getId()] = [];
            foreach ($place->getPoints() as $placePoint) {
                if ($placePoint && $placePoint->getActive() && !in_array($placePoint->getCityId(), self::$_citiesCache[$place->getId()], true)) {
                    $cityObj =  $placePoint->getCityId();
                    if($cityObj) {
                        self::$_citiesCache[$place->getId()][] = $cityObj;
                    }
                }
            }
        }

        return self::$_citiesCache[$place->getId()];
    }

        /**
     * @param Place $place
     * @deprecated from 2017-04-12
     * @return array
     */
    public function getCities(Place $place)
    {

        if (empty(self::$_citiesCache[$place->getId()])) {
            self::$_citiesCache[$place->getId()] = [];
            foreach ($place->getPoints() as $placePoint) {

                if ($placePoint->getActive() && !in_array($placePoint->getCityId(), self::$_citiesCache[$place->getId()], true)) {
                    self::$_citiesCache[$place->getId()][] = $placePoint->getCityId();
                }
            }
        }
        return self::$_citiesCache[$place->getId()];
    }

    public function getRelatedKitchens($placeId){

        $query = "SELECT kitchen_id FROM place_kitchen WHERE place_id = " . (int)$placeId;

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getRelatedSeoRecords($placeId){

        $query = "SELECT seorecord_id FROM place_seorecords WHERE place_id = " . (int)$placeId;

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
