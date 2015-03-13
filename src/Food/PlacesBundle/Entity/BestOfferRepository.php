<?php

namespace Food\PlacesBundle\Entity;

use Doctrine\ORM\EntityRepository;

class BestOfferRepository extends EntityRepository
{
    public function getRandomBestOffers($amount)
    {
        $query = "SELECT id FROM best_offer WHERE active=1 ORDER BY RAND() LIMIT 5";
        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        $activeIds = array();
        $offers = $stmt->fetchAll();
        foreach ($offers as $off) {
            $activeIds[] = $off['id'];
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
