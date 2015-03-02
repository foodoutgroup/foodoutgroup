<?php

namespace Food\PlacesBundle\Entity;

use Doctrine\ORM\EntityRepository;

class BestOfferRepository extends EntityRepository
{
    public function getRandomBestOffers($amount)
    {
        $queryBuilder = $this->createQueryBuilder('best_offer')
                             ->select('best_offer.id')
                             ->where('best_offer.active = 1');

        $activeIds = array_map([$this, 'filterIds'], $queryBuilder->getQuery()->getResult());
        $activeIds = array_slice($activeIds, 0, 5);

        if (0 == count($activeIds)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('best_offer')
                             ->where('best_offer.id IN (:ids)')
                             ->setParameter('ids', $activeIds);

        $items = $queryBuilder->getQuery()->getResult();
        shuffle($items);

        return $items;
    }

    private function filterIds($element)
    {
        return $element['id'];
    }
}
