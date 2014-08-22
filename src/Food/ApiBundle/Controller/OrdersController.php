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
                        "state" => $this->get('food_api.order')->convertOrderStatus($order->getOrderStatus()),
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
