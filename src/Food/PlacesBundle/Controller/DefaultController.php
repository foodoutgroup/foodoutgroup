<?php

namespace Food\PlacesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;



class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('FoodPlacesBundle:Default:index.html.twig');
    }

    public function listAction($kitchens = "")
    {
        $kitchens = explode(",", $kitchens);
        foreach ($kitchens as $kkey=> &$kitchen) {
            $kitchen = intval($kitchen);
        }
        if (!empty($kitchens)) {
            $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->findByKitchensIds(array(1,2,3,4,5,6,7));
        } else {
            $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->findAll();
        }
        return $this->render('FoodPlacesBundle:Default:list.html.twig', array('places' => $places));
    }


    public function citiesAction()
    {
        $cities = $this->get('food.places')->getAvailableCities();
        return new Response(json_encode($cities));
    }
}