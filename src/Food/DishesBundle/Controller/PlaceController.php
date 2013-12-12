<?php

namespace Food\DishesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PlaceController extends Controller
{
    public function indexAction($id, $slug)
    {
        $place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find($id);
        return $this->render('FoodDishesBundle:Place:index.html.twig', array('place' => $place));
    }
}
