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
}