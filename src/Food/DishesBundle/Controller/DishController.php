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
        return $this->render(
            'FoodDishesBundle:Dish:dish.html.twig',
            array(
                'dish' => $this->getDoctrine()->getRepository('FoodDishesBundle:Dish')->find((int)$dish),
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
     * @param $dish
     * @param $cartId
     * @return Response
     */
    public function removeDishInCartAction($dish, $cartId)
    {
        $dishEnt = $this->getDoctrine()->getRepository('FoodDishesBundle:Dish')->find((int)$dish);
        $cartEnt = $this->get('food.cart')->getCartDish(intval($dish), intval($cartId));
        return $this->render(
            'FoodDishesBundle:Dish:dish_remove.html.twig',
            array(
                'dish' => $dishEnt,
                'cart' => $cartEnt
            )
        );
    }
}