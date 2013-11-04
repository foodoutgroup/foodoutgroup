<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class KitchensController extends Controller
{
    public function indexAction()
    {
        return $this->forward('FoodAppBundle:Kitchens:list');
    }

    public function listAction()
    {
        $repository = $this->getDoctrine()->getRepository('FoodDishesBundle:Kitchen');
        $list = $repository->findAll();
        return $this->render('FoodAppBundle:Kitchens:list.html.twig', array('list' => $list));
    }
}
