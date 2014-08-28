<?php

namespace Food\PlacesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;



class DefaultController extends Controller
{
    public function indexAction($recommended = false)
    {
        if ($recommended) {
            $recommended = true;
        }
        $locData =  $this->get('food.googlegis')->getLocationFromSession();
        return $this->render(
            'FoodPlacesBundle:Default:index.html.twig',
            array(
                'recommended' => $recommended,
                'location' => $locData
            )
        );
    }

    public function listAction($recommended = false, Request $request)
    {
        if ($recommended) {
            $recommended = true;
        }

        $kitchens = $request->get('kitchens', "");
        $filters = $request->get('filters');
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

        $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
            $kitchens,
            $filter,
            $recommended,
            $this->get('food.googlegis')->getLocationFromSession()
        );
        $this->get('food.places')->saveRelationPlaceToPoint($places);
        $places = $this->get('food.places')->placesPlacePointsWorkInformation($places);



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

    public function changeLocationAction()
    {
        return $this->render('FoodPlacesBundle:Default:change_location.html.twig');
    }
}
