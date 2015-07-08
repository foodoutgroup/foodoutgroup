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

        $from = intval($from);
        $to = intval($to);

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