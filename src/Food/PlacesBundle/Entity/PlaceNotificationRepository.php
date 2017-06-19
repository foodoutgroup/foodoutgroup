<?php

namespace Food\PlacesBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Food\AppBundle\Entity\City;
use Food\DishesBundle\Entity\Place;

class PlaceNotificationRepository extends EntityRepository
{
    public function get(City $city = null, Place $place = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('pn')
            ->from('FoodPlacesBundle:PlaceNotification', 'pn');

        if($city != null) {
            $qb->join('pn.cityCollection', 'c')
                ->where('c.id = '.$city->getId());
        }

        if($place != null) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('pn.placeCollection'),
                    $qb->expr()->eq('pn.placeCollection', ':place')
                )
            )->setParameter('place', $place->getId());
        }

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->isNull('pn.showTill'),
                $qb->expr()->gte('pn.showTill', ':date')
            )
        )->setParameter('date', date("Y-m-d H:i:s"));

        $qb->andWhere('pn.active = 1');
        return $qb->getQuery()->execute();
    }
}
