<?php

namespace Food\PlacesBundle\Controller;

use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class DefaultController extends Controller
{
    protected $cityTranslations = [
        'Vilnius' => 'places.in_vilnius',
        'Kaunas' => 'places.in_kaunas',
        'Klaipėda' => 'places.in_klaipeda',
        'Klaipeda' => 'places.in_klaipeda',
        'Šiauliai' => 'places.in_siauliai',
        'Siauliai' => 'places.in_siauliai',
        'Panevėžys' => 'places.in_panevezys',
        'Panevezys' => 'places.in_panevezys',
        'Alytus' => 'places.in_alytus',
        'Utena' => 'places.in_utena',
        'Plungė' => 'places.in_plunge',
        'Plunge' => 'places.in_plunge',
        'Riga' => 'places.in_riga',
        'Rīga' => 'places.in_riga'
    ];

    public function indexAction($recommended = false, $zaval = false)
    {
        if ($recommended) {
            $recommended = true;
        }
        if ($zaval) {
            $zaval = true;
        }

        $locData =  $this->get('food.googlegis')->getLocationFromSession();
        $placeService = $this->get('food.places');
        $availableCitiesSlugs = $this->container->getParameter('available_cities_slugs');

        if (!empty($locData['city']) && in_array(mb_strtolower($locData['city']), $availableCitiesSlugs)) {
            $city_url = $this->generateUrl('food_city_' . lcfirst($locData['city']), [], true);
        } else {
            $city_name = lcfirst(reset($availableCitiesSlugs));
            $city_url = $this->generateUrl('food_city_' . (!empty($city_name) ? $city_name : 'vilnius'), [], true);
        }

        return $this->render(
            'FoodPlacesBundle:Default:index.html.twig',
            array(
                'recommended' => $recommended,
                'zaval' => $zaval,
                'location' => $locData,
                'city_translations' => $this->cityTranslations,
                'default_city' => 'Vilnius',
                'userAllAddress' => $placeService->getCurrentUserAddresses(),
                'delivery_type_filter' => $this->container->get('session')->get('delivery_type', OrderService::$deliveryDeliver),
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
        $availableCitiesSlugs = $this->container->getParameter('available_cities_slugs');
        $availableCitiesSlugs = array_map("mb_strtolower", $availableCitiesSlugs);

        if (!empty($city) && in_array(mb_strtolower($city), $availableCitiesSlugs)) {
            $city_url = $this->generateUrl('food_city_' . lcfirst($city), [], true);
        } else {
            $city_name = lcfirst(reset($availableCitiesSlugs));
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
                'zaval' => false,
                'location' => $locData,
                'city_translations' => $this->cityTranslations,
                'userAllAddress' => $placeService->getCurrentUserAddresses(),
                'delivery_type_filter' => $this->container->get('session')->get('delivery_type', OrderService::$deliveryDeliver),
                'slug_filter' => $slug_filter,
                'city_url' => $city_url,
                'selected_kitchens_names' => $selectedKitchensNames,
            )
        );
    }

    public function listAction($recommended = false, $slug_filter = false, $zaval = false, Request $request)
    {
        if ($recommended) {
            $recommended = true;
        }
        if ($zaval) {
            $zaval = true;
        }

        $recommendedFromRequest = $request->get('recommended', null);
        if ($recommendedFromRequest !== null) {
            $recommended = (bool)$recommendedFromRequest;
        }

        if ($deliveryType = $request->get('delivery_type', false)) {
            switch($deliveryType) {
                // @TODO: delivery !== deliver
                case 'delivery':
                    $this->container->get('session')->set('delivery_type', OrderService::$deliveryDeliver);
                    break;
                case 'pickup':
                    $this->container->get('session')->set('delivery_type', OrderService::$deliveryPickup);
                    break;
            }
        }

        $places = $this->get('food.places')->getPlacesForList($recommended, $request, $slug_filter, $zaval);

        $locData =  $this->get('food.googlegis')->getLocationFromSession();

        return $this->render(
            'FoodPlacesBundle:Default:list.html.twig',
            array(
                'places' => $places,
                'recommended' => ($recommended ? 1:0),
                'location' => $locData,
                'location_show' => (empty($locData) ? false : true),
                'delivery_type_filter' => $this->container->get('session')->get('delivery_type', OrderService::$deliveryDeliver)
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
