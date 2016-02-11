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

    public function getSmallestDishPublicPrice($dishId)
    {
        $prices = $this->em()->getRepository('FoodDishesBundle:Dish')->getSmallestPublicPrice($dishId);
        if (!empty($prices) && is_array($prices)) {
            return $prices[0]['publicPrice'];
        } elseif (!empty($prices)) {
            return $prices['publicPrice'];
        }
        return null;
    }

    public function getLargestDishPublicPrice($dishId)
    {
        $prices = $this->em()->getRepository('FoodDishesBundle:Dish')->getLargestPublicPrice($dishId);
        if (!empty($prices) && is_array($prices)) {
            return $prices[0]['publicPrice'];
        } elseif (!empty($prices)) {
            return $prices['publicPrice'];
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

    public function countDiscountSizes($dishId)
    {
        $countIt = $this->em()->getRepository('FoodDishesBundle:Dish')->getCountOfDiscountSizes($dishId);
        if (!empty($countIt) && is_array($countIt)) {
            return $countIt[0]['cnt'];
        } elseif (!empty($countIt)) {
            return $countIt['cnt'];
        }
        return null;
    }

    /**
     * @param $dishId
     * @param bool $returnAll
     * @return mixed
     */
    public function getOneOrAllDishDiscountPrice($dishId, $returnAll = false)
    {
        $query = "
            SELECT ds.discount_price AS discount, ds.price, du.short_name
            FROM dish_size ds, dish_unit du
            WHERE ds.unit_id = du.id
            AND ds.deleted_at IS NULL
            AND ds.discount_price > 0
            AND ds.dish_id = " . intval($dishId) . "
            ORDER BY (ds.discount_price/ds.price) DESC
        ";
        $stmt = $this->em()->getConnection()->prepare($query);
        $stmt->execute();
        if ($returnAll) {
            return $stmt->fetchAll();
        }
        return $stmt->fetch();
    }

    public function getDiscountString($dishId)
    {
        $data = $this->getOneOrAllDishDiscountPrice($dishId);
        if (!empty($data)) {
            if (!empty($data['short_name'])) {
                $proc = round(100 - (($data['discount'] / $data['price']) * 100));
                if (!empty($proc) && $proc > 1) {
                    return "-".$proc."% ".$data['short_name'];
                } else {
                    return "";
                }
            }
        }
        return "";
    }

    public function  hasDiscountPrice($dishId)
    {
        $hasAnyDiscountPrice = $this->em()->getRepository('FoodDishesBundle:Dish')->hasDiscountPrice($dishId);
        return $hasAnyDiscountPrice;
    }
}