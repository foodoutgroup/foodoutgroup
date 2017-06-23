<?php

namespace Food\PlacesBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Entity\City;
use Food\AppBundle\Utils\Misc;

class PedestrianService
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Misc
     */
    private $misc;

    public function __construct(EntityManager $entityManager,  $misc)
    {
        $this->em = $entityManager;
        $this->misc = $misc;
    }

    public function getPedestrianByCity($cityId){

        $return = false;

        $cityCheck = $this->em->getRepository('FoodAppBundle:City')->getActivePedestrianCityByLocation($cityId);
        $activeList = $this->misc->getParam('pedestrian_filter_show');

        if($cityCheck && $activeList){
            $return = true;
        }

        return $return;
    }
}
