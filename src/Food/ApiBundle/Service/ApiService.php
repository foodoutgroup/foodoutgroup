<?php
namespace Food\ApiBundle\Service;

use Food\ApiBundle\Common\MenuItem;
use Food\ApiBundle\Common\Restaurant;
use Symfony\Component\DependencyInjection\ContainerAware;

class ApiService extends ContainerAware
{
    public function createRestaurantFromPlace($place, $placePoint)
    {
        $restaurant = new Restaurant(null, $this->container);
        return $restaurant->loadFromEntity($place, $placePoint);
    }

    public function createMenuByPlaceId($placeId)
    {
        $place = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find((int)$placeId);
        if ($place) {
            $returner = array();
            foreach ($place->getDishes() as $dish) {
                $menuItem = new MenuItem(null, $this->container);
                $returner[] = $menuItem->loadFromEntity($dish);
            }
            return $returner;
        }
        return null;
    }

    public function createMenuItemByPlaceIdAndItemId($placeId, $menuItem)
    {
        $place = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find((int)$placeId);
        $dish = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Dish')->find((int)$menuItem);
        $menuItem = new MenuItem(null, $this->container);
        return $menuItem->loadFromEntity($dish, true);
    }
}