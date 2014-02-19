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

    public function listAction()
    {
        $kitchens = $this->getRequest()->get('kitchens');
        $filters = $this->getRequest()->get('filters');
        var_dump($filters);
        var_dump($kitchens);
        $kitchens = explode(",", $kitchens);
        $filters = explode(",", $filters);
        foreach ($kitchens as $kkey=> &$kitchen) {
            $kitchen = intval($kitchen);
        }
        foreach ($filters as $fkey=> &$filter) {
            $filter = trim($kitchen);
        }
        if (!empty($kitchens)) {
            $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->findByKitchensIds($kitchens, $filter);
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