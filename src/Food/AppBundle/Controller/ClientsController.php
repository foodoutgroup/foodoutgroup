<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ClientsController extends Controller
{
    public function indexAction()
    {
        return $this->forward('FoodAppBundle:Clients:list');
    }

    public function listAction()
    {
        $repository = $this->getDoctrine()->getRepository('FoodDishesBundle:Place');
        $list = $repository->findAll();
        //$cart = $this->get('food.cart');
        return $this->render('FoodAppBundle:Clients:list.html.twig', array('list' => $list));
    }
}
