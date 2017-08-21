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

    public function findNearestCity($locationData)
    {

        $lat = $locationData['latitude'];
        $lon = $locationData['longitude'];

        if (is_null($lat) || is_null($lon))
        {
            return null;
        }

        $subQuery = "SELECT city_id FROM place_point
                        WHERE active=1 
                        AND deleted_at IS NULL 
                        ORDER BY (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - ABS(lat)) * pi()/180 / 2), 2) + COS(ABS($lat) * pi()/180 ) * COS(ABS(lat) * pi()/180) * POWER(SIN(($lon - lon) * pi()/180 / 2), 2) ))) ASC LIMIT 1";

        $stmt = $this->getEntityManager()->getConnection()->prepare($subQuery);
        $stmt->execute();
        $result = $stmt->fetchAll();

        return count($result) >= 1 ? $result[0]['city_id'] : null;


    }

}