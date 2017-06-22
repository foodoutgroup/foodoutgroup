<?php

namespace Food\PlacesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class WidgetController extends Controller
{

    public function recommendedAction()
    {
        $location = $this->get('food.location')->get();
        $city = null;
        if (!empty($location['city'])) {
            $city = $location['city'];
        }
        $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->getRecommendedForTitle($city);

        return $this->render('FoodPlacesBundle:Widget:recommended.html.twig', array('places' => $places, 'city' => $city));
    }

    public function bestOffersAction()
    {
        $cityService = $this->get('food.city_service');
        $location = $this->get('food.location')->get();

        $city = null;
        if (!empty($location['city'])) {
            $city = $location['city_id'];
        }

        return $this->render('FoodPlacesBundle:Widget:best_offers.html.twig', [
            'best_offers' =>  $cityService->getRandomBestOffers($city)
        ]);
    }

}
