<?php

namespace Food\PlacesBundle\Entity;

use Doctrine\ORM\EntityRepository;

class BestOfferRepository extends EntityRepository
{
    /**
     * @param int $amount
     * @return BestOffer[]|array
     * @throws \Doctrine\DBAL\DBALException
     */
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

    /**
     * @param string|null $city
     * @return array|BestOffer[]
     */
    public function getActiveOffers($city = null)
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.active = :activity');

        $params = array(
            'activity' => true,
        );

        if (!empty($city)) {
            $qb->andWhere('(o.city IN (:city_filter) OR o.city IS NULL)');
            $params['city_filter'] = $city;
        }

        return $qb->setParameters($params)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $element
     * @return mixed
     */
    private function filterIds($element)
    {
        return $element['id'];
    }
}
