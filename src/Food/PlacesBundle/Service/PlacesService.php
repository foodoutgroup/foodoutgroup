<?php

namespace Food\PlacesBundle\Service;

use Food\AppBundle\Entity\City;
use Food\AppBundle\Entity\Slug;
use Food\AppBundle\Service\SlugService;
use Food\DishesBundle\Entity\Kitchen;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Service\OrderService;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\AppBundle\Traits;
use Symfony\Component\HttpFoundation\Request;
use Food\AppBundle\Utils\Language;

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
            ->find($placeId);
    }


//    /**
//     * @param int $placeId
//     *
//     * @return \Food\DishesBundle\Entity\Place
//     *
//     * @throws \InvalidArgumentException
//     */
//    public function getPlace($placeCode)
//    {
//        if (empty($placeId)) {
//            throw new \InvalidArgumentException('Cant search a place without and id. How can you find a house without address?');
//        }
//
//        return $this->em()->getRepository('FoodDishesBundle:Place')
//            ->find($placeId)
//            ;
//    }

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
     * @deprecated
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
     * @param int $dishId
     *
     * @return \Food\DishesBundle\Entity\Place|false
     */
    public function getPlaceByDish($dishId)
    {
        $dish = $this->em()->getRepository('FoodDishesBundle:Dish')->findOneBy(['id' => $dishId]);
        if (!$dish) {
            return false;
        } else {
            return $dish->getPlace();
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
                    'place' => $place->getId(),
                    'active' => 1,
                ],
                [
                    'lineup' => 'DESC'
                ]
            );
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
                'place' => $place->getId(),
                'public' => 1,
                'active' => 1,
            ],
                [
                    'city' => 'DESC',
                    'address' => 'ASC'
                ]
            );
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
            ]);
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
            ->getQuery();

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
        $rating = $con->fetchColumn("SELECT AVG( rate ) FROM place_reviews WHERE active = '1' AND place_id = " . $place->getId());

        return $rating;
    }

    public function placesPlacePointsWorkInformation($places)
    {
        $sortArrPrio = [];
        $sortArr = [];
        $sortTop = [];
        $sortIsDelivering = [];
        foreach ($places as &$place) {
            $place['show_top'] = 0;
            if ($place['pp_count'] == 1) {
                $place['is_work'] = ($this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->isPlacePointWorks($place['point']) ? 1 : 9);
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
            $place['is_delivering'] = $this->getContainer()->get('food.order')->isPlaceDeliveringToAddress($place['place']);
            /*if ($place['place']->getNavision()) {
                $place['show_top'] = 1;
            }*/
            $sortArrPrio[] = intval($place['priority']);// + ($place['place']->getNavision() ? 20:0);
            $sortArr[] = $place['is_work'];
            $sortTop[] = $place['show_top'];
            $sortIsDelivering[] = $place['is_delivering'];
        }

        array_multisort($sortTop, SORT_NUMERIC, SORT_DESC, $sortArr, SORT_NUMERIC, SORT_ASC, $sortIsDelivering, SORT_NUMERIC, SORT_DESC, $sortArrPrio, SORT_NUMERIC, SORT_DESC, $places);

        return $places;
    }

    /**
     * @param string $slug_filter
     * @param            $request
     * @param bool|false $names
     * @deprecated
     * @return array
     */
    public function getKitchensFromSlug($slugCollection, $request, $names = false)
    {
        $kitchens = [];

        if(!is_array($slugCollection)) {
            $slugCollection = explode("/", $slugCollection);
        }

        foreach ($slugCollection as $key => &$value) {

            $item_by_slug = $this->container->get('doctrine')->getManager()
                ->getRepository('FoodAppBundle:Slug')
                ->findOneBy(['name' => str_replace('#', '', trim($value)), 'type' => Slug::TYPE_KITCHEN, 'lang_id' => $request->getLocale()]);

            if (!empty($item_by_slug)) {
                if ($names == false) {
                    $kitchens[] = $item_by_slug->getItemId();
                } else {
                    $kitchen = $this->container->get('doctrine')
                        ->getRepository('FoodDishesBundle:Kitchen')->find($item_by_slug->getItemId());
                    if (!empty($kitchen)) {
                        $kitchens[] = $kitchen->getName();
                    }
                }
            }
        }
        return $kitchens;
    }

    /**
     * @param array $slugCollection
     * @param $request
     * @return array
     * @deprecated todo: change or redactor 2017-06-20
     */
    public function getKitchenCollectionFromSlug($slugCollection = [], $request)
    {
        if(!is_array($slugCollection)) {
            $slugCollection = explode("/", $slugCollection);
        }

        $kitchenCollection = [];

        $slugService = $this->getContainer()->get('slug');
//
//        // todo MULTI-L perdaryti i viena select :)
//        foreach ($slugCollection as $key => $value) {
//            $item = $slugService->getObjBySlug($value, Slug::TYPE_KITCHEN);
//
//            if(!empty($item) && $kitchen = $this->em()->getRepository('FoodDishesBundle:Kitchen')->find((int)$item->getItemId())) {
//                $kitchenCollection[] = $kitchen;
//            }
//        }
        return $kitchenCollection;
    }

    /**
     * @param Request $request
     * @param array $slug_filter
     * @param bool $rush_hour
     * @return array|mixed
     */
    public function getPlacesForList($rush_hour = false, Request $request)
    {
        $kitchens = $request->get('kitchens', "");
        $filters = $request->get('filters');


        if ($rush_hour) {
            $deliveryType = '';
        } else {
            $deliveryType = $this->container->get('session')->get('delivery_type', OrderService::$deliveryDeliver);
        }

        $kitchens = empty($kitchens) ? [] : explode(",", $kitchens);
        // TODO lets debug this strange scenario :(
        if (empty($filters)) {
            $filters = [];
        }


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
            $this->container->get('food.location')->get(),
            $this->container
        );



        $this->container->get('food.places')->saveRelationPlaceToPoint($places);
        $places = $this->container->get('food.places')->placesPlacePointsWorkInformation($places);

        if ($rush_hour) {
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
            } # Kiti veza P&D & 1
            elseif ($deliveryOption == 'delivery_and_pickup' && $selfDelivery) {
                $place['show_top'] = 3;
                $place['priority'] = 5000;
            } # Kiti veza D & 1
            elseif ($deliveryOption == 'delivery' && $selfDelivery) {
                $place['show_top'] = 2;
                $place['priority'] = 3000;
            } # Kiti veza P & 1
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
        if ($place->getSelfDelivery()) {
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

            return floatval($place->getCartMinimum());
        }

        return floatval($sum);
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
                ]);
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
                    'cityId'    => $city,
                    'address' => $address,
                ]);

        }

        return $user_address;
    }

    /**
     * @TODO: this should now about zaval
     * @param Place $place
     * @param PlacePoint|null $placePoint
     * @param string|null $dateShift
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

            foreach ($placePoints as $key => $placePoint){

                if($placePoint->getActive() == 1){
                    $placePoint = $placePoints[$key];
                }
            }
        }

        $workTime = $placePoint->{'getWd' . $day}();
        $workTime = preg_replace('~\s*-\s*~', '-', $workTime);
        $intervals = explode(' ', $workTime);
        $firstIntervalOnDay = true;
        $graph = [];
        foreach ($intervals as $interval) {
            if ($times = $this->parseIntervalToTimes($interval)) {
                list($startHour, $startMin, $endHour, $endMin) = $times;
            } else {
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

            $strtime = $firstIntervalOnDay ? '+1 hour' : '+30 minute';

            if (date('w') != $day ||
                (date('H', strtotime($strtime)) <= $startHour ||
                    date('H', strtotime($strtime)) == $startHour && date('i', strtotime($strtime)) < $startMin) ||
                (date('H', strtotime($strtime)) == $startHour && date('i', strtotime($strtime)) > 30)
            ) {
                // first open on day +1h
                if ($firstIntervalOnDay) {
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

            $firstIntervalOnDay = false;
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
     *
     * @return string
     */
    public function getDeliveryTime(Place $place, PlacePoint $placePoint = null, $type = false)
    {
        if ($type && $type == 'pedestrian') {
            $deliveryTime = $place->getDeliveryTime();
        } else {
            $deliveryTime = $placePoint ? $placePoint->getDeliveryTime() : $place->getDeliveryTime();
            if (!$place->getSelfDelivery() && !$place->getNavision() && $this->isShowZavalDeliveryTime($place)) {
                $rhDeliveryTime = $this->container->get('food.zavalas_service')->getRushHourTimeByPlace($place);
                if ($rhDeliveryTime) {
                    $deliveryTime = $rhDeliveryTime;
                }
            }
        }
        return $deliveryTime;
    }

    /**
     * we show zaval time if:
     * no locationdata is set
     * no city is set
     * city is in zaval zone
     * place do not delivers to setted city, but has place point in zaval zone
     *
     * @param Place $place
     *
     * @return bool
     */
    public function isShowZavalDeliveryTime(Place $place)
    {
        $response = false;
        $rhService = $this->container->get('food.zavalas_service');
        if ($rhService->isRushHourEnabled()) {
            $locationData = $this->container->get('food.location')->get();
            $placeCityCollection = $this->em()->getRepository('FoodDishesBundle:Place')->getCityCollectionByPlace($place);
            foreach ($placeCityCollection as $city) {
                if ($rhService->isRushHourAtCity($city)
                    && (empty($locationData)
                        || empty($locationData['city'])
                        || $rhService->isRushHourAtCityById($locationData['city_id'])
                        || !$this->isPlaceDeliversToCity($place, $locationData['city_id']))) {
                        $response = true;
                        break;
                }
            }
        }

        return $response;
    }

    /**
     * @param Place $place
     * @param       $city
     *
     * @return bool
     */
    public function isPlaceDeliversToCity(Place $place, $cityId)
    {
       return $this->container->get('doctrine')->getRepository('FoodDishesBundle:PlacePoint')->isDeliverToCity($place, $cityId);
    }

    /**
     * @todo refactor to have 1 exit point
     *
     * @param string $interval
     *
     * @return array|bool
     */
    public function parseIntervalToTimes($interval = '')
    {
        if (strpos($interval, '-') === false) {
            return false;
        }

        list($start, $end) = explode('-', $interval);
        if (strlen($start) < 1 || strlen($end) < 1) {
            return false;
        }

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
            return false;
        }

        return [$startHour, $startMin, $endHour, $endMin];
    }

    /**
     * @param City $city
     * @return Kitchen[]
     */
    public function getKitchensByCity(City $city)
    {
        $kitchenCollection = [];
        $placeCollection = $this->getPlacesByCity($city);

        foreach ($placeCollection as $place) {
            foreach ($place->getKitchens() as $kitchen) {
                if (!in_array($kitchen, $kitchenCollection)) {
                    $kitchenCollection[] = $kitchen;
                }
            }
        }

        return $kitchenCollection;
    }

    /**
     * @return Place[]
     */
    public function getPlacesByCity(City $city)
    {
        $placeCollection = [];

        $placePointCollection = $this->getDoctrine()->getRepository('FoodDishesBundle:PlacePoint')->findBy(array('cityId' => $city->getId())); // todo MULTI-L refactor :)
        foreach ($placePointCollection as $placePoint) {
            $placeCollection[] = $placePoint->getPlace();
        }
        return $placeCollection;
    }

    /**
     * @param $placeId
     * @param $city
     * @return null
     */
    public function getPlaceUrlByCity($placeId, $city)
    {
        $current_place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find((int)$placeId);
        if (!empty($current_place) && !empty($city)) {
            $name = $current_place->getName();
            $repo = $this->em()->getRepository('FoodDishesBundle:PlacePoint');
            $placePoints = $repo->getPlacePointsBy($name, $city);

            foreach ($placePoints as $placePoint) {
                $place = $placePoint->getPlace();
                if ($place->getActive()) {
                    return $this->container->get('food.dishes.utils.slug')->getSlugByItem($place->getId(), 'place');
                }
            }
        }
        return null;
    }

    /**
     * @param $placeId
     * @return null|string
     */
    public function getCitiesByPlace($placeId)
    {
        $citiesArr = [];
        $current_place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find((int)$placeId);
        if (!empty($current_place)) {
            $name = $current_place->getName();
            $repo = $this->em()->getRepository('FoodDishesBundle:PlacePoint');
            $placePoints = $repo->getCitiesByPlaceName($name);

            foreach ($placePoints as $placePoint) {
                $place = $placePoint->getPlace();
                $city = $placePoint->getCityId()->getTitle();
                if ($place->getActive()) {
                    $citiesArr[] = $city;
                }
            }
            return array_unique($citiesArr);
        }
        return null;
    }

    public function useAdminFee(Place $place)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (is_object($user)){
            if ($user->getIsBussinesClient() ) {
                return false;
            }
        }
        $dontUseFee = !$place->isUseAdminFee();
        if ($dontUseFee === false)
        {
            return false;
        }
        else{
            return (bool) $this->container->get('food.app.utils.misc')->getParam('use_admin_fee_globally'); //Globally set to use fee?
        }
        return (bool)$dontUseFee;
    }

    public function getAdminFee(Place $place)
    {
        $feeSize = $place->getAdminFee();
        if (!$feeSize)
        {
            $feeSize = $this->container->get('food.app.utils.misc')->getParam('admin_fee_size');
        }
        return floatval(str_replace(',', '.',  $feeSize));
    }

    public function getPedestrianDeliveryTime()
    {
        return $this->container->get('food.app.utils.misc')->getParam('pedestrian_delivery_time');
    }

    /**
     * @param string $city
     * @return string
     * @deprecated from 2017-05-22
     */
    public function getCityName($city) //TODO-ML A tikrai to reik?
    {
        $country = $this->container->getParameter('country');
        if ($country == "LV" && $city == 'Rīga') {
            $city = 'Rīga un pierīga';
        }

        return $city;
    }

    public function getListPedestrianFilter()
    {
        $return = false;
        $location = $this->container->get('food.location')->get();
        if($location['city_id']) {
            $cityCheck = $this->em()->getRepository('FoodAppBundle:City')->find($location['city_id']);
            if ($cityCheck && $cityCheck->getPedestrian()) {
                $return = true;
            }
        }

        return $return;
    }
}
