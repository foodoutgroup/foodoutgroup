<?php

namespace Food\PlacesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class DefaultController extends Controller
{
    protected $cityTranslations = [
        'Vilnius' => 'places.in_vilnius',
        'Kaunas' => 'places.in_kaunas',
        'Klaipėda' => 'places.in_klaipeda'
    ];

    public function indexAction($recommended = false)
    {
        if ($recommended) {
            $recommended = true;
        }
        $locData =  $this->get('food.googlegis')->getLocationFromSession();
        $placeService = $this->get('food.places');

        return $this->render(
            'FoodPlacesBundle:Default:index.html.twig',
            array(
                'recommended' => $recommended,
                'location' => $locData,
                'city_translations' => $this->cityTranslations,
                'default_city' => 'Vilnius',
                'userAllAddress' => $placeService->getCurrentUserAddresses(),
                'delivery_type_filer' => 'delivery_and_pickup', // TODO saving or other cool feature
            )
        );
    }

    public function indexCityAction($city)
    {
        $city = ucfirst($city);
        $city = str_replace(array("#", "-",";","'",'"',":", ".", ",", "/", "\\"), "", $city);
        $this->get('food.googlegis')->setCityOnlyToSession($city);
        $locData =  $this->get('food.googlegis')->getLocationFromSession();
        $placeService = $this->get('food.places');

        return $this->render(
            'FoodPlacesBundle:Default:index.html.twig',
            array(
                'recommended' => false,
                'location' => $locData,
                'city_translations' => $this->cityTranslations,
                'userAllAddress' => $placeService->getCurrentUserAddresses(),
                'delivery_type_filer' => 'delivery_and_pickup', // TODO saving or other cool feature
            )
        );
    }

    public function listAction($recommended = false, Request $request)
    {
        if ($recommended) {
            $recommended = true;
        }
        $recommendedFromRequest = $request->get('recommended', null);
        if ($recommendedFromRequest !== null) {
            $recommended = (bool)$recommendedFromRequest;
        }
        $listType = $request->get('delivery_type', 'delivery');

        $places = $this->get('food.places')->getPlacesForList($recommended, $request);
        $locData =  $this->get('food.googlegis')->getLocationFromSession();

        return $this->render(
            'FoodPlacesBundle:Default:list.html.twig',
            array(
                'places' => $places,
                'recommended' => ($recommended ? 1:0),
                'location' => $locData,
                'location_show' => (empty($locData) ? false : true),
                'list_type' => $listType
            )
        );
    }

    public function recommendedAction()
    {
        $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->getRecommendedForTitle();
        return $this->render('FoodPlacesBundle:Default:recommended.html.twig', array('places' => $places));
    }

    public function bestOffersAction()
    {
        $view = 'FoodPlacesBundle:Default:best_offers.html.twig';
        $options = [
            'best_offers' => $this->getBestOffers(5)
        ];

        return $this->render($view, $options);
    }

    public function changeLocationAction()
    {
        return $this->render('FoodPlacesBundle:Default:change_location.html.twig');
    }

    /**
     * @param integer $amount
     * @return array
     */
    private function getBestOffers($amount)
    {
        $items = $this->get('doctrine.orm.entity_manager')
                      ->getRepository('FoodPlacesBundle:BestOffer')
                      ->getRandomBestOffers($amount);

        return $items;
    }
}
