<?php

namespace Food\PlacesBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Entity\City;
use Food\AppBundle\Utils\Misc;

class BannerLinkService
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Misc
     */
    private $misc;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
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
