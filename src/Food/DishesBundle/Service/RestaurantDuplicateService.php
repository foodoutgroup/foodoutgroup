<?php
namespace Food\DishesBundle\Service;

use Food\DishesBundle\Entity\Place;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\AppBundle\Traits;
use Symfony\Component\Validator\Constraints\Null;

class RestaurantDuplicateService extends ContainerAware {
    use Traits\Service;

    public function __construct()
    {

    }

    public function DuplicateRestaurant($placeId)
    {
        $em = $this->container->get('doctrine')->getManager();

        $oldPlace = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find($placeId);


        $newPlace = $oldPlace;
        $newPlace->setActive(0);



        $em->persist($newPlace);
        $em->persist($newPlace);
        $em->flush();

        return $newPlace->getId();

    }


}