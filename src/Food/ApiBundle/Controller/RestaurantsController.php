<?php

namespace Food\ApiBundle\Controller;

use Food\ApiBundle\Common\Restaurant;
use Food\ApiBundle\Common\MenuItem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RestaurantsController extends Controller
{
    public function getRestaurantsAction(Request $request)
    {
        /**
         * address,city,lat,lng,cuisines,keyword,offset,limit
         *
         */


        $address = $request->get('address');
        $city = $request->get('city');
        $lat = $request->get('lat');
        $lng = $request->get('lng');

        $kitchens = explode(", ", $request->get('cuisines', ''));
        if (empty($kitchens) || (sizeof($kitchens) == 1 && empty($kitchens[0]))) {
            $kitchens = array();
        }
        if (!empty($address)) {

            $location = $this->get('food.googlegis')->getPlaceData($address.', '.$city);
            $locationInfo = $this->get('food.googlegis')->groupData($location, $address, $city);

            $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
                $kitchens,
                array(),
                false,
                $this->get('food.googlegis')->getLocationFromSession()
            );
        } elseif (!empty($lat) && !empty($lng)) {
            $data = $this->get('food.googlegis')->findAddressByCoords($lat, $lng);
            $this->get('food.googlegis')->setLocationToSession(
                array(
                    'city' => $data['city'],
                    'lat' => $lat,
                    'lng' => $lng
                )
            );
            $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
                $kitchens,
                array(),
                false,
                $this->get('food.googlegis')->getLocationFromSession()
            );
        } else {
            $places = array();
        }

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

    /**
     * @return JsonResponse
     *
     * @todo Countingas pagal objektus kurie netoli yra :D
     */
    public function getRestaurantsFilteredAction(Request $request)
    {
        $address = $request->get('address');
        $city = $request->get('city');
        $lat = $request->get('lat');
        $lng = $request->get('lng');

        if (!empty($address)) {

            $location = $this->get('food.googlegis')->getPlaceData($address.', '.$city);
            $locationInfo = $this->get('food.googlegis')->groupData($location, $address, $city);

            $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
                array(),
                array(),
                false,
                $this->get('food.googlegis')->getLocationFromSession()
            );
        } elseif (!empty($lat) && !empty($lng)) {
            $this->get('food.googlegis')->setLocationToSession(
                array(
                    'lat' => $lat,
                    'lng' => $lng
                )
            );
            $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
                array(),
                array(),
                false,
                $this->get('food.googlegis')->getLocationFromSession()
            );
        } else {
            $places = array();
        }

        $cuisines = array();
        if (!empty($places)) {
            foreach ($places as $place) {
                foreach ($place['place']->getKitchens() as $kit) {
                    if (empty($cuisines[$kit->getId()])) {
                        $cuisines[$kit->getId()] = array(
                            'id' => $kit->getId(),
                            'name' => $kit->getName(),
                            'count' => 1
                        );
                    } else {
                        $cuisines[$kit->getId()]['count']++;
                    }
                }
            }
        }
        $cuisines = array_values($cuisines);
        return new JsonResponse($cuisines);
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
