<?php

namespace Food\AppBundle\Controller;



use Doctrine\Common\Inflector\Inflector;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class TestController extends Controller
{
    public function indexAction()
    {
        $dishEm = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Dish');
        $dishOp = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:DishOption');
        $cart = $this->get('food.cart');
        $cart->addDish(
            $dishEm->find(1),
            1,
            array(
                $dishOp->find(1),
                $dishOp->find(2),
                $dishOp->find(3),
                $dishOp->find(4),
                $dishOp->find(5)
            )
        );

        return new Response('Uber');

    }
}