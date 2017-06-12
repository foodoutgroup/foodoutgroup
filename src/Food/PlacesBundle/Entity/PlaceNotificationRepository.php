<?php

namespace Food\PlacesBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Food\AppBundle\Entity\City;
use Food\DishesBundle\Entity\Place;

class PlaceNotificationRepository extends EntityRepository
{
    public function get(City $city = null, Place $place = null)
    {
        $qb = $this->createQueryBuilder('pn');
        $qb->where('1=1'); // xDDDDDDDDDDDDDDDDDDDDD

        $params = [];

        if($city != null) {
            $qb->andWhere('pn.cityCollection = :city');
            $params['city'] = $city->getId();
        }

        if($place != null) {
            $qb->andWhere('pn.placeCollection = :place');
            $params['place'] = $place->getId();
        }

        $qb->andWhere('pn.active = 1');

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->isNull('pn.showTill'),
                $qb->expr()->lte('pn.showTill', ':date')
            )
        );

        $params['date'] = date("Y-m-d H:i:s");

        return $qb->getQuery()->execute($params);
    }
}
