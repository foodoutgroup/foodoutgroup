<?php
namespace Food\PlacesBundle\Service;

use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\AppBundle\Traits;

class PlacesService extends ContainerAware {
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
     * @return \Food\DishesBundle\Entity\Place
     *
     * @throws \InvalidArgumentException
     */
    public function getPlace($placeId) {
        if (empty($placeId)) {
            throw new \InvalidArgumentException('Cant search a place without and id. How can you find a house without address?');
        }

        return $this->em()->getRepository('FoodDishesBundle:Place')
            ->find($placeId);
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
     * @return array|\Food\DishesBundle\Entity\FoodCategory[]
     */
    public function getActiveCategories($place)
    {
        return $this->em()->getRepository('FoodDishesBundle:FoodCategory')
            ->findBy(
                array(
                    'place' => $place->getId(),
                    'active' => 1,
                ),
                array(
                    'lineup' => 'DESC'
                )
            );
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $place
     * @return array|\Food\DishesBundle\Entity\PlacePoint[]
     */
    public function getPublicPoints($place)
    {
        return $this->em()->getRepository('FoodDishesBundle:PlacePoint')
            ->findBy(array(
                'place' => $place->getId(),
                'public' => 1,
                'active' => 1,
            ));
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $place
     * @return array|\Food\DishesBundle\Entity\PlacePoint[]
     */
    public function getAllPoints($place)
    {
        return $this->em()->getRepository('FoodDishesBundle:PlacePoint')
            ->findBy(array(
                'place' => $place->getId()
            ));
    }

    /**
     * @param $pointId
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
        $rel = array();
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
            $sessionData = array();
        }
        $sessionData[$placeId] = $pointId;

        $this->getSession()->set('point_data', $sessionData);
    }

    /**
     * @param int $limit
     * @return array|\Food\DishesBundle\Entity\Place[]
     */
    public function getTopRatedPlaces($limit=10)
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
     * @return mixed
     */
    public function calculateAverageRating($place)
    {
        $em = $this->em();
        $con = $em->getConnection();
        $rating = $con->fetchColumn("SELECT AVG( rate ) FROM  place_reviews WHERE place_id = ".$place->getId());

        return $rating;
    }

    public function placesPlacePointsWorkInformation($places)
    {
        $sortArrPrio = array();
        $sortArr = array();
        $sortTop = array();
        foreach ($places as &$place) {
            $place['show_top'] = 0;
            if ($place['pp_count'] == 1) {
                $place['is_work'] = ($this->container->get('food.order')->isTodayWork($place['point']) ? 1:9);
            } else {
                if ($this->container->get('food.order')->isTodayWorkDayForAll($place['place'])) {
                    $place['is_work'] = 1;
                } else {
                    if ($this->container->get('food.order')->isTodayNoOneWantsToWork($place['place'])) {
                        $place['is_work'] = 9;
                    } else {
                        $place['is_work'] = 2;
                    }
                }
            }
            if ($place['place']->getNavision()) {
                $place['show_top'] = 1;
            }
            $sortArrPrio[] = intval($place['priority']);// + ($place['place']->getNavision() ? 20:0);
            $sortArr[] = $place['is_work'];
            $sortTop[] = $place['show_top'];
        }

        array_multisort($sortTop,SORT_NUMERIC, SORT_DESC, $sortArr, SORT_NUMERIC, SORT_ASC, $sortArrPrio, SORT_NUMERIC, SORT_DESC, $places);
        return $places;
    }

    /**
     * @param string $slug_filter
     * @param $request
     * @param bool|false $names
     * @return array
     */
    public function getKitchensFromSlug($slug_filter = '', $request, $names = false) {
        $kitchens = array();
        $slugs = explode("/", $slug_filter);
        foreach ($slugs as $skey=> &$slug) {
            $item_by_slug = $this->container->get('doctrine')->getManager()
                ->getRepository('FoodAppBundle:Slug')
                ->findOneBy(array('name' => str_replace('#', '', trim($slug)), 'type' => 'kitchen', 'lang_id' => $request->getLocale()));
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
     * @param $recommended
     * @param $request
     * @param $slug_filter
     * @return mixed
     */
    public function getPlacesForList($recommended, $request, $slug_filter = false)
    {
        $kitchens = $request->get('kitchens', "");
        $filters = $request->get('filters');
        $deliveryType = $request->get('delivery_type', '');
        if (empty($kitchens)) {
            $kitchens = array();
        } else {
            $kitchens = explode(",", $kitchens);
        }

        if (!empty($slug_filter)) {
            $kitchens = $this->getKitchensFromSlug($slug_filter, $request);
        }

        // TODO lets debug this strange scenario :(
        if (is_array($filters)) {
            $this->getContainer()->get('logger')->error('getPlacesForList filters param got array -cant be. Array contents: '.var_export($filters, true));
        }

        $filters = explode(",", $filters);
        if (!empty($deliveryType)) {
            $filters['delivery_type'] = $deliveryType;
        }

        foreach ($kitchens as $kkey=> &$kitchen) {
            $kitchen = intval($kitchen);
        }
        foreach ($filters as $fkey=> &$filter) {
            $filter = trim($filter);
        }

        $places = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
            $kitchens,
            $filters,
            $recommended,
            $this->container->get('food.googlegis')->getLocationFromSession()
        );
        $this->container->get('food.places')->saveRelationPlaceToPoint($places);
        return $this->container->get('food.places')->placesPlacePointsWorkInformation($places);
    }

    public function getMinDeliveryPrice($placeId)
    {
        $sum = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:Place')->getMinDeliveryPrice($placeId);
        if (empty($sum)) {
            $place = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find($placeId);
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

    public function getCurrentUserAddresses() {
        $current_user = $this->container->get('security.context')->getToken()->getUser();
        $all_user_address = array();
        if (!empty($current_user) && is_object($current_user)) {
            $all_user_address = $this->container->get('doctrine')->getRepository('FoodUserBundle:UserAddress')
                ->findBy(array(
                    'user' => $current_user,
                ));
        }
        return $all_user_address;
    }

    public function getCurrentUserAddress($city, $address) {
        $current_user = $this->container->get('security.context')->getToken()->getUser();
        $user_address = array();
        if (!empty($current_user) && is_object($current_user) && !empty($city) && !empty($address)) {
            $user_address = $this->container->get('doctrine')->getRepository('FoodUserBundle:UserAddress')
                ->findOneBy(array(
                    'user' => $current_user,
                    'city' => $city,
                    'address' => $address,
                ));
        }
        return $user_address;
    }

    /**
     * @param Place $place
     * @param PlacePoint|null $placePoint
     * @param string|null $dateShift
     * @return array
     */
    public function getFullRangeWorkTimes($place, $placePoint=null, $dateShift=null)
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

        $from = $placePoint->{'getWd'.$day.'Start'}();
        $to = $placePoint->{'getWd'.$day.'EndLong'}();
        if (empty($to)) {
            $to = $placePoint->{'getWd'.$day.'End'}();
        }

        if (strpos($from, ':') === false) {
            return array();
        }

        $from = str_replace(':', '', $from);
        $to = str_replace(':', '', $to);
        $graph = array();

        if (($to < '0500' && $to >= '0000') || $to > '2400') {
            $to = '2400';
        }

        // +100 nes duodam restoranui atsidaryt, negali gi pristatyt ta pacia valanda, kai atsidare restoranas :D
        $from = intval($from)+100;
        $to = intval($to);

        // jei restoranas jau dirba ilgiau nei valanda - nuo dabar duodam užsakyt tik po valandos, jei kalbam apie siandien
        if ($dateShift == 0 && $from <= date("Hi", strtotime('+30 minute'))) {
            $from = intval(date('H').'00') + 100;
            if (date('i') > 0 && date('i') <= 30) {
                $from = $from + 30;
            } else if (date('i') > 30) {
                $from = $from +100;
            }
        }

        // If restaurant starts at a dumbt time, that is not our wanted 00 or 30 - fix dat crap
        $minutes = $from%100;
        if ($minutes != 0 && $minutes != 30) {
            $hour = ($from - ($from%100))/100;

            if ($minutes < 30) {
                $minutes = '30';
            } else {
                $minutes = '00';
                $hour++;
            }
            if ($hour < 10) {
                $hour = '0'.$hour;
            }
            $from = $hour.$minutes;
        }

        $i = $from;

        while($i <= $to) {
            if ($i%100 == 60) {
                $i = $i+40;
            }

            $hour = ($i - ($i%100))/100;
            if ($hour < 10) {
                $hour = '0'.$hour;
            }
            $minutes = $i%100;
            if ($minutes == 0) {
                $minutes = '00';
            } elseif ($minutes < 10) {
                $minutes = '0'.$minutes;
            }
            $graph[] = $hour.':'.$minutes;

            $i = $i+30;
        }

        return $graph;
    }
}