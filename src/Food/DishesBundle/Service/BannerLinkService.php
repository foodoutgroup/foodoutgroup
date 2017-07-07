<?php

namespace Food\DishesBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Entity\City;
use Food\AppBundle\Utils\Misc;
use Food\DishesBundle\Entity\Place;

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

    public function getBannerLink(Place $place){
        return $this->em->getRepository('FoodDishesBundle:BannerLinks')->getBannerLinkByPlace($place->getId());
    }
}
