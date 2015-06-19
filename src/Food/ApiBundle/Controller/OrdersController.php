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
    private function _theJudge($request)
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
        @mail("paulius@foodout.lt", "FOO LOGS PRE", print_r($request->getContent(), true), "FROM: test@foodout.lt");
        try {
            $requestJson = new JsonRequest($request);
            return new JsonResponse($this->get('food_api.order')->createOrder($request, $requestJson, true));
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
            $this->get('food.order')->informPlace();

            return new JsonResponse($this->get('food_api.order')->getOrderForResponse($order));
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        }  catch (\Exception $e) {
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
