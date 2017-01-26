<?php

namespace Api\V2Bundle\Controller;

use Api\BaseBundle\Common\JsonRequest;
use Api\BaseBundle\Helper\ResponseInterpreter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Food\ApiBundle\Exceptions\ApiException;

class OrderController extends Controller
{

    public function getAction($hash, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Orders:getOrderDetailsByHashAction Request: hash - ' . $hash, (array) $request);
        try {
            $order = $this->get('food.order')->getOrderByHash($hash);
            $response = $this->get('api.v2.order')->getOrderForResponseFull($order);
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

        return new ResponseInterpreter($request, $response);
    }

    public function createAction($hash, Request $request){

        $return = ['success' => false];
        try {
            $requestJson = new JsonRequest($request);
            $place = $this->get('api.v2.place')->getPlaceByHash($hash);
            $return['hash'] = $this->get('api.v2.order')->createOrderFromRequest($place, $requestJson);
            $return['success'] = true;
        } catch (\Api\BaseBundle\Exceptions\ApiException $e) {
            $return['message'] = "API: ".$e->getMessage();
        } catch (\Exception $e) {
            $return['message'] = "System: ".$e->getMessage().' '. $e->getLine().' - '. $e->getFile();
        }
        return new ResponseInterpreter($request, $return);

    }

    public function updateStatusAction($hash, $status, Request $request)
    {
        $return = ['success' => false, 'message' => ''];
        $startTime = microtime(true);
        $this->get('logger')->alert('Order:updateStatusAction Request: hash - ' . $hash, (array)$request);

        $status = $request->get("status");

        try {

            $status = str_replace("-", "_", $status);

            $order = $this->get('food.order')->getOrderByHash($hash);

            $orderService = $this->container->get('food.order');
            $orderService->setOrder($order);
            if ($orderService->isValidOrderStatusChange($order->getOrderStatus(), $status) && !in_array($status, ['picked'])) {

                switch ($status) {
                    case "canceled":
                        $return['success'] = true;
                        $orderService->statusCanceled('restaurant_api');
                        break;
                    case "accepted":
                        $orderService->statusAccepted('restaurant_api');
                        $return['success'] = true;
                        break;
                    case "finished":
                        $orderService->statusFinished('restaurant_api');
                        $return['success'] = true;
                        break;
                    case "completed":
                        $orderService->statusCompleted('restaurant_api');
                        $return['success'] = true;
                        break;
                    case "picked":
                        $orderService->getOrder()->setOrderPicked(true);
                        $return['success'] = true;
                        break;
                    case "delayed":
                        $orderService->statusDelayed('restaurant_api', 'delay reason: ' . $request->get('reason'));
                        $orderService->getOrder()->setDelayed(true);
                        if (!empty($request)) {
                            $orderService->getOrder()->setDelayReason($request->get('reason'));
                            $orderService->getOrder()->setDelayDuration($request->get('duration'));
                        }
                        $orderService->saveDelay();
                        $return['success'] = true;
                        break;
                    case "canceled_produced":
                        $orderService->statusCanceled_produced("restaurant_api");
                        $return['success'] = true;
                        break;
                    default:
                        $return['message'] = $status.' was not found';
                        break;
                }
            }

            $orderService->saveOrder();

        } catch (ApiException $e) {
            $this->get('logger')->error('Order:updateStatusAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Order:updateStatusAction Trace1:' . $e->getTraceAsString());
            $return['message'] = $e->getMessage();
            $return['success'] = false;
        } catch (\Exception $e) {
            $this->get('logger')->error('Order:updateStatusAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Order:updateStatusAction Trace2:' . $e->getTraceAsString());
            $return['message'] = $e->getMessage();
            $return['success'] = false;
        }

        $this->get('logger')->alert('Order:updateStatusAction Response:' . print_r($return, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');


        return new ResponseInterpreter($request, $return);
    }

    public function getStatusAction($hash, Request $request){

        $return = ['success' => false, 'order' => []];
        $startTime = microtime(true);
        $this->get('logger')->alert('Order:getStatusAction Request: hash - ' . $hash, (array)$request);
        try {
            $order = $this->get('food.order')->getOrderByHash($hash);
            if($order) {
               $return['success'] = true;
               $return['order'] = [
                   'hash' => $order->getOrderHash(),
                   'status' => $this->get('api.v2.order')->convertOrderStatus($order->getOrderStatus())
               ];
            }
        } catch (ApiException $e) {
            $this->get('logger')->error('Order:getStatusAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Order:getStatusAction Trace1:' . $e->getTraceAsString());
            $return['message'] = $e->getMessage();
            $return['success'] = false;
        } catch (\Exception $e) {
            $this->get('logger')->error('Order:getStatusAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Order:getStatusAction Trace2:' . $e->getTraceAsString());
            $return['message'] = $e->getMessage();
            $return['success'] = false;
        }

        $this->get('logger')->alert('Order:getStatusAction Response:' . print_r($return, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');


        return new ResponseInterpreter($request, $return);

    }

}
