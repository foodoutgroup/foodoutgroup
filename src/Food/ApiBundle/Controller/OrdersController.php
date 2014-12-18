<?php

namespace Food\ApiBundle\Controller;

use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Food\ApiBundle\Common\JsonRequest;
use Food\ApiBundle\Exceptions\ApiException;

class OrdersController extends Controller
{
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

    public function createOrderAction(Request $request)
    {
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

    public function createOrderPreAction(Request $request)
    {
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

    public function getOrderDetailsAction($id, Request $request)
    {
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

    public function confirmOrderAction($id)
    {
        mb_internal_encoding('utf-8');
        mail("paulius@foodout.lt", "Some freaky start ".$id." 1", "This is the end", "FROM: info@foodout.lt");

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
            $this->get('food.order')->informPlace();
            mail("paulius@foodout.lt", "Some freaky start ".$id." After inform", "This is the end", "FROM: info@foodout.lt");
            return new JsonResponse($this->get('food_api.order')->getOrderForResponse($order));
        }  catch (ApiException $e) {
            mail("paulius@foodout.lt", "Some freaky start ".$id." API", "This is the end", "FROM: info@foodout.lt");
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        }  catch (\Exception $e) {
            mail("paulius@foodout.lt", "Some freaky start ".$id." GENERAL", "This is the end", "FROM: info@foodout.lt");
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    public function getOrderStatusAction($id)
    {
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
                        "info_number" => "+".$order->getPlacePoint()->getPhone(),
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
}
