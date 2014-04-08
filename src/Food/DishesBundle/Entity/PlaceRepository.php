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
    public function magicFindByKitchensIds($kitchens, $filters, $recommended = false, $locationData = null)//, $city, $lat, $long)
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

        $city = $locationData['city'];
        $lat = $locationData['lat'];
        $lon = $locationData['lng'];


        $subQuery = "SELECT id  FROM place_point WHERE active=1 AND city='".$city."' AND place = p.id AND (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) <= 7 ORDER BY fast DESC, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) ASC LIMIT 1";
        $kitchensQuery = "";

        if (!empty($kitchens)) {
            $kitchensQuery = "AND p.id IN (SELECT place_id FROM place_kitchen WHERE kitchen_id IN(".implode(",", $kitchens)."))";
        }

        if ($recommended) {
            if (!empty($kitchensQuery)) {
                $kitchensQuery.= "AND recommended=1";
            } else {
                $kitchensQuery.= " recommended=1";
            }
            $query = "SELECT p.id as place_id, pp.id as point_id FROM place p, place_point pp WHERE pp.place = p.id AND ".$kitchensQuery;
        } else {
            $query = "SELECT p.id as place_id, pp.id as point_id FROM place p, place_point pp WHERE pp.place = p.id AND pp.id =  (". $subQuery .") ".$kitchensQuery;
        }

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        $places = $stmt->fetchAll();

        foreach ($places as $pkey=>&$place) {
            $place['place'] = $this->find($place['place_id']);
            $place['point'] = $this->getEntityManager()->getRepository('FoodDishesBundle:PlacePoint')->find($place['point_id']);
        }

        return $places;
    }

    /**
     * @return Place[]
     */
    public function getRecommendedForTitle()
    {
        $query = "SELECT p.id FROM place p WHERE p.active = 1 AND p.recommended = 1 AND p.deleted_at IS NULL ORDER BY RAND()";
        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        $placesIds = $stmt->fetchAll();
        $places = array();
        foreach ($placesIds as $placeRow) {
            $places[] = $this->find($placeRow['id']);
        }
        return $places;
    }
}

?>