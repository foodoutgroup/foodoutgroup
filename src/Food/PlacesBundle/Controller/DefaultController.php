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

    public function listAction($recommended = false)
    {
        $kitchens = $this->getRequest()->get('kitchens', "");
        $filters = $this->getRequest()->get('filters');
        if (empty($kitchens)) {
            $kitchens = array();
        } else {
            $kitchens = explode(",", $kitchens);
        }

        $filters = explode(",", $filters);
        foreach ($kitchens as $kkey=> &$kitchen) {
            $kitchen = intval($kitchen);
        }
        foreach ($filters as $fkey=> &$filter) {
            $filter = trim($filter);
        }

        $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds($kitchens, $filter);
        $this->get('food.places')->saveRelationPlaceToPoint($places);


        $locData =  $this->get('food.googlegis')->getLocationFromSession();
        return $this->render(
            'FoodPlacesBundle:Default:list.html.twig',
            array(
                'places' => $places,
                'recommended' => ($recommended ? 1:0),
                'location' => $locData,
                'location_show' => (empty($locData) ? false : true)

            )
        );
    }


    public function citiesAction()
    {
        $cities = $this->get('food.places')->getAvailableCities();
        return new Response(json_encode($cities));
    }

    public function recommendedAction()
    {
        $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->getRecommendedForTitle();
        return $this->render('FoodPlacesBundle:Default:recommended.html.twig', array('places' => $places));
    }
}