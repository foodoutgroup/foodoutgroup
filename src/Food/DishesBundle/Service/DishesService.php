<?php
namespace Food\DishesBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Food\AppBundle\Traits;

class DishesService extends ContainerAware {
    use Traits\Service;

    public function __construct()
    {

    }

    /**
     * @param int $categoryId
     * @return array|\Food\DishesBundle\Entity\Dish[]
     */
    public function getActiveDishesByCategory($categoryId)
    {
        return $this->em()->getRepository('FoodDishesBundle:Dish')->getCategoryActiveDishes($categoryId);
    }

    public function getSmallestDishPrice($dishId)
    {
        $prices = $this->em()->getRepository('FoodDishesBundle:Dish')->getSmallestPrice($dishId);
        if (!empty($prices) && is_array($prices)) {
            return $prices[0]['price'];
        } elseif (!empty($prices)) {
            return $prices['price'];
        }
        return null;
    }

    public function getLargestDishPrice($dishId)
    {
        $prices = $this->em()->getRepository('FoodDishesBundle:Dish')->getLargestPrice($dishId);
        if (!empty($prices) && is_array($prices)) {
            return $prices[0]['price'];
        } elseif (!empty($prices)) {
            return $prices['price'];
        }
        return null;
    }
}