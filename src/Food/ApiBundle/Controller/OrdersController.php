<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Food\ApiBundle\Common\JsonRequest;
use Food\ApiBundle\Exceptions\ApiException;

class OrdersController extends Controller
{
    /**
     * @param $request
     */
    private function _theJudge(Request $request)
    {
        $miscUtils = $this->get('food.app.utils.misc');
        // Check if user is not banned
        $ip = $request->getClientIp();
        // Dude is banned - hit him
        if ($miscUtils->isIpBanned($ip)) {
            $this->get('logger')->warning('GOT BAN: '.$ip);
            die('{error: "Piktybinis", description: null}');
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrdersAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Orders:getOrdersAction Request:', (array) $request);
        $this->_theJudge($request);
        try {
            $requestJson = new JsonRequest($request);
            $response = $this->get('food_api.order')->getPendingOrders($request, $requestJson);
        }  catch (ApiException $e) {
            $this->get('logger')->error('Orders:getOrdersAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Orders:getOrdersAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Orders:getOrdersAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Orders:getOrdersAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Orders:getOrdersAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createOrderAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Orders:createOrderAction Request:', (array) $request);
        $this->_theJudge($request);
        try {
            $requestJson = new JsonRequest($request);
            $response = $this->get('food_api.order')->createOrder($request, $requestJson);
        }  catch (ApiException $e) {
            $this->get('logger')->error('Orders:createOrderAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Orders:createOrderAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Orders:createOrderAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Orders:createOrderAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Orders:createOrderAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createOrderPreAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Orders:createOrderPreAction Request:', (array) $request);
        $this->_theJudge($request);
        try {
            $requestJson = new JsonRequest($request);
            $response = $this->get('food_api.order')->createOrder($request, $requestJson, true);
        } catch (ApiException $e) {
            $this->get('logger')->error('Orders:createOrderPreAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Orders:createOrderPreAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Orders:createOrderPreAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Orders:createOrderPreAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Orders:createOrderPreAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderDetailsByHashAction($hash, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Orders:getOrderDetailsByHashAction Request: hash - ' . $hash, (array) $request);

        try {
            $order = $this->get('food.order')->getOrderByHash($hash);

            $response = $this->get('food_api.order')->getOrderForResponseFull($order);
        }  catch (ApiException $e) {
            $this->get('logger')->error('Orders:getOrderDetailsByHashAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Orders:getOrderDetailsByHashAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Orders:getOrderDetailsByHashAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Orders:getOrderDetailsByHashAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Orders:getOrderDetailsByHashAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    public function getOrdersByPlacepointHashAction($hash, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Orders:getOrdersByPlacepointHashAction Request: hash - ' . $hash, (array) $request);

        try {
            $orders = $this->get('food.order')->getOrdersByPlacepointHash($hash);
            $response = $this->get('food_api.order')->getOrdersForResponseFull($orders, $hash);
        }  catch (ApiException $e) {
            $this->get('logger')->error('Orders:getOrdersByPlacepointHashAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Orders:getOrdersByPlacepointHashAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Orders:getOrdersByPlacepointHashAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Orders:getOrdersByPlacepointHashAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Orders:getOrdersByPlacepointHashAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderDetailsAction($id, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Orders:getOrderDetailsAction Request: id - ' . $id, (array) $request);

        try {
            $this->_theJudge($request);

            $token = $request->headers->get('X-API-Authorization');
            $this->container->get('food_api.api')->loginByHash($token);
            $security = $this->container->get('security.context');

            $user = $security->getToken()->getUser();
            if (!$user) {
                throw new ApiException(
                    'Unauthorized',
                    401,
                    array(
                        'error' => 'Request requires a sesion_token',
                        'description' => $this->container->get('translator')->trans('api.orders.user_not_authorized')
                    )
                );
            }

            $order = $this->get('food.order')->getOrderById($id);

            if (!$order || $order->getUser()->getId() != $user->getId()) {
                throw new ApiException(
                    "Order not found",
                    404,
                    array(
                        'error' => 'Order not found',
                        'description' => null,
                    )
                );
            }

            $response = $this->get('food_api.order')->getOrderForResponse($order);
        }  catch (ApiException $e) {
            $this->get('logger')->error('Orders:getOrderDetailsAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Orders:getOrderDetailsAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Orders:getOrderDetailsAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Orders:getOrderDetailsAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Orders:getOrderDetailsAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function confirmOrderAction($id)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Orders:confirmOrderAction Request: id - ' . $id);
        mb_internal_encoding('utf-8');

        try {
            $order = $this->get('food.order')->getOrderById($id);

            if (!$order) {
                throw new ApiException(
                    "Order not found",
                    404,
                    array(
                        'error' => 'Order not found',
                        'description' => null,
                    )
                );
            }
            $this->get('food.order')->setOrder($order);
            $this->get('food.order')->statusNew('api');
            $this->get('food.order')->saveOrder();
            $this->get('food.order')->billOrder();

            // Jei naudotas kuponas, paziurim ar nereikia jo deaktyvuoti
            $this->container->get('food.order')->setOrder($order);
            $this->container->get('food.order')->deactivateCoupon();

            $response = $this->get('food_api.order')->getOrderForResponse($order);
        }  catch (ApiException $e) {
            $this->get('logger')->error('Orders:confirmOrderAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Orders:confirmOrderAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Orders:confirmOrderAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Orders:confirmOrderAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Orders:confirmOrderAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function getOrderStatusAction($id)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Orders:getOrderStatusAction Request: id - ' . $id);
        try {
            $order = $this->get('food.order')->getOrderById($id);

            if (!$order) {
                throw new ApiException(
                    "Order not found",
                    404,
                    array(
                        'error' => 'Order not found',
                        'description' => null,
                    )
                );
            }

            $message = $this->get('food_api.order')->getOrderStatusMessage($order);

            $response = [
                "order_id" => $order->getId(),
                "status" => array(
                    "title" => $this->get('food_api.order')->convertOrderStatus($order->getOrderStatus()),
                    // TODO Rodome nebe restorano, o dispeceriu nr
                    "info_number" => "+".$this->container->getParameter('dispatcher_contact_phone'),
//                        "info_number" => "+".$order->getPlacePoint()->getPhone(),
                    "message" => $message
                )
            ];
        }  catch (ApiException $e) {
            $this->get('logger')->error('Orders:getOrderStatusAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Orders:getOrderStatusAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Orders:getOrderStatusAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Orders:getOrderStatusAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Orders:getOrderStatusAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCouponAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Orders:getCouponAction Request: ', (array) $request);
        $this->_theJudge($request);
        try {
            $place = null;
            $now = date('Y-m-d H:i:s');
            $requestJson = new JsonRequest($request);
            $code = $requestJson->get('code');
            $orderService = $this->get('food.order');
            if ($basketId = $requestJson->get('basket_id')) {
                $basket = $this->getDoctrine()->getRepository('FoodApiBundle:ShoppingBasketRelation')->find($basketId);
                if (empty($basket)) {
                    throw new ApiException(
                        'Basket Not found',
                        404,
                        array(
                            'error' => 'Basket Not found',
                            'description' => $this->container->get('translator')->trans('api.orders.basket_does_not_exists')
                        )
                    );
                }
                $place = $basket->getPlaceId();
            }
            // uncomment after new APP release
//            else {
//                throw new ApiException(
//                    'Update APP',
//                    404,
//                    array(
//                        'error' => 'Update APP',
//                        'description' => $this->get('translator')->trans('api.orders.update_app')
//                    )
//                );
//            }

            if (!empty($code)) {
                $coupon = $orderService->getCouponByCode($code);
                if (empty($coupon)) {
                    throw new ApiException(
                        'Coupon Not found',
                        404,
                        array(
                            'error' => 'Coupon Not found',
                            'description' => $this->container->get('translator')->trans('api.orders.coupon_does_not_exists')
                        )
                    );
                }
                if (!$coupon->isAllowedForApi()) {
                    throw new ApiException(
                        'Coupon for web',
                        404,
                        array(
                            'error' => 'Coupon for web',
                            'description' => $this->container->get('translator')->trans('general.coupon.only_web')
                        )
                    );
                }
                if ($place) {
                    if (!$orderService->validateCouponForPlace($coupon, $place)
                        || $coupon->getOnlyNav() && !$place->getNavision()
                        || $coupon->getNoSelfDelivery() && $place->getSelfDelivery()) {
                        throw new ApiException(
                            'Coupon Wrong Place',
                            404,
                            array(
                                'error' => 'Coupon Wrong Place',
                                'description' => $this->get('translator')->trans('general.coupon.wrong_place')
                            )
                        );
                    }
                }
                // online payment coupons disallowed in app until online payments will be made
                if ($coupon->getOnlinePaymentsOnly()) {
                    throw new ApiException(
                        'Coupon Online Payments Only',
                        404,
                        array(
                            'error' => 'Coupon Online Payments Only',
                            'description' => $this->get('translator')->trans('general.coupon.only_web')
                        )
                    );
                }
                // Coupon is still valid Begin
                if ($coupon->getEnableValidateDate()) {
                    if ($coupon->getValidFrom()->format('Y-m-d H:i:s') > $now) {
                        throw new ApiException(
                            'Coupon Not Valid Yet',
                            404,
                            array(
                                'error' => 'Coupon Not Valid Yet',
                                'description' => $this->get('translator')->trans('api.orders.coupon_too_early')
                            )
                        );
                    }
                    if ($coupon->getValidTo()->format('Y-m-d H:i:s') < $now) {
                        throw new ApiException(
                            'Coupon Expired',
                            404,
                            array(
                                'error' => 'Coupon Expired',
                                'description' => $this->get('translator')->trans('api.orders.coupon_expired')
                            )
                        );
                    }
                }
                // Coupon is still valid End

                if ($coupon->getValidHourlyFrom() && $coupon->getValidHourlyFrom() > new \DateTime()) {
                    throw new ApiException(
                        'Coupon Not Valid Yet',
                        404,
                        array(
                            'error' => 'Coupon Not Valid Yet',
                            'description' => $this->get('translator')->trans('api.orders.coupon_too_early')
                        )
                    );
                }
                if ($coupon->getValidHourlyTo() && $coupon->getValidHourlyTo() < new \DateTime()) {
                    throw new ApiException(
                        'Coupon Expired',
                        404,
                        array(
                            'error' => 'Coupon Expired',
                            'description' => $this->get('translator')->trans('api.orders.coupon_expired')
                        )
                    );
                }

                $arr_places = array();
                $places = $coupon->getPlaces();
                if (!empty($places) && count($places) > 0) {
                    foreach ($places as $place) {
                        $arr_places[$place->getId()] = $place->getName();
                    }
                }

                $response = [
                    'id' => $coupon->getId(),
                    'name' => $coupon->getName(),
                    'code' => $coupon->getCode(),
                    'discount' => $coupon->getDiscount(),
                    'discount_sum' => $coupon->getDiscountSum() * 100,
                    'free_delivery' => $coupon->getFreeDelivery(),
                    'single_use' => $coupon->getSingleUse(),
                    'no_self_delivery' => $coupon->getNoSelfDelivery(), // Only for non self delivery restaurants
                    'enable_validate_date' => $coupon->getEnableValidateDate(),
                    'valid_from' => ($coupon->getValidFrom() != null ? $coupon->getValidFrom()->format('Y-m-d H:i:s') : null),
                    'valid_to' => ($coupon->getValidTo() != null ? $coupon->getValidTo()->format('Y-m-d H:i:s') : null),
                    'places' => $arr_places,
                ];
            } else {
                throw new ApiException(
                    'Coupon Code Is Empty',
                    404,
                    array(
                        'error' => 'Coupon Code Is Empty',
                        'description' => $this->get('translator')->trans('api.orders.coupon_empty')
                    )
                );
            }
        }  catch (ApiException $e) {
            $this->get('logger')->error('Orders:getOrderStatusAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Orders:getOrderStatusAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Orders:getOrderStatusAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Orders:getOrderStatusAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Orders:getOrderStatusAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * For debuging purpose only - log request data and action name for easy debug
     *
     * @param string $action
     * @param array|Request $params
     */
    protected function logActionParams($action, $params)
    {
        $logger = $this->get('logger');

        if ($params instanceof Request) {
            $params = $params->request->all();
        }

        $logger->alert('=============================== '.$action.' =====================================');
        $logger->alert('Request params:');
        $logger->alert(var_export($params, true));
        $logger->alert('=========================================================');
    }
}
