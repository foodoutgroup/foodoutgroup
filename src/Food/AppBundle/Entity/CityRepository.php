<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class CityRepository extends EntityRepository
{

    public function getZavalasTimeByTitle($cityTitle)
    {
        return $this->findOneBy(array('title' => $cityTitle))->getZavalasTime();
    }

    public function getActive()
    {
        return $this->findBy(['active' => 1], ['position' => 'ASC']);
    }

    public function getBestOffersByCity($id = false)
    {

        $result = $this->createQueryBuilder('c')->select('b.id')
            ->leftJoin('c.bestOffers', 'b')
            ->where('b.active = :active');

        $params = ['active' => 1];

        if ($id) {
            $result->andWhere('c.id = :id');
            $params['id'] = $id;
        }
        $result->setParameters($params)
            ->setMaxResults(5);

        $return = $result->getQuery()->getResult();

        return $return;


    }

}
