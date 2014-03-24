<?php
namespace Food\PlacesBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;
use Food\AppBundle\Traits;

class PlacesService extends ContainerAware {
    use Traits\Service;

    public function __construct()
    {

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
            throw new \InvalidArgumentException('Cant seach a place without and id. How can you find a house without address?');
        }

        return $this->em()->getRepository('FoodDishesBundle:Place')
            ->find($placeId);
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
            ->findBy(array(
                'place' => $place->getId(),
                'active' => 1,
            ));
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
     * @param int $limit
     * @return array|\Food\DishesBundle\Entity\Place[]
     */
    public function getTopRatedPlaces($limit=10)
    {
        $placesQuery = $this->em()->getRepository('FoodDishesBundle:Place')
            ->createQueryBuilder('p')
            ->where('p.active = 1')
            ->orderBy('p.averageRating', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();

        return $placesQuery->getResult();
    }
}