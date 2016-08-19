<?php
namespace Food\PlacesBundle\Service;

use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Service\OrderService;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\AppBundle\Traits;
use Symfony\Component\HttpFoundation\Request;

class PlacesService extends ContainerAware
{
    use Traits\Service;

    public function __construct()
    {

    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function getSession()
    {
        return $this->container->get('session');
    }

    /**
     * @param int $placeId
     *
     * @return \Food\DishesBundle\Entity\Place
     *
     * @throws \InvalidArgumentException
     */
    public function getPlace($placeId)
    {
        if (empty($placeId)) {
            throw new \InvalidArgumentException('Cant search a place without and id. How can you find a house without address?');
        }

        return $this->em()->getRepository('FoodDishesBundle:Place')
            ->find($placeId)
            ;
    }

    public function savePlace($place)
    {
        if (!($place instanceof Place)) {
            throw new \InvalidArgumentException('Place not given. How should I save it?');
        }
        $em = $this->em();
        $em->persist($place);
        $em->flush();
    }

    /**
     * @return mixed
     */
    public function getAvailableCities()
    {
        $em = $this->em();
        $con = $em->getConnection();
        $cities = $con->fetchAll("SELECT DISTINCT(pp.city) FROM `place_point` pp, `place` p WHERE pp.place = p.id AND pp.active=1 AND p.active = 1");
        foreach ($cities as &$city) {
            $city = $city['city'];
        }

        return $cities;
    }

    /**
     * @param int $categoryId
     *
     * @return \Food\DishesBundle\Entity\Place|false
     */
    public function getPlaceByCategory($categoryId)
    {
        $cateogory = $this->em()->getRepository('FoodDishesBundle:FoodCategory')->find($categoryId);

        if (!$cateogory) {
            return false;
        } else {
            return $cateogory->getPlace();
        }
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $place
     *
     * @return array|\Food\DishesBundle\Entity\FoodCategory[]
     */
    public function getActiveCategories($place)
    {
        return $this->em()->getRepository('FoodDishesBundle:FoodCategory')
            ->findBy(
                [
                    'place'  => $place->getId(),
                    'active' => 1,
                ],
                [
                    'lineup' => 'DESC'
                ]
            )
            ;
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $place
     *
     * @return array|\Food\DishesBundle\Entity\PlacePoint[]
     */
    public function getPublicPoints($place)
    {
        return $this->em()->getRepository('FoodDishesBundle:PlacePoint')
            ->findBy([
                'place'  => $place->getId(),
                'public' => 1,
                'active' => 1,
            ],
                [
                    'city'    => 'DESC',
                    'address' => 'ASC'
                ]
            )
            ;
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $place
     *
     * @return array|\Food\DishesBundle\Entity\PlacePoint[]
     */
    public function getAllPoints($place)
    {
        return $this->em()->getRepository('FoodDishesBundle:PlacePoint')
            ->findBy([
                'place' => $place->getId()
            ])
            ;
    }

    /**
     * @param $pointId
     *
     * @return \Food\DishesBundle\Entity\PlacePoint
     */
    public function getPlacePointData($pointId)
    {
        // TODO Trying to catch fatal when searching for PlacePoint
        if (empty($pointId)) {
            $this->getContainer()->get('logger')->error('Trying to find PlacePoint without ID in PlacesService - getPlacePointData');
        }

        return $this->em()->getRepository('FoodDishesBundle:PlacePoint')->find($pointId);
    }

    /**
     * @param array $data
     */
    public function saveRelationPlaceToPoint($data)
    {
        $rel = [];
        foreach ($data as $row) {
            $rel[$row['place_id']] = $row['point_id'];
        }
        $this->getSession()->set('point_data', $rel);
    }

    /**
     * @param $placeId
     * @param $pointId
     */
    public function saveRelationPlaceToPointSingle($placeId, $pointId)
    {
        $sessionData = $this->getSession()->get('point_data');
        if (empty($sessionData)) {
            $sessionData = [];
        }
        $sessionData[$placeId] = $pointId;

        $this->getSession()->set('point_data', $sessionData);
    }

    /**
     * @param int $limit
     *
     * @return array|\Food\DishesBundle\Entity\Place[]
     */
    public function getTopRatedPlaces($limit = 10)
    {
        $placesQuery = $this->em()->getRepository('FoodDishesBundle:Place')
            ->createQueryBuilder('p')
            ->where('p.active = 1')
            ->orderBy('p.averageRating', 'DESC')
            ->addOrderBy('p.reviewCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
        ;

        return $placesQuery->getResult();
    }

    /**
     * @param Place $place
     *
     * @return mixed
     */
    public function calculateAverageRating($place)
    {
        $em = $this->em();
        $con = $em->getConnection();
        $rating = $con->fetchColumn("SELECT AVG( rate ) FROM  place_reviews WHERE place_id = " . $place->getId());

        return $rating;
    }

    public function placesPlacePointsWorkInformation($places)
    {
        $sortArrPrio = [];
        $sortArr = [];
        $sortTop = [];
        foreach ($places as &$place) {
            $place['show_top'] = 0;
            if ($place['pp_count'] == 1) {
                $place['is_work'] = ($this->container->get('food.order')->isTodayWork($place['point']) ? 1 : 9);
            } else {
                if ($this->container->get('food.order')->isTodayWorkDayForAll($place['place'])) {
                    $place['is_work'] = 1;
                } else {
                    if ($this->container->get('food.order')->isTodayNoOneWantsToWork($place['place'])) {
                        $place['is_work'] = 9;
                    } else {
                        //$place['is_work'] = 2;
                        $place['is_work'] = 1;
                    }
                }
            }
            /*if ($place['place']->getNavision()) {
                $place['show_top'] = 1;
            }*/
            $sortArrPrio[] = intval($place['priority']);// + ($place['place']->getNavision() ? 20:0);
            $sortArr[] = $place['is_work'];
            $sortTop[] = $place['show_top'];
        }

        array_multisort($sortTop, SORT_NUMERIC, SORT_DESC, $sortArr, SORT_NUMERIC, SORT_ASC, $sortArrPrio, SORT_NUMERIC, SORT_DESC, $places);

        return $places;
    }

    /**
     * @param string     $slug_filter
     * @param            $request
     * @param bool|false $names
     *
     * @return array
     */
    public function getKitchensFromSlug($slug_filter = '', $request, $names = false)
    {
        $kitchens = [];
        $slugs = explode("/", $slug_filter);
        foreach ($slugs as $skey => &$slug) {
            $item_by_slug = $this->container->get('doctrine')->getManager()
                ->getRepository('FoodAppBundle:Slug')
                ->findOneBy(['name' => str_replace('#', '', trim($slug)), 'type' => 'kitchen', 'lang_id' => $request->getLocale()])
            ;
            if (!empty($item_by_slug)) {
                if ($names == false) {
                    $kitchens[] = $item_by_slug->getItemId();
                } else {
                    $kitchen = $this->container->get('doctrine')
                        ->getRepository('FoodDishesBundle:Kitchen')->find($item_by_slug->getItemId())
                    ;
                    if (!empty($kitchen)) {
                        $kitchens[] = $kitchen->getName();
                    }
                }
            }
        }

        return $kitchens;
    }

    /**
     * @param         $recommended
     * @param Request $request
     * @param bool    $slug_filter
     * @param bool    $zaval
     *
     * @return array|mixed
     */
    public function getPlacesForList($recommended, Request $request, $slug_filter = false, $zaval = false)
    {
        $kitchens = $request->get('kitchens', "");
        $filters = $request->get('filters');
        if ($zaval) {
            $deliveryType = '';
        } else {
            $deliveryType = $this->container->get('session')->get('delivery_type', OrderService::$deliveryDeliver);
        }

        if (empty($kitchens)) {
            $kitchens = [];
        } else {
            $kitchens = explode(",", $kitchens);
        }

        if (!empty($slug_filter)) {
            $kitchens = $this->getKitchensFromSlug($slug_filter, $request);
        }

        // TODO lets debug this strange scenario :(
        if (empty($filters)) {
            $filters = [];
        }
//        if (is_array($filters)) {
//            $this->getContainer()->get('logger')->error('getPlacesForList filters param got array -cant be. Array contents: ' . var_export($filters, true));
//        }

        if (is_string($filters)) {
            $filters = explode(",", $filters);
        }
        if (!empty($deliveryType)) {
            $filters['delivery_type'] = $deliveryType;
        }

        foreach ($kitchens as $kkey => &$kitchen) {
            $kitchen = intval($kitchen);
        }
        foreach ($filters as &$filter) {
            $filter = trim($filter);
        }

        $places = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
            $kitchens,
            $filters,
            $recommended,
            $this->container->get('food.googlegis')->getLocationFromSession()
        )
        ;

        $this->container->get('food.places')->saveRelationPlaceToPoint($places);
        $places = $this->container->get('food.places')->placesPlacePointsWorkInformation($places);

        if ($zaval) {
            $places = $this->container->get('food.places')->zavalPlaces($places);
        }

        return $places;
    }

    /**
     * @param $places
     *
     * @return mixed
     */
    public function zavalPlaces(array $places)
    {
        $sortTop = [];
        $sortArr = [];
        $sortArrPrio = [];
        foreach ($places as &$place) {
            $place['show_top'] = 0;
            $deliveryOption = $place['place']->getDeliveryOptions();
            $selfDelivery = $place['place']->getSelfDelivery();
            # Mes Vezam P & 0
            if ($deliveryOption == 'pickup' && !$selfDelivery) {
                $place['show_top'] = 4;
                $place['priority'] = 8000;
            }

            # Kiti veza P&D & 1
            elseif ($deliveryOption == 'delivery_and_pickup' && $selfDelivery) {
                $place['show_top'] = 3;
                $place['priority'] = 5000;
            }

            # Kiti veza D & 1
            elseif ($deliveryOption == 'delivery' && $selfDelivery) {
                $place['show_top'] = 2;
                $place['priority'] = 3000;
            }

            # Kiti veza P & 1
            elseif ($deliveryOption == 'pickup' && $selfDelivery) {
                $place['show_top'] = 1;
                $place['priority'] = 2000;
            }

            $sortTop[] = $place['show_top'];
            $sortArr[] = $place['is_work'];
            $sortArrPrio[] = intval($place['priority']);
        }

        array_multisort($sortTop, SORT_NUMERIC, SORT_DESC, $sortArr, SORT_NUMERIC, SORT_ASC, $sortArrPrio, SORT_NUMERIC, SORT_DESC, $places);

        return $places;
    }

    public function getMinDeliveryPrice($placeId)
    {
        $place = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find($placeId);
        if (!$place->getSelfDelivery()) {
            return 0;
        }

        $sum = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:Place')->getMinDeliveryPrice($placeId);
        if (empty($sum)) {
            return $place->getDeliveryPrice();
        }

        return $sum;
    }

    public function getMaxDeliveryPrice($placeId)
    {
        $sum = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:Place')->getMaxDeliveryPrice($placeId);
        if (empty($sum)) {
            $place = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find($placeId);

            return $place->getDeliveryPrice();
        }

        return $sum;
    }

    public function getMinCartPrice($placeId)
    {
        $sum = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:Place')->getMinCartSize($placeId);
        if (empty($sum)) {
            $place = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find($placeId);

            return $place->getCartMinimum();
        }

        return $sum;
    }

    public function getMaxCartPrice($placeId)
    {
        $sum = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:Place')->getMaxCartSize($placeId);
        if (empty($sum)) {
            $place = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find($placeId);

            return $place->getCartMinimum();
        }

        return $sum;
    }

    public function getCurrentUserAddresses()
    {
        $current_user = $this->container->get('security.context')->getToken()->getUser();
        $all_user_address = [];
        if (!empty($current_user) && is_object($current_user)) {
            $all_user_address = $this->container->get('doctrine')->getRepository('FoodUserBundle:UserAddress')
                ->findBy([
                    'user' => $current_user,
                ])
            ;
        }

        return $all_user_address;
    }

    public function getCurrentUserAddress($city, $address)
    {
        $current_user = $this->container->get('security.context')->getToken()->getUser();
        $user_address = [];
        if (!empty($current_user) && is_object($current_user) && !empty($city) && !empty($address)) {
            $user_address = $this->container->get('doctrine')->getRepository('FoodUserBundle:UserAddress')
                ->findOneBy([
                    'user'    => $current_user,
                    'city'    => $city,
                    'address' => $address,
                ])
            ;
        }

        return $user_address;
    }

    /**
     * @param Place           $place
     * @param PlacePoint|null $placePoint
     * @param string|null     $dateShift
     *
     * @return array
     */
    public function getFullRangeWorkTimes($place, $placePoint = null, $dateShift = null)
    {
        if (empty($dateShift)) {
            $day = date("w");
        } else {
            $day = date("w", strtotime($dateShift));
        }
        if ($day == 0) $day = 7;

        if (empty($placePoint)) {
            $placePoints = $place->getPoints();

            $placePoint = $placePoints[0];
        }

        $workTime = $placePoint->{'getWd'.$day}();
        $intervals = explode(' ', $workTime);
        $firstOnDay = true;
        $graph = array();
        foreach ($intervals as $interval) {
            if (strpos($interval, '-') === false) {
                continue;
            }
            list($start, $end) = explode('-', $interval);
            if (strpos($start, ':') !== false) {
                list($startHour, $startMin) = explode(':', $start);
            } else {
                $startHour = $start;
                $startMin = 0;
            }

            if (strpos($end, ':') !== false) {
                list($endHour, $endMin) = explode(':', $end);
            } else {
                $endHour = $end;
                $endMin = 0;
            }

            if (!is_numeric($startHour) || !is_numeric($endHour)) {
                continue;
            }

            if ($endHour < $startHour || $endHour == $startHour && $endMin < $startMin) {
                $endHour = 24;
                $endMin = 0;
            }

            // fix start time
            if ($startMin > 0 && $startMin < 30) {
                $startMin = 30;
            } elseif ($startMin > 30) {
                $startHour++;
                $startMin = 0;
            }

            // fix end time
            if ($endMin > 0 && $endMin < 30) {
                $endMin = 0;
            } elseif ($startMin > 30) {
                $endMin = 30;
            }


            $strtime = $firstOnDay ? '+1 hour' : '+30 minute';

            if (0 != $dateShift ||
                (date('H', strtotime($strtime)) < $startHour ||
                    date('H', strtotime($strtime)) == $startHour && date('i', strtotime($strtime)) < $startMin)
            ) {
                // first open on day +1h
                if ($firstOnDay) {
                    $startHour++;

                // else +30min
                } elseif ($startMin) {
                    $startHour++;
                    $startMin = 0;
                } else {
                    $startMin = 30;
                }
            } else {
                $startHour = date('H', strtotime('+1 hour'));
                if (date('i') > 30) {
                    $startMin = 0;
                    $startHour++;
                } else {
                    $startMin = 30;
                }
            }

            while ($startHour < $endHour || $startHour == $endHour && $startMin <= $endMin) {
                $graph[] = sprintf('%02d:%02d', $startHour, $startMin);
                $startMin += 30;
                if (60 == $startMin) {
                    $startHour++;
                    $startMin = 0;
                }
            }

            $firstOnDay = false;
        }

        natsort($graph);

        return array_unique($graph);
//
//        $from = $placePoint->{'getWd'.$day.'Start'}();
//        $to = $placePoint->{'getWd'.$day.'EndLong'}();
//        if (empty($to)) {
//            $to = $placePoint->{'getWd'.$day.'End'}();
//        }
//
//        if (strpos($from, ':') === false) {
//            return array();
//        }
//
//        $from = str_replace(':', '', $from);
//        $to = str_replace(':', '', $to);
//        $graph = array();
//
//        if (($to < '0500' && $to >= '0000') || $to > '2400') {
//            $to = '2400';
//        }
//
//        // +100 nes duodam restoranui atsidaryt, negali gi pristatyt ta pacia valanda, kai atsidare restoranas :D
//        $from = intval($from)+100;
//        $to = intval($to);
//
//        // jei restoranas jau dirba ilgiau nei valanda - nuo dabar duodam užsakyt tik po valandos, jei kalbam apie siandien
//        if ($dateShift == 0 && $from <= date("Hi", strtotime('+30 minute'))) {
//            $from = intval(date('H').'00') + 100;
//            if (date('i') > 0 && date('i') <= 30) {
//                $from = $from + 30;
//            } else if (date('i') > 30) {
//                $from = $from +100;
//            }
//        }
//
//        // If restaurant starts at a dumbt time, that is not our wanted 00 or 30 - fix dat crap
//        $minutes = $from%100;
//        if ($minutes != 0 && $minutes != 30) {
//            $hour = ($from - ($from%100))/100;
//
//            if ($minutes < 30) {
//                $minutes = '30';
//            } else {
//                $minutes = '00';
//                $hour++;
//            }
//            if ($hour < 10) {
//                $hour = '0'.$hour;
//            }
//            $from = $hour.$minutes;
//        }
//
//        $i = $from;
//
//        while($i <= $to) {
//            if ($i%100 == 60) {
//                $i = $i+40;
//            }
//
//            $hour = ($i - ($i%100))/100;
//            if ($hour < 10) {
//                $hour = '0'.$hour;
//            }
//            $minutes = $i%100;
//            if ($minutes == 0) {
//                $minutes = '00';
//            } elseif ($minutes < 10) {
//                $minutes = '0'.$minutes;
//            }
//            $graph[] = $hour.':'.$minutes;
//
//            $i = $i+30;
//        }
//
//        return $graph;
    }


    /**
     * @param Place $place
     *
     * @return bool
     */
    public function getAllowOnlinePayment(Place $place)
    {
        if ($place->getDisabledOnlinePayment()) {
            return false;
        }

        $day_of_week = date('w');
        $current_hour = date('H');
        if (in_array($day_of_week, [0, 1, 2, 3, 4]) && $current_hour == 22 || in_array($day_of_week, [0, 6]) && $current_hour == 0) {
            return false;
        }

        return true;
    }

    /**
     * @param Place $place
     * @return bool|string
     */
    public function getZavalTime(Place $place)
    {
        if (!$place->getSelfDelivery() && !$place->getNavision() && $place->getDeliveryOptions() != 'pickup') {
            $miscService = $this->container->get('food.app.utils.misc');
            $zaval_on = $miscService->getParam('zaval_on');
            $zaval_time = $miscService->getParam('zaval_time');

            if ($zaval_on > 0 && $zaval_time > 0) {
                $zaval_city_exists = $this->findZavalCity($place->getPoints(), $this->getZavalCities());
                if ($zaval_city_exists) {
                    return round($zaval_time / 60, 2);
                }
            }
        }

        return false;
    }

    /**
     * @param PlacePoint[] $placePoints
     * @param $zaval_cities
     * @return bool
     */
    private function findZavalCity($placePoints, $zaval_cities)
    {
        $sessionLocation = $this->container->get('food.googlegis')->getLocationFromSession();
        if (!empty($sessionLocation) && !empty($sessionLocation['city'])) {
            $city = $sessionLocation['city'];
            if (in_array($city, $zaval_cities)) {
                return true;
            } else {
                return false;
            }
        }

        foreach ($placePoints as $placePoint) {
            if (!in_array($placePoint->getCity(), $zaval_cities)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param null $current_city
     * @return array|bool
     */
    public function getZavalCities($current_city = null)
    {
        $cities = [];
        $miscService = $this->container->get('food.app.utils.misc');
        $zaval_cities = $miscService->getParam('zaval_cities');
        $cities_data = explode(',', $zaval_cities);
        if (count($cities_data)) {
            foreach ($cities_data as $city) {
                $city = mb_strtolower($city, 'utf-8');
                $city = mb_eregi_replace('[^a-ž]', ' ', $city);
                $city = mb_eregi_replace('\s+', '', $city);
                $cities[] = mb_convert_case(trim($city), MB_CASE_TITLE, 'utf-8') ;
            }
            if (!empty($current_city)) {
                return in_array($current_city, $cities);
            }
        }
        return $cities;
    }
}
