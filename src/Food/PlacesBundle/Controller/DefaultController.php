<?php

namespace Food\PlacesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class DefaultController extends Controller
{
    protected $cityTranslations = [
        'Vilnius' => 'places.in_vilnius',
        'Kaunas' => 'places.in_kaunas',
        'Klaipėda' => 'places.in_klaipeda',
        'Klaipeda' => 'places.in_klaipeda',
        'Riga' => 'places.in_riga',
        'Rīga' => 'places.in_riga'
    ];

    public function indexAction($recommended = false)
    {
        if ($recommended) {
            $recommended = true;
        }
        $locData =  $this->get('food.googlegis')->getLocationFromSession();
        $placeService = $this->get('food.places');

        if (!empty($locData['city'])) {
            $city_url = $this->generateUrl('food_city_' . str_replace(array("ī", "ė"), array("i", "e"), lcfirst($locData['city'])), [], true);
        } else {
            $availableCities = $this->container->getParameter('available_cities');
            $city_name = str_replace(array('ė', 'ī'), array('e', 'i'), lcfirst(reset($availableCities)));
            $city_url = $this->generateUrl('food_city_' . (!empty($city_name) ? $city_name : 'vilnius'), [], true);
        }

        return $this->render(
            'FoodPlacesBundle:Default:index.html.twig',
            array(
                'recommended' => $recommended,
                'location' => $locData,
                'city_translations' => $this->cityTranslations,
                'default_city' => 'Vilnius',
                'userAllAddress' => $placeService->getCurrentUserAddresses(),
                'delivery_type_filer' => 'delivery_and_pickup', // TODO saving or other cool feature
                'slug_filter' => null,
                'city_url' => $city_url,
                'selected_kitchens_names' => array(),
            )
        );
    }

    public function indexCityAction($city, $slug_filter = false, Request $request)
    {
        $city = ucfirst($city);
        $city = str_replace(array("#", "-",";","'",'"',":", ".", ",", "/", "\\"), "", $city);

        if (!empty($city)) {
            $city_url = $this->generateUrl('food_city_' . lcfirst($city), [], true);
        } else {
            $availableCities = $this->container->getParameter('available_cities');
            $city_name = str_replace(array('ė', 'ī'), array('e', 'i'), lcfirst(reset($availableCities)));
            $city = ucfirst($city_name);
            $city_url = $this->generateUrl('food_city_' . (!empty($city_name) ? $city_name : 'vilnius'), [], true);
        }

        $this->get('food.googlegis')->setCityOnlyToSession($city);
        $locData =  $this->get('food.googlegis')->getLocationFromSession();
        $placeService = $this->get('food.places');
        $selectedKitchensNames = $placeService->getKitchensFromSlug($slug_filter, $request, true);

        return $this->render(
            'FoodPlacesBundle:Default:index.html.twig',
            array(
                'recommended' => false,
                'location' => $locData,
                'city_translations' => $this->cityTranslations,
                'userAllAddress' => $placeService->getCurrentUserAddresses(),
                'delivery_type_filer' => 'delivery_and_pickup', // TODO saving or other cool feature
                'slug_filter' => $slug_filter,
                'city_url' => $city_url,
                'selected_kitchens_names' => $selectedKitchensNames,
            )
        );
    }

    public function listAction($recommended = false, $slug_filter = false, Request $request)
    {
        if ($recommended) {
            $recommended = true;
        }
        $recommendedFromRequest = $request->get('recommended', null);
        if ($recommendedFromRequest !== null) {
            $recommended = (bool)$recommendedFromRequest;
        }

        $listType = $request->get('delivery_type', 'delivery');

        $places = $this->get('food.places')->getPlacesForList($recommended, $request, $slug_filter);
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
