<?php

namespace Food\DishesBundle\Entity;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;

class PlaceRepository extends EntityRepository
{
    /**
     * @param array $kitchens
     * @param array $filters
     * @param bool $recommended
     * @param array|null $locationData
     * @return array
     */
    public function magicFindByKitchensIds($kitchens, $filters=array(), $recommended = false, $locationData = null)//, $city, $lat, $long)
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
        $city = null;
        $lat = null;
        $lon = null;

        if (!empty($locationData) && !empty($locationData['lat']) && !empty($locationData['lng'])) {
            $city = (!empty($locationData['city']) ? $locationData['city'] : null);
            $lat = str_replace(",", ".", $locationData['lat']);
            $lon = str_replace(",", ".", $locationData['lng']);
        } elseif (!empty($locationData) && !empty($locationData['city']) && isset($locationData['city_only']) && $locationData['city_only'] === true) {
            $city = $locationData['city'];
        }

        $subQuery = "SELECT id FROM place_point pps WHERE active=1 AND deleted_at IS NULL AND place = p.id
            AND (
            (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pps.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pps.lat) * pi()/180) * POWER(SIN(($lon - pps.lon) * pi()/180 / 2), 2) ))) <= 7
                 OR
                 p.self_delivery = 1
            )
            ORDER BY fast DESC, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pps.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pps.lat) * pi()/180) * POWER(SIN(($lon - pps.lon) * pi()/180 / 2), 2) ))) ASC LIMIT 1";
        $kitchensQuery = "";

        if (!empty($kitchens)) {
            $kitchensQuery = "AND p.id IN (SELECT place_id FROM place_kitchen WHERE kitchen_id IN(".implode(",", $kitchens)."))";
        }

        // Place filters
        $placeFilter = '';
        if (is_array($filters) && !empty($filters)) {
            foreach ($filters as $filterName => $filterValue) {
                switch($filterName) {
                    case 'keyword':
                        if (!empty($filterValue)) {
                            $placeFilter .= ' AND p.name LIKE "%'.$filterValue.'%"';
                        }
                        break;

                    default:
                }
            }
        }

        if ($recommended) {
            if (!empty($kitchensQuery)) {
                $kitchensQuery.= "AND recommended=1";
            } else {
                $kitchensQuery.= " recommended=1";
            }
            $ppCounter = "SELECT COUNT(*) FROM place_point ppc WHERE ppc.active=1 AND ppc.deleted_at IS NULL AND ppc.place = p.id";
            $query = "SELECT p.id as place_id, pp.id as point_id, pp.address, (".$ppCounter.") as pp_count, p.priority FROM place p, place_point pp WHERE pp.place = p.id AND p.active=1 AND pp.deleted_at IS NULL ".$placeFilter." AND ".$kitchensQuery." GROUP BY p.id ORDER BY p.priority DESC, RAND()";
        } elseif ($lat == null || $lon == null) {
            if ($city == null) {
                $ppCounter = "SELECT COUNT(*) FROM place_point ppc WHERE ppc.active=1 AND ppc.deleted_at IS NULL AND ppc.place = p.id";
                $query = "SELECT p.id as place_id, pp.id as point_id, pp.address, (".$ppCounter.") as pp_count, p.priority FROM place p, place_point pp WHERE pp.place = p.id AND p.active=1 AND pp.deleted_at IS NULL ".$placeFilter.$kitchensQuery." GROUP BY p.id";
            } else {
                $ppCounter = "SELECT COUNT(*) FROM place_point ppc WHERE ppc.active=1 AND ppc.deleted_at IS NULL AND ppc.city='".$city."' AND ppc.place = p.id";
                $query = "SELECT p.id as place_id, pp.id as point_id, pp.address, (".$ppCounter.") as pp_count, p.priority FROM place p, place_point pp WHERE pp.place = p.id AND p.active=1 AND pp.deleted_at IS NULL AND pp.city='".$city."' ".$placeFilter.$kitchensQuery." GROUP BY p.id";
            }
        } else {
            $ppCounter = "SELECT COUNT(*) FROM place_point ppc WHERE ppc.active=1 AND ppc.deleted_at IS NULL AND ppc.city='".$city."' AND ppc.place = p.id";
            $query = "SELECT p.id as place_id, pp.id as point_id, pp.address, (".$ppCounter.") as pp_count, p.priority FROM place p, place_point pp WHERE pp.place = p.id AND p.active=1 AND pp.deleted_at IS NULL AND pp.city='".$city."' ".$placeFilter." AND pp.id =  (". $subQuery .") ".$kitchensQuery." ORDER BY p.priority DESC, RAND()";
        }

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        $places = $stmt->fetchAll();

        foreach ($places as $pkey=>&$place) {
            //var_dump($place['pp_count']);
            $place['place'] = $this->find($place['place_id']);
            $place['point'] = $this->getEntityManager()->getRepository('FoodDishesBundle:PlacePoint')->find($place['point_id']);
        }
        return $places;
    }

    /**
     * @param int $placeId
     * @param array|null $locationData
     * @param bool $ignoreSelfDelivery
     * @return null
     */
    public function getPlacePointNear($placeId, $locationData, $ignoreSelfDelivery = false)
    {
        if (empty($locationData['city']) || empty($locationData['lat'])) {
            return null;
        }
        $city = $locationData['city'];
        $lat = str_replace(",", ".", $locationData['lat']);
        $lon = str_replace(",", ".", $locationData['lng']);


        $subQuery = "SELECT pp.id FROM place_point pp, place p WHERE p.id = pp.place AND pp.active=1 AND pp.deleted_at IS NULL AND p.active=1 AND pp.city='".$city."' AND pp.place = $placeId
            AND (
                (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) <= 7
                ".(!$ignoreSelfDelivery ? " OR p.self_delivery = 1":"")."
            )
            ORDER BY fast DESC, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) ASC LIMIT 1";

        $stmt = $this->getEntityManager()->getConnection()->prepare($subQuery);

        $stmt->execute();
        $places = $stmt->fetchAll();
        if (!empty($places) && !empty($places[0])) {
            return (int)$places[0]['id'];
        }
        return null;
    }

    /**
     * @return Place[]
     */
    public function getRecommendedForTitle()
    {
        $query = "SELECT p.id FROM place p WHERE p.active = 1 AND p.recommended = 1 AND p.deleted_at IS NULL ORDER BY RAND() LIMIT 5";
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
