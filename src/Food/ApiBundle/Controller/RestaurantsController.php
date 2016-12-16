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
        $startTime = microtime(true);
        $this->get('logger')->alert('Restaurants:getRestaurantsAction Request:', (array) $request);
        try {
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

                    $this->get('food.googlegis')->groupData($address, $city);

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
        } catch (ApiException $e) {
            $this->get('logger')->error('Restaurants:getRestaurantsAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getRestaurantsAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Restaurants:getRestaurantsAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getRestaurantsAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Restaurants:getRestaurantsAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
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
        $startTime = microtime(true);
        $this->get('logger')->alert('Restaurants:getRestaurantsFilteredAction Request:', (array) $request);
        try {
            $address = $request->get('address');
            $city = $request->get('city');
            if (!empty($city)) {
                $city = str_replace("laipeda", "laipėda", $city);
            }
            $lat = $request->get('lat');
            $lng = $request->get('lng');

            $places = array();

            if (!empty($address)) {

                $this->get('food.googlegis')->groupData($address, $city);

                $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
                    array(),
                    array(),
                    false,
                    $this->get('food.googlegis')->getLocationFromSession(),
                    $this->container
                );
            } elseif (!empty($lat) && !empty($lng)) {
                $foundAddress = $this->get('food.googlegis')->findAddressByCoords($lat, $lng);
                if (isset($foundAddress['city'])) {
                    $this->get('food.googlegis')->setLocationToSession(
                        array(
                            'lat' => $lat,
                            'lng' => $lng,
                            'city' => $foundAddress['city']
                        )
                    );
                    $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
                        array(),
                        array(),
                        false,
                        $this->get('food.googlegis')->getLocationFromSession(),
                        $this->container
                    );
                }
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
            $response = array_values($cuisines);
        } catch (ApiException $e) {
            $this->get('logger')->error('Restaurants:getRestaurantsFilteredAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getRestaurantsFilteredAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Restaurants:getRestaurantsFilteredAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getRestaurantsFilteredAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Restaurants:getRestaurantsFilteredAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function getRestaurantAction($id, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Restaurants:getRestaurantAction Request: id - ' . $id, (array) $request);
        try {
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
                $locationInfo = $this->get('food.googlegis')->groupData($address, $city);
                if (isset($locationInfo['city'])) {
                    $searchCrit['city'] = $locationInfo['city'];
                }
                if (isset($locationInfo['lat'])) {
                    $searchCrit['lat'] = $locationInfo['lat'];
                }
                if (isset($locationInfo['lng'])) {
                    $searchCrit['lng'] = $locationInfo['lng'];
                }
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
                    $restaurant = $this->get('food_api.api')->createRestaurantFromPlace($place, null, true, $this->get('food.googlegis')->getLocationFromSession());
                }
            }
            $response = $restaurant->data;
        } catch (ApiException $e) {
            $this->get('logger')->error('Restaurants:getRestaurantAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getRestaurantAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Restaurants:getRestaurantAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getRestaurantAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Restaurants:getRestaurantAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        $realResponse = new JsonResponse($response);
        $responseHeaders = $realResponse->headers;
        $responseHeaders->set('Access-Control-Allow-Headers', 'origin, content-type, accept');
        $responseHeaders->set('Access-Control-Allow-Origin', '*');
        $responseHeaders->set('Access-Control-Allow-Methods', 'GET');

        return $realResponse;
    }

    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function getMenuAction($id, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Restaurants:getMenuAction Request: id - ' . $id, (array) $request);
        try {
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

        } catch (ApiException $e) {
            $this->get('logger')->error('Restaurants:getMenuAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getMenuAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Restaurants:getMenuAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getMenuAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Restaurants:getMenuAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
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
        $startTime = microtime(true);
        $this->get('logger')->alert('Restaurants:getMenuItemAction Request: placeId - ' . $placeId . ', menuItem - ' . $menuItem);
        try {
            $response = $this->get('food_api.api')->createMenuItemByPlaceIdAndItemId($placeId, $menuItem);
        } catch (ApiException $e) {
            $this->get('logger')->error('Restaurants:getMenuItemAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getMenuItemAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Restaurants:getMenuItemAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getMenuItemAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Restaurants:getMenuItemAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
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
        $startTime = microtime(true);
        $this->get('logger')->alert('Restaurants:getMenuCategoriesAction Request: id - ' . $id);
        try {
            $response = [];
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
        } catch (ApiException $e) {
            $this->get('logger')->error('Restaurants:getMenuCategoriesAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getMenuCategoriesAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Restaurants:getMenuCategoriesAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getMenuCategoriesAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Restaurants:getMenuCategoriesAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }
}
