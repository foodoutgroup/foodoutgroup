<?php

namespace Food\PlacesBundle\Service;

use Food\AppBundle\Entity\City;
use Food\AppBundle\Entity\Slug;
use Food\AppBundle\Service\SlugService;
use Food\DishesBundle\Entity\Kitchen;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Service\OrderService;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\AppBundle\Traits;
use Symfony\Component\HttpFoundation\Request;
use Food\AppBundle\Utils\Language;

class PedestrianService extends ContainerAware
{
    public function getPedestrianByCity($cityId){

        $return = false;

        $cityCheck = $this->container->get('doctrine')->getRepository('FoodAppBundle:City')->getActivePedestrianCityByLocation($cityId);
        $activeList = $this->container->get('food.app.utils.misc')->getParam('pedestrian_filter_show');

        if($cityCheck && $activeList){
            $return = true;
        }

        return $return;
    }
}
