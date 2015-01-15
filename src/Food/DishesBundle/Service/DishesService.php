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

    public function getSmallestDishDiscountPrice($dishId)
    {
        $prices = $this->em()->getRepository('FoodDishesBundle:Dish')->getSmallestDiscountPrice($dishId);
        if (!empty($prices) && is_array($prices)) {
            return $prices[0]['discountPrice'];
        } elseif (!empty($prices)) {
            return $prices['discountPrice'];
        }
        return null;
    }

    public function getLargestDishDiscountPrice($dishId)
    {
        $prices = $this->em()->getRepository('FoodDishesBundle:Dish')->getLargestDiscountPrice($dishId);
        if (!empty($prices) && is_array($prices)) {
            return $prices[0]['discountPrice'];
        } elseif (!empty($prices)) {
            return $prices['discountPrice'];
        }
        return null;
    }

    public function getOneDishDiscountPrice($dishId)
    {
        $query = "SELECT discount_price as discount, price FROM dish_size WHERE deleted_at IS NULL AND discount_price > 0 AND dish_id=".intval($dishId)." ORDER BY (discount_price/price) DESC ";
        $stmt = $this->em()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function  hasDiscountPrice($dishId)
    {
        $hasAnyDiscountPrice = $this->em()->getRepository('FoodDishesBundle:Dish')->hasDiscountPrice($dishId);
        return $hasAnyDiscountPrice;
    }
}