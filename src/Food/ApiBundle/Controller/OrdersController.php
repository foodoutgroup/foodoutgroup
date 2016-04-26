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
            @mail("paulius@foodout.lt", "GOT BAN", $ip, "FROM: test@foodout.lt");
            die('{error: "Piktybinis", description: null}');
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrdersAction(Request $request)
    {
        $this->_theJudge($request);
        try {
            $requestJson = new JsonRequest($request);
            return new JsonResponse($this->get('food_api.order')->getPendingOrders($request, $requestJson));
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $errBody = $e->getMessage();
            $errBody.= "\n\n\n";
            $errBody.= $e->getTraceAsString();
            @mail("paulius@foodout.lt", "GET ORDERS ERROR ".date("Y-m-d H:i:s"), $errBody, "FROM: info@foodout.lt");
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createOrderAction(Request $request)
    {
        $this->logActionParams('createOrder action', $request);
        $this->_theJudge($request);
        mail("paulius@foodout.lt", "FOO LOGS", print_r($request->getContent(), true), "FROM: test@foodout.lt");
        try {
            $requestJson = new JsonRequest($request);
            return new JsonResponse($this->get('food_api.order')->createOrder($request, $requestJson));
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $errBody = $e->getMessage();
            $errBody.= "\n\n\n";
            $errBody.= $e->getTraceAsString();
            @mail("paulius@foodout.lt", "CREATE ORDER ERROR ".date("Y-m-d H:i:s"), $errBody, "FROM: info@foodout.lt");
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createOrderPreAction(Request $request)
    {
        $this->logActionParams('createOrderPre action', $request);
        $this->_theJudge($request);
        @mail("paulius@foodout.lt", "FOO LOGS PRE ".date("Y-m-d H:i:s"), print_r($request->getContent(), true), "FROM: test@foodout.lt");
        try {
            $requestJson = new JsonRequest($request);
            return new JsonResponse($this->get('food_api.order')->createOrder($request, $requestJson, true));
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $errBody = $e->getMessage();
            $errBody.= "\n\n\n";
            $errBody.= "POST\n";
            $errBody.= print_r($_POST, true);
            $errBody.= "\n\n\n";
            $errBody.= "GET\n";
            $errBody.= print_r($_GET, true);
            $errBody.= "\n\n\n";
            $errBody.= "REQUEST BODY\n";
            $errBody.= print_r(file_get_contents('php://input'), true);

            $errBody.= "\n\n\n";
            $errBody.= $e->getTraceAsString();
            @mail("paulius@foodout.lt", "CREATE ORDER PRE ERROR ".date("Y-m-d H:i:s"), $errBody, "FROM: info@foodout.lt");
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderDetailsAction($id, Request $request)
    {
        $this->logActionParams('getOrderDetails action', $request);
        $this->_theJudge($request);
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

            return new JsonResponse($this->get('food_api.order')->getOrderForResponse($order));
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $errBody = $e->getMessage();
            $errBody.= "\n\n\n";
            $errBody.= $e->getTraceAsString();
            @mail("paulius@foodout.lt", "GET ORDER DETAILS ERROR ".date("Y-m-d H:i:s"), $errBody, "FROM: info@foodout.lt");

            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function confirmOrderAction($id)
    {
        $this->logActionParams('confirmOrder action', $id);
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

            return new JsonResponse($this->get('food_api.order')->getOrderForResponse($order));
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        }  catch (\Exception $e) {
            $errBody = $e->getMessage();
            $errBody.= "\n\n\n";
            $errBody.= $e->getTraceAsString();
            @mail("paulius@foodout.lt", "CONFIRM ORDER ERROR ".date("Y-m-d H:i:s"), $errBody, "FROM: info@foodout.lt");

            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function getOrderStatusAction($id)
    {
        $this->logActionParams('getOrderStatus action', $id);
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

            return new JsonResponse(
                array(
                    "order_id" => $order->getId(),
                    "status" => array(
                        "title" => $this->get('food_api.order')->convertOrderStatus($order->getOrderStatus()),
                        // TODO Rodome nebe restorano, o dispeceriu nr
                        "info_number" => "+".$this->container->getParameter('dispatcher_contact_phone'),
//                        "info_number" => "+".$order->getPlacePoint()->getPhone(),
                        "message" => $message
                    )
                )
            );
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $errBody = $e->getMessage();
            $errBody.= "\n\n\n";
            $errBody.= $e->getTraceAsString();
            @mail("paulius@foodout.lt", "GET ORDER STATUS ERROR ".date("Y-m-d H:i:s"), $errBody, "FROM: info@foodout.lt");

            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCouponAction(Request $request)
    {
        $this->_theJudge($request);
        try {
            $coupon = null;
            $now = date('Y-m-d H:i:s');
            $requestJson = new JsonRequest($request);
            $code = $requestJson->get('code');
            $this->logActionParams('getCoupon action', $code);

            if (!empty($code)) {
                $coupon = $this->get('food.order')->getCouponByCode($code);
                if (!empty($coupon)) {
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
                    if (!$coupon->isAllowedForApi()) {
                        throw new ApiException(
                            'Coupon for web',
                            404,
                            array(
                                'error' => 'Coupon for web',
                                'description' => $this->get('translator')->trans('general.coupon.only_web')
                            )
                        );
                    }
                    // Coupon is still valid End

                    $arr_places = array();
                    $places = $coupon->getPlaces();
                    if (!empty($places) && count($places) > 0) {
                        foreach ($places as $place) {
                            $arr_places[$place->getId()] = $place->getName();
                        }
                    }

                    $response = array(
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
                    );
                    return new JsonResponse($response);
                } else {
                    throw new ApiException(
                        'Coupon Not found',
                        404,
                        array(
                            'error' => 'Coupon Not found',
                            'description' => $this->get('translator')->trans('api.orders.coupon_does_not_exists')
                        )
                    );
                }
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
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
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
