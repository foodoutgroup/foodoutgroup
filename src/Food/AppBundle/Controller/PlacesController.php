<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PlacesController extends Controller
{
    /**
     * @todo check ar reikalingas
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->forward('FoodAppBundle:Places:list');
    }

    /**
     * @todo check ar reikalingas
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction()
    {
        $repository = $this->getDoctrine()->getRepository('FoodDishesBundle:Place');
        $list = $repository->findAll();
        return $this->render('FoodAppBundle:Places:list.html.twig', array('list' => $list));
    }
}
