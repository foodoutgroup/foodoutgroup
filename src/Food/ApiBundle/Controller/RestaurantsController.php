<?php

namespace Food\ApiBundle\Controller;

use Food\ApiBundle\Common\Restaurant;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RestaurantsController extends Controller
{
    public function getRestaurantsAction()
    {
        $address = "Vivulskio 21";
        $city = "Vilnius";
        $location = $this->get('food.googlegis')->getPlaceData($address.', '.$city);
        $locationInfo = $this->get('food.googlegis')->groupData($location, $address);

        $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
            array(),
            array(),
            false,
            $this->get('food.googlegis')->getLocationFromSession()
        );

        $response = array(
            'restaurants' => array(),
            '_meta' => array(
                'total' => sizeof($places),
                'offset' => 0,
                'limit' => 50
            )
        );
        foreach ($places as $place) {
            $restaurant = $this->get('food_api.api')->createRestaurantFromPlace($place['place'], $place['point']);
            $response['restaurants'][] = $restaurant->data;
        }
        return new JsonResponse($response);
    }

    public function getRestaurantsFilteredAction()
    {

    }

    public function getRestaurantAction($id)
    {

    }

    public function getMenuAction($id)
    {
        $menuItems = $this->get('food_api.api')->createMenuByPlaceId($id);

        $response = array(
            'menu' => $menuItems,
            '_meta' => array(
                'total' => sizeof($menuItems),
                'offset' => 0,
                'limit' => 50
            )
        );
        return new JsonResponse($response);
    }
}
