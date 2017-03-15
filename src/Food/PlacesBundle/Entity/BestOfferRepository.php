<?php

namespace Food\PlacesBundle\Entity;

use Doctrine\ORM\EntityRepository;

class BestOfferRepository extends EntityRepository
{

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
            $params['city_filter'] = '%' . $city . '%';
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

    public function getBestOffersByIds($ids){


        $result = $this->findBy(['id'=>$ids]);

        return $result;
    }
}
