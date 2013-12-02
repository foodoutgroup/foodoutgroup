<?php

namespace Food\DishesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class PlaceController extends Controller
{
    public function indexAction($id, $slug)
    {
        $place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find($id);
        return $this->render('FoodDishesBundle:Place:index.html.twig', array('place' => $place));
    }
}
