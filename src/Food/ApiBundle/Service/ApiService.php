<?php
namespace Food\ApiBundle\Service;

use Food\ApiBundle\Common\Restaurant;
use Symfony\Component\DependencyInjection\ContainerAware;

class ApiService extends ContainerAware
{
    public function createRestaurantFromPlace($place, $placePoint)
    {
        $restaurant = new Restaurant(null, $this->container);
        return $restaurant->loadFromEntity($place, $placePoint);
    }
}