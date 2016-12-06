<?php

namespace Food\PlacesBundle\Controller;

use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class DefaultController extends Controller
{
       public function indexAction($recommended = false, $zaval = false)
    {

        $locData =  $this->get('food.location')->getLocationFromSession();
        $placeService = $this->get('food.places');
        $cityService = $this->get('food.city_service');


        if(!$city = $cityService->getCityById($locData['city'])) {
            if(!$city = $cityService->getDefaultCity()){
                throw new NotFoundHttpException('City was not found');
            }
        }

        return $this->render(
            'FoodPlacesBundle:Default:index.html.twig', [
                'recommended' => $recommended,
                'zaval' => $zaval,
                'location' => $locData,
                'default_city' => 'Vilnius',
                'userAllAddress' => $placeService->getCurrentUserAddresses(),
                'delivery_type_filter' => $this->container->get('session')->get('delivery_type', OrderService::$deliveryDeliver),
                'slug_filter' => null,
                'selected_kitchens_names' => [],
                'city' => $city,
                'cityUrl' => $this->generateUrl('food_slug', ['slug' => $city->getSlug()])
            ]
        );
    }

    public function indexCityAction($id, $slug, $params = [])
    {

        $request = $this->get('request');

        $cityService = $this->get('food.city_service');
        if(!$city = $cityService->getCityById($id)) {
            if(!$city = $cityService->getDefaultCity()){
                throw new NotFoundHttpException('City was not found');
            }
        }

        $this->get('food.googlegis')->setCityOnlyToSession($city->getTitle());
        $locData =  $this->get('food.googlegis')->getLocationFromSession();
        $placeService = $this->get('food.places');
        $selectedKitchensNames = $placeService->getKitchensFromSlug($params, $request, true);
        $current_url = $request->getUri();


        $selectedKitchensIds = $placeService->getKitchensFromSlug($params, $request);
        if (!empty($selectedKitchensIds)) {
            $kitchen = $this->getDoctrine()->getRepository('FoodDishesBundle:Kitchen')->find($selectedKitchensIds[0]);
            $metaTitle = $kitchen->getMetaTitle();
            $metaDescription = $kitchen->getMetaDescription();
        } else {
            $metaTitle = '';
            $metaDescription = '';
        }

        return $this->render(
            'FoodPlacesBundle:Default:city.html.twig',
            array(
                'recommended' => false,
                'zaval' => false,
                'location' => $locData,
                'userAllAddress' => $placeService->getCurrentUserAddresses(),
                'delivery_type_filter' => $this->container->get('session')->get('delivery_type', OrderService::$deliveryDeliver),
                'slug_filter' => implode("/", $params),
                'selected_kitchens_names' => $selectedKitchensNames,
                'current_url' => $current_url,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'city' => $city,
                'cityUrl' => $this->generateUrl('food_slug', ['slug' => $city->getSlug()]),
            )
        );
    }

    public function listAction($recommended = false, $slug_filter = false, $zaval = false, Request $request)
    {
        if ($recommendedFromRequest = $request->get('recommended', null) !== null) {
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
        return $this->render('FoodPlacesBundle:Default:best_offers.html.twig', [
            'best_offers' => $this->getBestOffers(5)
        ]);
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
