<?php

namespace Food\ApiBundle\Controller;

use Food\ApiBundle\Common\Restaurant;
use Food\ApiBundle\Common\MenuItem;
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
        $place = $this->get('doctrine')->getRepository('FoodDishesBundle:Place')->find(intval($id));
        $restaurant = $this->get('food_api.api')->createRestaurantFromPlace($place, null);
        return new JsonResponse($restaurant->data);
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

    public function getMenuItemAction($placeId, $menuItem)
    {
        $response = $this->get('food_api.api')->createMenuItemByPlaceIdAndItemId($placeId, $menuItem);
        return new JsonResponse($response);
    }

    public function getMenuCategoriesAction($id)
    {
        $response = array();
        $items = $this->getDoctrine()->getRepository('FoodDishesBundle:FoodCategory')->findBy(
            array(
                'place' => (int)$id,
                'active' => 1
            ),
            array('lineup'=> 'DESC')
        );
        foreach ($items as $key=>$item) {
            $response[] = array(
                'id' => $item->getId(),
                'name' => $item->getName(),
                'precedence' => ($key+1)
            );
        }
        return new JsonResponse($response);
    }
}
