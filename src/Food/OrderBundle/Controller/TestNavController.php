<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TestNavController extends Controller
{
    public function indexAction()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', true);

        $nav = $this->get('food.nav');

        $order = $this->getDoctrine()
                      ->getManager()
                      ->getRepository('FoodOrderBundle:Order')
                      ->find(640);
        $data = $nav->getOrderDataForNav($order);
        $result = $nav->insertOrder($data);
        var_dump($result);
        die('xxx');
    }
}
