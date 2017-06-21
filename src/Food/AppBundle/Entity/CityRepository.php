<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class CityRepository extends EntityRepository
{

    public function getZavalasTimeByTitle($cityId)
    {
        return $this->findOneBy(array('city_id' => $cityId))->getZavalasTime();
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

        $result->setParameters($params);
        $result->distinct('b.id');
        $return = $result->getQuery()->getResult();

        $return = array_slice($return,0,5);

        return $return;


    }

    public function getActiveById($id)
    {
        return $this->findBy(['active' => 1,'id'=>$id]);
    }

    /**
     * @param $cityName
     * @return City
     */
    public function getByName($cityName)
    {
        $cityName = ucfirst(strtolower(trim($cityName)));

        $search = [];
        $replace = [];
        $cityName  = str_replace($search, $replace, $cityName);

        return $this->findOneBy(['title' => $cityName]);
    }

    public function getActivePedestrianCityByLocation($id)
    {
        return $this->findBy(['active' => 1, 'pedestrian' => 1,'id'=>$id]);
    }
}
