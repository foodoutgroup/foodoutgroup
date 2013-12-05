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

        // EPIC DISH ADD
        /*
        $cart->addDish(
            $dishEm->find(1),
            1,
            array(
                array('quantity' => 1, 'option' => $dishOp->find(1)),
                array('quantity' => 1, 'option' => $dishOp->find(2)),
                array('quantity' => 1, 'option' => $dishOp->find(3)),
                array('quantity' => 1, 'option' => $dishOp->find(4)),
                array('quantity' => 1, 'option' => $dishOp->find(5))
            )
        );
        */


        //$cart->removeOption($dishEm->find(1), $dishOp->find(3));
        $cart->removeDish($dishEm->find(1));

        return new Response('Uber');

    }
}