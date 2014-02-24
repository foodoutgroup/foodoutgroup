<?php

namespace Food\DishesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

class DishController extends Controller
{
    public function getDishAction($dish)
    {
        $dishObj = $this->getDoctrine()->getRepository('FoodDishesBundle:Dish')->find((int)$dish);
        return $this->render(
            'FoodDishesBundle:Dish:dish.html.twig',
            array(
                'dish' => $dishObj
            )
        );
    }
}