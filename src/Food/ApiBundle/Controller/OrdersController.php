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
    public function getOrdersAction(Request $request)
    {
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
        try {
            $requestJson = new JsonRequest($request);
            return new JsonResponse($this->get('food_api.order')->createOrder($request, $requestJson));
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        }/** catch (\Exception $e) {
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
 */
    }

    public function getOrderDetailsAction($id)
    {
        try {
            return new JsonResponse(
                $this->get('food_api.order')->getOrderForResponse(
                    $this->get('food.order')->getOrderById($id)
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

    public function confirmOrderAction($id)
    {

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

            $message = '';

            if ($order->getDelayed()) {
                $message = $this->get('translator')->trans(
                    'mobile.order_status.order_delayed',
                    array('%delayTime%' => $order->getDelayDuration())
                );
            }

            return new JsonResponse(
                array(
                    "order_id" => $order->getId(),
                    "status" => array(
                        "state" => $order->getOrderStatus(),
                        "phone" => "+".$order->getPlacePoint()->getPhone(),
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
