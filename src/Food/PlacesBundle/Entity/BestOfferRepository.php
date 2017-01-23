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
        $items = array();
        if (!empty($activeIds)) {
            $queryBuilder = $this->createQueryBuilder('best_offer')
                ->where('best_offer.id IN (:ids)')
                ->setParameter('ids', $activeIds);

            $items = $queryBuilder->getQuery()->getResult();
            shuffle($items);
        }

        return $items;
    }

    /**
     * @param string|null $city
     * @param boolean $forMobile
     * @return array|BestOffer[]
     */
    public function getActiveOffers($city = null, $forMobile = false)
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.active = :activity');

        $params = ['activity' => true];

        if ($forMobile) {
            $qb->andWhere('o.useUrl != :use_url');
            $params['use_url'] = true;
        }

        if (!empty($city)) {

            $city = strtolower($city);

            $city = str_replace('klaipeda', 'klaipėda', $city);
            $city = str_replace('marijampole', 'marijampolė', $city);
            $city = str_replace('siauliai', 'šiauliai', $city);
            $city = str_replace('plunge', 'plungė', $city);
            $city = str_replace('panevežys', 'panevezys', $city);
            $city = str_replace('panevėzys', 'panevezys', $city);
            $city = str_replace('panevezys', 'panevėžys', $city);
            $city = ucfirst($city);

            $qb->andWhere($qb->expr()->like('o.text', ':city_filter'));
            $params['city_filter'] = '%'.$city.'%';
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
