<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\EntityRepository;

class PlacePointRepository extends EntityRepository
{
    /**
     * @param $name
     * @param $city
     * @return PlacePoint
     */
    public function getPlacePointsBy($name, $city)
    {
        return $this->createQueryBuilder('pp')
            ->select('pp')
            ->innerJoin('Food\DishesBundle\Entity\Place', 'p', 'WITH', 'p.id = pp.place')
            ->where('p.name LIKE :name')
            ->andWhere('pp.city = :city')
            ->andWhere('pp.active = 1')
            ->setParameter('name', '%' . $name . '%')
            ->setParameter('city', $city)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $name
     * @return array
     */
    public function getCitiesByPlaceName($name)
    {
        return $this->createQueryBuilder('pp')
            ->select('pp')
            ->innerJoin('Food\DishesBundle\Entity\Place', 'p', 'WITH', 'p.id = pp.place')
            ->where('p.name LIKE :name')
            ->andWhere('pp.active = 1')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()
            ->getResult();
    }


    public function isDeliverToCity(Place $place, $cityId)
    {

        $query = $this->createQueryBuilder('pp')
            ->where('pp.place = :placeId')
            ->andWhere("pp.cityId = :cityId")
            ->andWhere('pp.active = 1')
            ->setParameters(['placeId' => $place->getId(), 'cityId' => $cityId])->getQuery()->execute();

        return count($query);

    }
}