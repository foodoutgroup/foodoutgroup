<?php

namespace Food\DishesBundle\Entity;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;

class PlaceRepository extends EntityRepository
{
    /**
     * @param array $kitchens
     * @param array $filters
     * @param bool $recommended
     * @param array|null $locationData
     * @param $container
     * @return array
     */
    public function magicFindByKitchensIds($kitchens, $filters=array(), $recommended = false, $locationData = null, $container = null)//, $city, $lat, $long)
    {
        /*
            SET @lat1 = 54.680437, @lon1 = 25.261236, @lat2 = 54.681914, @lon2 = 25.268156;
            SELECT (6371 * 2 * ASIN(SQRT(POWER(SIN((@lat1 - abs(@lat2)) * pi()/180 / 2), 2) + COS(abs(@lat1) * pi()/180 ) * COS(abs(@lat2) * pi()/180) * POWER(SIN((@lon1 - @lon2) * pi()/180 / 2), 2) ))) as dist;
         */
/*
        $qb = $this->createQueryBuilder('p');
        $qb->join('p.kitchens', 'f')
            ->where(
                $qb->expr()->in('f.id', $kitchens)
            );

        $qb->join('p.points', 'pp')
            ->where("pp.city = :city")
            //->where("(6371 * 2 * ASIN(SQRT(POWER(SIN((:lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs(:lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN((:lon - pp.lon) * pi()/180 / 2), 2) ))) <= 7")
            ->andWhere("(6371 * 2 * ASIN(SQRT(POWER(SIN((:lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs(:lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN((:lon - pp.lon) * pi()/180 / 2), 2) ))) <= 7")
            ->setParameter('city', $city)
            ->setParameter('lat', $lat)
            ->setParameter('lon', $lon);


        return $qb->getQuery()->getResult();
*/

        /*
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult('FoodDishesBundle:Place', 'p');
        $rsm->addFieldResult('p', 'id', 'id');
        $rsm->addFieldResult('p', 'name', 'name');
        $rsm->addFieldResult('p', 'logo', 'logo');
        $rsm->addEntityResult('FoodDishesBundle:PlacePoint', 'pp');
        $rsm->addEntityResult('FoodDishesBundle:Kitchen', 'pk');


        $queryPart_kitchen = "";

        $query = $this->_em->createNativeQuery("
            SELECT
                p.id, p.name, p.logo
            FROM place p
            JOIN place_point pp

            WHERE
                pp.place = p.id
                AND pp.city = '$city'
                AND (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) <= 7
                "
        , $rsm);
        */
        //return $query->getResult();
        $city = null;
        $lat = null;
        $lon = null;

        if (!empty($locationData) && !empty($locationData['lat']) && !empty($locationData['lng'])) {
            $city = (!empty($locationData['city']) ? $locationData['city'] : null);
            $lat = str_replace(",", ".", $locationData['lat']);
            $lon = str_replace(",", ".", $locationData['lng']);
        } elseif (!empty($locationData) && !empty($locationData['city']) && isset($locationData['city_only']) && $locationData['city_only'] === true) {
            $city = $locationData['city'];
        }

        /**
         * $container->getParameter('default_delivery_distance')
         *  This stuff needs to be deprecated. And parameter removed.
         */

        $defaultZone = "SELECT MAX(ppdzd.distance) FROM `place_point_delivery_zones` ppdzd WHERE ppdzd.deleted_at IS NULL AND ppdzd.active=1 AND ppdzd.place_point IS NULL AND ppdzd.place IS NULL";
        $maxDistance = "SELECT MAX(ppdz.distance) FROM `place_point_delivery_zones` ppdz WHERE ppdz.deleted_at IS NULL AND ppdz.active=1 AND ppdz.place_point=pps.id";

        $subQuery = "SELECT id FROM place_point pps WHERE active=1 AND deleted_at IS NULL AND place = p.id
            AND (
            (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pps.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pps.lat) * pi()/180) * POWER(SIN(($lon - pps.lon) * pi()/180 / 2), 2) ))) <=
                IF(($maxDistance) IS NULL, (".$defaultZone."), ($maxDistance))
            )
            ORDER BY fast DESC, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pps.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pps.lat) * pi()/180) * POWER(SIN(($lon - pps.lon) * pi()/180 / 2), 2) ))) ASC LIMIT 1";
        $kitchensQuery = "";

        if (!empty($kitchens)) {
            $kitchensQuery = " AND p.id IN (SELECT place_id FROM place_kitchen WHERE kitchen_id IN(".implode(",", $kitchens)."))";
        }

        // Place filters
        $placeFilter = '';
        if (is_array($filters) && !empty($filters)) {
            foreach ($filters as $filterName => $filterValue) {
                switch($filterName) {
                    case 'keyword':
                        if (!empty($filterValue)) {
                            $placeFilter .= ' AND p.name LIKE "%'.$filterValue.'%"';
                        }
                        break;

                    case 'delivery_type':
                        if (!empty($filterValue)) {
                            switch($filterValue) {
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

        if ($recommended) {
//            if (!empty($kitchensQuery)) {
                $kitchensQuery.= " AND recommended=1";
//            } else {
//                $kitchensQuery.= " recommended=1";
//            }
            $ppCounter = "SELECT COUNT(*) FROM place_point ppc WHERE ppc.active=1 AND ppc.deleted_at IS NULL AND ppc.place = p.id";
            $query = "SELECT p.id as place_id, pp.id as point_id, pp.address, (".$ppCounter.") as pp_count, p.priority, p.navision FROM place p, place_point pp WHERE pp.place = p.id AND p.active=1 AND pp.active = 1 AND pp.deleted_at IS NULL ".$placeFilter.$otherFilters.$kitchensQuery." GROUP BY p.id ORDER BY p.priority DESC, RAND()";
        } elseif ($lat == null || $lon == null) {
            if ($city == null) {
                $ppCounter = "SELECT COUNT(*) FROM place_point ppc WHERE ppc.active=1 AND ppc.deleted_at IS NULL AND ppc.place = p.id";
                $query = "SELECT p.id as place_id, pp.id as point_id, pp.address, (".$ppCounter.") as pp_count, p.priority, p.navision FROM place p, place_point pp WHERE pp.place = p.id AND p.active=1 AND pp.active = 1 AND pp.deleted_at IS NULL ".$placeFilter.$otherFilters.$kitchensQuery." GROUP BY p.id";
            } else {
                $ppCounter = "SELECT COUNT(*) FROM place_point ppc WHERE ppc.active=1 AND ppc.deleted_at IS NULL AND ppc.city='".$city."' AND ppc.place = p.id";
                $query = "SELECT p.id as place_id, pp.id as point_id, pp.address, (".$ppCounter.") as pp_count, p.priority, p.navision FROM place p, place_point pp WHERE pp.place = p.id AND p.active=1 AND pp.active = 1 AND pp.deleted_at IS NULL AND pp.city='".$city."' ".$placeFilter.$otherFilters.$kitchensQuery." GROUP BY p.id";
            }
        } else {
            $ppCounter = "SELECT COUNT(*) FROM place_point ppc WHERE ppc.active=1 AND ppc.deleted_at IS NULL AND ppc.city='".$city."' AND ppc.place = p.id";
            $query = "SELECT p.id as place_id, pp.id as point_id, pp.address, (".$ppCounter.") as pp_count, p.priority, p.navision FROM place p, place_point pp WHERE pp.place = p.id AND p.active=1 AND pp.active = 1 AND pp.deleted_at IS NULL AND pp.city='".$city."' ".$placeFilter.$otherFilters." AND pp.id =  (". $subQuery .") ".$kitchensQuery." ORDER BY p.priority DESC, RAND()";
        }

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        $places = $stmt->fetchAll();

        $dh = date("H");
        $dm = date("i");
        $wd = date("N");
        if (intval($dh) < 6) {
            $dh = 24 + intval($dh);
            $wd = date("N", strtotime("-1 day"));
        }
        $dth = $dh."".$dm;

        foreach ($places as $pkey=>&$place) {
            //var_dump($place['pp_count']);
            $place['place'] = $this->find($place['place_id']);

            $placePointQuery = "
                SELECT pps.id
                    FROM place_point pps,
                    place p
                    WHERE
                        p.id = pps.place
                        AND pps.active=1
                        AND pps.deleted_at is NULL
                        AND pps.city='".$city."'
                        AND pps.place = ".$place['place']->getId()."
                        AND '".$dth."' BETWEEN  (REPLACE(wd".$wd."_start, ':','') + 0) AND IF(wd".$wd."_end_long IS NULL, wd".$wd."_end, wd".$wd."_end_long)
                        AND pps.delivery=1
                        AND (
            (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pps.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pps.lat) * pi()/180) * POWER(SIN(($lon - pps.lon) * pi()/180 / 2), 2) ))) <= 7

            )
            ORDER BY fast DESC, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pps.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pps.lat) * pi()/180) * POWER(SIN(($lon - pps.lon) * pi()/180 / 2), 2) ))) ASC LIMIT 1"
            ;

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


    public function getDeliveryPriceForPlacePoint(Place $place, PlacePoint $placePoint, $locationData)
    {
        $data = $this->getPlacePointNearWithDistance($place->getId(), $locationData);
        $deliveryPrice = "SELECT price FROM `place_point_delivery_zones` WHERE place_point=".(int)$data['id']." AND active=1 AND distance >= ".(float)$data['distance']." ORDER BY distance ASC LIMIT 1";
        $stmt = $this->getEntityManager()->getConnection()->prepare($deliveryPrice);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getMinimumCartForPlacePoint(Place $place, PlacePoint $placePoint, $locationData)
    {
        $data = $this->getPlacePointNearWithDistance($place->getId(), $locationData);
        $deliveryPrice = "SELECT cart_size FROM `place_point_delivery_zones` WHERE place_point=".(int)$data['id']." AND active=1 AND distance >= ".(float)$data['distance']." ORDER BY distance ASC LIMIT 1";
        $stmt = $this->getEntityManager()->getConnection()->prepare($deliveryPrice);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * @param int $placeId
     * @param array|null $locationData
     * @param bool $ignoreSelfDelivery
     * @return float|null
     */
    public function getPlacePointNearWithDistance($placeId, $locationData, $ignoreSelfDelivery = false, $ignoreWorkTime = false)
    {
        if (empty($locationData['city']) || empty($locationData['lat'])) {
            return null;
        }
        $city = $locationData['city'];
        $lat = str_replace(",", ".", $locationData['lat']);
        $lon = str_replace(",", ".", $locationData['lng']);

        $dh = date("H");
        $dm = date("i");
        $wd = date("N");
        if (intval($dh) < 6) {
            $dh = 24 + intval($dh);
            $wd = date("N", strtotime("-1 day"));
        }
        $dth = $dh."".$dm;

        $defaultZone = "SELECT MAX(ppdzd.distance) FROM `place_point_delivery_zones` ppdzd WHERE ppdzd.deleted_at IS NULL AND ppdzd.active=1 AND ppdzd.place_point IS NULL AND ppdzd.place IS NULL";
        $maxDistance = "SELECT MAX(ppdz.distance) FROM `place_point_delivery_zones` ppdz WHERE ppdz.deleted_at IS NULL AND ppdz.active=1 AND ppdz.place_point=pp.id";
        /**
         * @todo check the need of self delivery
         */
        $subQuery = "SELECT pp.id, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) as distance FROM place_point pp, place p WHERE p.id = pp.place AND pp.active=1 AND pp.deleted_at IS NULL AND p.active=1 AND pp.city='".$city."' AND pp.place = $placeId
            AND (
                (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) <=
                IF(($maxDistance) IS NULL, ($defaultZone), ($maxDistance))
                ".(!$ignoreSelfDelivery ? "":"")."
            ) ";

        if (!$ignoreWorkTime) {
            $subQuery.=" AND '".$dth."' BETWEEN (REPLACE(wd".$wd."_start,':','') + 0) AND IF(wd".$wd."_end_long IS NULL, wd".$wd."_end, wd".$wd."_end_long)";
        }

        $subQuery.=" AND delivery=1 ORDER BY fast DESC, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) ASC LIMIT 1";

        $stmt = $this->getEntityManager()->getConnection()->prepare($subQuery);

        $stmt->execute();
        $places = $stmt->fetchAll();

        if (!empty($places) && !empty($places[0])) {
            return $places[0];
        }
        return null;
    }

    /**
     * Backup solution for API. Finds place point with lowest cart and delivery price
     *
     * @param int $placeId
     * @param array|null $locationData
     * @param boolean $ignoreWorkTime
     * @return float|null
     */
    public function getCheapestPlacePoint($placeId, $locationData, $ignoreWorkTime = false)
    {
        $city = null;
        if (isset($locationData['city']) && !empty($locationData['city'])) {
            $city = $locationData['city'];
        }

        $dh = date("H");
        $dm = date("i");
        $wd = date("N");
        if (intval($dh) < 6) {
            $dh = 24 + intval($dh);
            $wd = date("N", strtotime("-1 day"));
        }
        $dth = $dh."".$dm;

        $subQuery = "SELECT pp.id FROM place_point pp, place p WHERE p.id = pp.place AND pp.active=1 AND pp.public=1 AND pp.deleted_at IS NULL AND p.active=1 AND pp.place = $placeId";

        if (!empty($city)) {
            $subQuery.= " AND pp.city='".$city."'";
        }

        if (!$ignoreWorkTime) {
            $subQuery.=" AND '".$dth."' BETWEEN (REPLACE(wd".$wd."_start,':','') + 0) AND IF(wd".$wd."_end_long IS NULL, wd".$wd."_end, wd".$wd."_end_long)";
        }

        $subQuery.=" AND delivery=1 ORDER BY pp.delivery_time ASC, fast DESC LIMIT 1";

        $stmt = $this->getEntityManager()->getConnection()->prepare($subQuery);

        $stmt->execute();
        $places = $stmt->fetchAll();

        if (!empty($places) && !empty($places[0])) {
            return $places[0];
        }
        return null;
    }

    /**
     * @param int $placeId
     * @param array|null $locationData
     * @param bool $ignoreSelfDelivery
     * @return int|null
     *
     * @todo ar dar naudojamas shitas?
     */
    public function getPlacePointNear($placeId, $locationData, $ignoreSelfDelivery = false)
    {
        if (empty($locationData['city']) || empty($locationData['lat'])) {
            return null;
        }
        $city = $locationData['city'];
        $lat = str_replace(",", ".", $locationData['lat']);
        $lon = str_replace(",", ".", $locationData['lng']);

        $dh = date("H");
        $dm = date("i");
        $wd = date("N");
        if (intval($dh) < 6) {
            $dh = 24 + intval($dh);
            $wd = date("N", strtotime("-1 day"));
        }
        $dth = $dh."".$dm;
        /**
         * @todo check the need of self delivery
         */

        $defaultZone = "SELECT MAX(ppdzd.distance) FROM `place_point_delivery_zones` ppdzd WHERE ppdzd.deleted_at IS NULL AND ppdzd.active=1 AND ppdzd.place_point IS NULL AND ppdzd.place IS NULL";
        $maxDistance = "SELECT MAX(ppdz.distance) FROM `place_point_delivery_zones` ppdz WHERE ppdz.deleted_at IS NULL AND ppdz.active=1 AND ppdz.place_point=pp.id";

        $subQuery = "SELECT pp.id, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) FROM place_point pp, place p WHERE p.id = pp.place AND pp.active=1 AND pp.deleted_at IS NULL AND p.active=1 AND pp.city='".$city."' AND pp.place = $placeId
            AND (
                (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) <=
                IF(($maxDistance) IS NULL, ($defaultZone), ($maxDistance))
                ".(!$ignoreSelfDelivery ? "":"")."
            )
            AND '".$dth."' BETWEEN (REPLACE(wd".$wd."_start,':','')+0) AND IF(wd".$wd."_end_long IS NULL, wd".$wd."_end, wd".$wd."_end_long)
            AND delivery=1
            ORDER BY fast DESC, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) ASC LIMIT 1";

        $stmt = $this->getEntityManager()->getConnection()->prepare($subQuery);

        $stmt->execute();
        $places = $stmt->fetchAll();
        if (!empty($places) && !empty($places[0])) {
            return (int)$places[0]['id'];
        }
        return null;
    }

    /**
     * @return Place[]
     */
    public function getRecommendedForTitle()
    {
        $otherFilters = '';
        // 21:30 isjungiame alkoholiku rodyma :)
        $hour = date("H");
        if ($hour > '21' || ($hour == '21' && date('i') > '30')) {
            $otherFilters .= ' AND p.only_alcohol != 1';
        }

        $query = "SELECT p.id
                FROM place p
                WHERE
                    p.active = 1
                    AND ((p.navision=1 AND p.recommended = 1) OR p.recommended = 1)
                    AND p.deleted_at IS NULL
                    {$otherFilters}
                ORDER BY p.navision DESC, RAND()
                LIMIT 5";
        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        $placesIds = $stmt->fetchAll();
        shuffle($placesIds);
        $places = array();
        foreach ($placesIds as $placeRow) {
            $places[] = $this->find($placeRow['id']);
        }
        return $places;
    }

    public function getMinDeliveryPrice($placeId)
    {
        $minPrice = "SELECT MIN(price) as price FROM `place_point_delivery_zones` WHERE deleted_at IS NULL AND active=1 AND place=".(int)$placeId;
        $stmt = $this->getEntityManager()->getConnection()->prepare($minPrice);
        $stmt->execute();
        return $stmt->fetchColumn(0);
    }

    public function getMaxDeliveryPrice($placeId)
    {
        $minPrice = "SELECT MAX(price) as price FROM `place_point_delivery_zones` WHERE deleted_at IS NULL AND active=1 AND place=".(int)$placeId;
        $stmt = $this->getEntityManager()->getConnection()->prepare($minPrice);
        $stmt->execute();
        return $stmt->fetchColumn(0);
    }

    public function getMinCartSize($placeId)
    {
        $minPrice = "SELECT MIN(cart_size) as price FROM `place_point_delivery_zones` WHERE deleted_at IS NULL AND active=1 AND place=".(int)$placeId;
        $stmt = $this->getEntityManager()->getConnection()->prepare($minPrice);
        $stmt->execute();
        return $stmt->fetchColumn(0);
    }

    public function getMaxCartSize($placeId)
    {
        $minPrice = "SELECT MAX(cart_size) as price FROM `place_point_delivery_zones` WHERE deleted_at IS NULL AND active=1 AND place=".(int)$placeId;
        $stmt = $this->getEntityManager()->getConnection()->prepare($minPrice);
        $stmt->execute();
        return $stmt->fetchColumn(0);
    }
}

?>
