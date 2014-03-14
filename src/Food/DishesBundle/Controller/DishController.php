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
                'dish' => $this->getDoctrine()->getRepository('FoodDishesBundle:Dish')->find((int)$dish)
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
    public function editDishInCart($dish, $cartId)
    {
        return $this->render(
            'FoodDishesBundle:Dish:dish.html.twig',
            array(
                'dish' => $this->getDoctrine()->getRepository('FoodDishesBundle:Dish')->find((int)$dish),
                'cart' => $this->get('food.cart')->getCartDish(intval($dish), intval($cartId))
            )
        );
    }
}