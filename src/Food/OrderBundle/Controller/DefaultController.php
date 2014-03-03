<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Acl\Exception\Exception;

class DefaultController extends Controller
{
    /**
     * @Route("/hello/{name}")
     * @Template()
     */
    public function indexAction($name)
    {
        return array('name' => $name);
    }

    public function mobileAction($hash)
    {
        /**
         * @todo DFQ mantai per toArray struktura pas tave tam Orders?????
         */
        $order = $this->get('food.order')->getOrderByHash($hash);
        return $this->render('FoodOrderBundle:Default:mobile.html.twig', array('order' => $order));
    }
}
