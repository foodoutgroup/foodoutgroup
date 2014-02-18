<?php

namespace Food\DishesBundle\Entity;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;

class PlaceRepository extends EntityRepository
{
    /**
     * @param $kitchens
     * @param $city
     * @param $long
     * @param $lat
     * @return array
     */
    public function findByKitchensIds($kitchens, $filters)//, $city, $lat, $long)
    {
        $city = "Vilnius";
        $lat = "54.680437";
        $lon = "25.261236";

        /*
            SET @lat1 = 54.680437, @lon1 = 25.261236, @lat2 = 54.681914, @lon2 = 25.268156;
            SELECT (6371 * 2 * ASIN(SQRT(POWER(SIN((@lat1 - abs(@lat2)) * pi()/180 / 2), 2) + COS(abs(@lat1) * pi()/180 ) * COS(abs(@lat2) * pi()/180) * POWER(SIN((@lon1 - @lon2) * pi()/180 / 2), 2) ))) as dist;
         */

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
    }
}

?>