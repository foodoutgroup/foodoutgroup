<?php

namespace Food\DishesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

class DishController extends Controller
{
    /**
     * Disho langas kad prideti i cart
     *
     * @param $dish
     * @return Response
     */
    public function getDishAction($dish)
    {
        $dishEnt = $this->getDoctrine()->getRepository('FoodDishesBundle:Dish')->find((int)$dish);
        $sizeCount = sizeof($dishEnt->getSizes());
        $selSize = 1;
        if ($sizeCount == 3) {
            $selSize = 2;
        } elseif ($sizeCount == 4) {
            $selSize = 3;
        }
        return $this->render(
            'FoodDishesBundle:Dish:dish.html.twig',
            array(
                'dish' => $dishEnt,
                'selectedSize' => $selSize,
                'cart' => null
            )
        );
    }

    /**
     * Disho editas carte.
     *
     * @param $dish
     * @param $cartId
     * @return Response
     *
     * @todo - tikrinti issue :) Yra sukurta. Pajungti ir sutvarkyti
     */
    public function editDishInCartAction($dish, $cartId)
    {
        $dishEnt = $this->getDoctrine()->getRepository('FoodDishesBundle:Dish')->find((int)$dish);
        $cartEnt = $this->get('food.cart')->getCartDish(intval($dish), intval($cartId));
        return $this->render(
            'FoodDishesBundle:Dish:dish.html.twig',
            array(
                'dish' => $dishEnt,
                'cart' => $cartEnt
            )
        );
    }

    /**
     * Disho editas carte.
     *
     * @param int $dish
     * @param int $cartId
     * @param int $inCart
     * @return Response
     */
    public function removeDishInCartAction($dish, $cartId, $inCart)
    {
        $dishEnt = $this->getDoctrine()->getRepository('FoodDishesBundle:Dish')->find((int)$dish);
        $cartEnt = $this->get('food.cart')->getCartDish(intval($dish), intval($cartId));
        return $this->render(
            'FoodDishesBundle:Dish:dish_remove.html.twig',
            array(
                'dish' => $dishEnt,
                'cart' => $cartEnt,
                'inCart' => $inCart,
            )
        );
    }
}