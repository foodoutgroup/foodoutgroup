<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Pirminis\Maybe;

class MssqlTestController extends Controller
{
    public function insertOrderAction()
    {
        // services
        $nav = $this->get('food.nav');

        $order = $this->getDoctrine()
                      ->getManager()
                      ->getRepository('FoodOrderBundle:Order')
                      ->find(911);
        $data = $nav->getOrderDataForNav($order);
        $result = $nav->insertOrder($data);

        var_dump($result);
        die('xxx');
    }
}
