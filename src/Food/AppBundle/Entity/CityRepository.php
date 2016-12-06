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
}
