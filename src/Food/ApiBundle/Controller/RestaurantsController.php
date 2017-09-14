<?php

namespace Food\ApiBundle\Controller;

use Food\ApiBundle\Exceptions\ApiException;
use Food\DishesBundle\Entity\Kitchen;
use Food\OrderBundle\Entity\Order;
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
        $this->get('logger')->alert('Restaurants:getRestaurantsAction Request:', (array)$request);
        $doctrine = $this->getDoctrine();
        try {
            /**
             * address,city,lat,lng,cuisines,keyword,offset,limit
             *
             */
            $pickUpOnly = false;
            $city = $request->get('city');
            $address = $request->get('address');

            $lat = $request->get('lat');
            $lng = $request->get('lng');

            $locationService = $this->get('food.location');
            $locationData = null;

            if ($address && $city) {
                $locationData = $locationService->findByAddress($address . ', ' . $city);
                $locationService->set($locationData);
            } elseif ($address) {
                $locationData = $locationService->findByAddress($address);
                $locationService->set($locationData);
            } elseif ($lat && $lng) {
                $locationData = $locationService->findByCords($lat, $lng);
                $locationService->set($locationData);
            }

            $keyword = $request->get('keyword', '');
            $delivery_type = $request->get('delivery', '');

            $filters = array(
                'keyword' => $keyword
            );

            if (!empty($delivery_type) && in_array($delivery_type, array('delivery', 'pickup','pedestrian'))) {
                $filters['delivery_type'] = $delivery_type;
            }

            $kitchenCollection = explode(", ", $request->get('cuisines', ''));
            if (empty($kitchenCollection) || (sizeof($kitchenCollection) == 1 && empty($kitchenCollection[0]))) {
                $kitchenCollection = array();
            }

            if($request->query->has('limit')) {
                $filters['limit'] = $request->query->has('limit');
            }

            if($request->query->has('offset')) {
                $filters['offset'] = $request->query->has('offset');
            }

            $placeCollection = [];

            if (!empty($locationData)) {
                $placeCollection = $doctrine->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
                    $kitchenCollection,
                    $filters,
                    $locationData,
                    $this->container
                );
            }



            $this->get('session')->set('filter', $filters);


            $response = [
                'restaurants' => [],
                '_meta' => [
                    'total' => sizeof($placeCollection),
                    'offset' => isset($filters['offset']) ? intval($filters['offset']) : 0,
                    'limit' => isset($filters['limit']) ? intval($filters['limit']) : 50
                ]
            ];
            if($delivery_type == 'pickup'){
                $pickUpOnly = true;
            }

            $placeCollection = $this->get('food.places')->placesPlacePointsWorkInformation($placeCollection);
            foreach ($placeCollection as $place) {
                $response['restaurants'][] = $this->get('food_api.api')
                    ->createRestaurantFromPlace($place['place'], $place['point'],$pickUpOnly)->data;
            }
        } catch (ApiException $e) {
            $this->get('logger')->error('Restaurants:getRestaurantsAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getRestaurantsAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Restaurants:getRestaurantsAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Restaurants:getRestaurantsAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened').''. $e->getMessage()],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Restaurants:getRestaurantsAction Response:' . json_encode($response));
        //$this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response, 200, array('Access-Control-Allow-Origin'=> '*'));
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
        $this->get('logger')->alert('Restaurants:getRestaurantsFilteredAction Request:', (array)$request);
        try {

            $city = $request->get('city');
            $address = $request->get('address');

            $lat = $request->get('lat');
            $lng = $request->get('lng');

            $locationService = $this->get('food.location');
            $locationData = null;

            if ($address && $city) {
                $locationData = $locationService->findByAddress($address . ', ' . $city);
                $locationService->set($locationData);
            } elseif ($address) {
                $locationData = $locationService->findByAddress($address);
                $locationService->set($locationData);
            } elseif ($lat && $lng) {
                $locationData = $locationService->findByCords($lat, $lng);
                $locationService->set($locationData);
            }

            $places = [];
            if (!empty($locationData)) {
                $places = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')
                    ->magicFindByKitchensIds([], [], $locationData, $this->container);

            }

            $cuisines = array();
            if (!empty($places)) {
                foreach ($places as $place) {

                    foreach ($place['place']->getKitchens() as $kit) {
                        /**
                         * @var $kit Kitchen
                         */
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

        $this->get('logger')->alert('Restaurants:getRestaurantsFilteredAction Response:' . json_encode($response));
        //$this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response, 200, array('Access-Control-Allow-Origin'=> '*'));
    }

    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function getRestaurantAction($id, Request $request)
    {

        $startTime = microtime(true);
        //$this->get('logger')->alert('Restaurants:getRestaurantAction Request: id - ' . $id, (array)$request);
        try {

            $city = $request->get('city');
            $address = $request->get('address');

            $lat = $request->get('lat');
            $lng = $request->get('lng');

            $locationService = $this->get('food.location');
            $locationData = null;

            if ($address && $city) {
                $locationData = $locationService->findByAddress($address . ', ' . $city);
                $locationService->set($locationData);
            } elseif ($address) {
                $locationData = $locationService->findByAddress($address);
                $locationService->set($locationData);
            } elseif ($lat && $lng) {
                $locationData = $locationService->findByCords($lat, $lng);
                $locationService->set($locationData);
            }

            $place = $this->get('doctrine')->getRepository('FoodDishesBundle:Place')->find(intval($id));
            $pointId = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->getPlacePointNear($place->getId(), $locationData, true);

            if (!empty($pointId)) {
                $placePoint = $this->getDoctrine()->getRepository('FoodDishesBundle:PlacePoint')->find($pointId);
                $restaurant = $this->get('food_api.api')->createRestaurantFromPlace($place, $placePoint,false, $locationData);
            } else {
                $pointId = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->getPlacePointNear($place->getId(), $locationData);
                if (!empty($pointId)) {
                    $placePoint = $this->getDoctrine()->getRepository('FoodDishesBundle:PlacePoint')->find($pointId);
                    $restaurant = $this->get('food_api.api')->createRestaurantFromPlace($place, $placePoint, false, $locationData);
                } else {
                    $restaurant = $this->get('food_api.api')->createRestaurantFromPlace($place, null, true, $locationData);
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

        //$this->get('logger')->alert('Restaurants:getRestaurantAction Response:' . print_r($response, true));
        //$this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
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
        //$this->get('logger')->alert('Restaurants:getMenuAction Request: id - ' . $id, (array)$request);
        try {
            $updated_at = $request->get('updated_at');
            $menuItems = $this->get('food_api.api')->createMenuByPlaceId($id, $updated_at, $request);
            $deletedItems = $this->get('food_api.api')->createDeletedByPlaceId($id, $updated_at, $menuItems);

            $response = array(
                'menu' => $menuItems,
                'deleted' => $deletedItems,
                '_meta' => array(
                    'total' => sizeof($menuItems),
                    'offset' => intval($request->query->get('offset', 0)),
                    'limit' => intval($request->query->get('limit', 50))
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

        //$this->get('logger')->alert('Restaurants:getMenuAction Response:' . print_r($response, true));
        //$this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
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
        //$this->get('logger')->alert('Restaurants:getMenuItemAction Request: placeId - ' . $placeId . ', menuItem - ' . $menuItem);
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

        //$this->get('logger')->alert('Restaurants:getMenuItemAction Response:' . print_r($response, true));
        //$this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
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
        //$this->get('logger')->alert('Restaurants:getMenuCategoriesAction Request: id - ' . $id);
        try {
            $response = [];
            $items = $this->getDoctrine()->getRepository('FoodDishesBundle:FoodCategory')->findBy(
                array(
                    'place' => (int)$id,
                    'active' => 1
                ),
                array('lineup' => 'DESC')
            );
            foreach ($items as $key => $item) {
                $response[] = array(
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'precedence' => ($key + 1)
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

        //$this->get('logger')->alert('Restaurants:getMenuCategoriesAction Response:' . print_r($response, true));
        //$this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response, 200, array('Access-Control-Allow-Origin'=> '*'));
    }
}
