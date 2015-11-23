<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RestaurantsController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getRestaurantsAction(Request $request)
    {
        /**
         * address,city,lat,lng,cuisines,keyword,offset,limit
         *
         */
        $address = $request->get('address');
        $city = $request->get('city');
        if (!empty($city)) {
            $city = str_replace("laipeda", "laipėda", $city);
        }
        $lat = $request->get('lat');
        $lng = $request->get('lng');
        $keyword = $request->get('keyword', '');
        $delivery_type = $request->get('delivery', '');

        $filters = array(
            'keyword' => $keyword
        );

        if (!empty($delivery_type) && in_array($delivery_type, array('delivery', 'pickup'))) {
            $filters['delivery_type'] = $delivery_type;
        }

        $kitchens = explode(", ", $request->get('cuisines', ''));
        if (empty($kitchens) || (sizeof($kitchens) == 1 && empty($kitchens[0]))) {
            $kitchens = array();
        }
        if (!empty($address)) {

            // TODO Pauliau, istrink sita gabala, kai isspresi GIS'a
            $availableCities = $this->container->getParameter('available_cities');
            $availableCities = array_map("mb_strtolower", $availableCities);
            if (!in_array(mb_strtolower($city), $availableCities)) {
                $places = array();
            } else {
                // TODO HACK pabaiga :)

                $location = $this->get('food.googlegis')->getPlaceData($address.', '.$city);

                $this->get('food.googlegis')->groupData($location, $address, $city);

                $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
                    $kitchens,
                    $filters,
                    false,
                    $this->get('food.googlegis')->getLocationFromSession(),
                    $this->container
                );
            }
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
                $filters,
                false,
                $this->get('food.googlegis')->getLocationFromSession(),
                $this->container
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

        $places = $this->get('food.places')->placesPlacePointsWorkInformation($places);
        foreach ($places as $place) {
            $restaurant = $this->get('food_api.api')->createRestaurantFromPlace(
                $place['place'],
                $place['point'],
                false,
                $this->get('food.googlegis')->getLocationFromSession(),
                $delivery_type
            );
            $response['restaurants'][] = $restaurant->data;
        }

        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @todo Countingas pagal objektus kurie netoli yra :D
     */
    public function getRestaurantsFilteredAction(Request $request)
    {
        $address = $request->get('address');
        $city = $request->get('city');
        if (!empty($city)) {
            $city = str_replace("laipeda", "laipėda", $city);
        }
        $lat = $request->get('lat');
        $lng = $request->get('lng');

        if (!empty($address)) {

            $location = $this->get('food.googlegis')->getPlaceData($address.', '.$city);
            $locationInfo = $this->get('food.googlegis')->groupData($location, $address, $city);

            $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
                array(),
                array(),
                false,
                $this->get('food.googlegis')->getLocationFromSession(),
                $this->container
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
                $this->get('food.googlegis')->getLocationFromSession(),
                $this->container
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

    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function getRestaurantAction($id, Request $request)
    {
        $city = $request->get('city');
        if (!empty($city)) {
            $city = str_replace("laipeda", "laipėda", $city);
        }
        $address = $request->get('address');
        $lat = $request->get('lat');
        $lng = $request->get('lng');
        $searchCrit = array(
            'city' => null,
            'lat' => null,
            'lng' => null
        );
        if (!empty($city) && !empty($address)) {
            $placeData = $this->get('food.googlegis')->getPlaceData($address.",", $city);
            $locationInfo = $this->get('food.googlegis')->groupData($placeData, $address, $city);
            $searchCrit = array(
                'city' => $locationInfo['city'],
                'lat' => $locationInfo['lat'],
                'lng' => $locationInfo['lng']
            );
        } elseif (!empty($lat) && !empty($lng)) {
            $data = $this->get('food.googlegis')->findAddressByCoords($lat, $lng);
            $searchCrit = array(
                'city' => $data['city'],
                'lat' => $lat,
                'lng' => $lng
            );
        }

        $place = $this->get('doctrine')->getRepository('FoodDishesBundle:Place')->find(intval($id));
        $pointId = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->getPlacePointNear(
            $place->getId(),
            $searchCrit,
            true
        );

        if (!empty($pointId)) {
            $placePoint = $this->getDoctrine()->getRepository('FoodDishesBundle:PlacePoint')->find($pointId);
            $restaurant = $this->get('food_api.api')->createRestaurantFromPlace(
                $place,
                $placePoint,
                false,
                $this->get('food.googlegis')->getLocationFromSession()
            );
        } else {
            $pointId = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->getPlacePointNear(
                $place->getId(),
                $searchCrit
            );
            if (!empty($pointId)) {
                $placePoint = $this->getDoctrine()->getRepository('FoodDishesBundle:PlacePoint')->find($pointId);
                $restaurant = $this->get('food_api.api')->createRestaurantFromPlace(
                    $place,
                    $placePoint,
                    false,
                    $this->get('food.googlegis')->getLocationFromSession()
                );
            } else {
                $restaurant = $this->get('food_api.api')->createRestaurantFromPlace($place, null, false, $this->get('food.googlegis')->getLocationFromSession());
            }
        }

        return new JsonResponse($restaurant->data);
    }

    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function getMenuAction($id, Request $request)
    {
        $updated_at = $request->get('updated_at');
        $menuItems = $this->get('food_api.api')->createMenuByPlaceId($id, $updated_at);
        $deletedItems = $this->get('food_api.api')->createDeletedByPlaceId($id, $updated_at, $menuItems);

        $response = array(
            'menu' => $menuItems,
            'deleted' => $deletedItems,
            '_meta' => array(
                'total' => sizeof($menuItems),
                'offset' => 0,
                'limit' => 50
            )
        );
        $resp = new JsonResponse($response);
        $resp->setMaxAge(1);
        $resp->setSharedMaxAge(1);
        $date = new \DateTime();
        $resp->setLastModified($date);
        return $resp;
    }

    /**
     * @param int $placeId
     * @param int $menuItem
     * @return JsonResponse
     */
    public function getMenuItemAction($placeId, $menuItem)
    {
        $response = $this->get('food_api.api')->createMenuItemByPlaceIdAndItemId($placeId, $menuItem);
        $resp = new JsonResponse($response);
        $resp->setMaxAge(1);
        $resp->setSharedMaxAge(1);
        $date = new \DateTime();
        $resp->setLastModified($date);
        return $resp;
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
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
