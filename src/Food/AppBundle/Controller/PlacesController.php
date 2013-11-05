<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PlacesController extends Controller
{
    public function indexAction()
    {
        return $this->forward('FoodAppBundle:Places:list');
    }

    public function listAction()
    {
        $repository = $this->getDoctrine()->getRepository('FoodDishesBundle:Place');
        $list = $repository->findAll();
        //$cart = $this->get('food.cart');
        return $this->render('FoodAppBundle:Places:list.html.twig', array('list' => $list));
    }

    public function statusAction()
    {
        /* TODO Doctrine update? */
        $repository = $this->getDoctrine()->getRepository('FoodDishesBundle:Place');
        $item = $repository->find($this->getRequest()->get('id'));
//        $item->status = ....??
        return 'crap?';
    }
}
