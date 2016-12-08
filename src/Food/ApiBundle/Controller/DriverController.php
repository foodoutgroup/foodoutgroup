<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DriverController extends Controller
{
    /**
     * User information by token
     *
     * @param string $token
     * @param Request $request
     * @return JsonResponse
     */
    public function meAction($token, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Driver:meAction Request: token - ' . $token, (array) $request);
        try {
            $driver = $this->get('food_api.api')->getDriverByToken($token);
            $translator = $this->get('translator');

            $response = array(
                'id' => $driver->getId(),
                'type' => $driver->getType(),
                'extId' => $driver->getExtId(),
                'phone' => $driver->getPhone(),
                'name' => $driver->getName(),
                'city' => $driver->getCity(),
                'dispatchPhone' => $translator->trans('general.top_contact.phone'),
                //'bannerUrl' => '',
                //'timezone' => '2'
            );
        } catch (ApiException $e) {
            $this->get('logger')->error('Driver:meAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Driver:meAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Driver:meAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Driver:meAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Driver:meAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * User information by Id
     *
     * @param integer $id
     * @param Request $request
     * @return JsonResponse
     */
    public function meIdAction($id, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Driver:meIdAction Request: id - ' . $id, (array) $request);
        try {
            $driver = $this->get('food_api.api')->getDriverById($id);
            $translator = $this->get('translator');

            $response = [
                'id' => $driver->getId(),
                'type' => $driver->getType(),
                'extId' => $driver->getExtId(),
                'phone' => $driver->getPhone(),
                'name' => $driver->getName(),
                'city' => $driver->getCity(),
                'dispatchPhone' => $translator->trans('general.top_contact.phone'),
                //'bannerUrl' => '',
                //'timezone' => '2'
            ];
        } catch (ApiException $e) {
            $this->get('logger')->error('Driver:meIdAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Driver:meIdAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Driver:meIdAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Driver:meIdAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Driver:meIdAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * Assign driver to order
     *
     * @param integer $driverId
     * @param integer $orderId
     * @param Request $request
     * @return JsonResponse
     */
    public function assignToOrderAction($driverId, $orderId, Request $request)
    {

        $startTime = microtime(true);
        $this->get('logger')->alert('Driver:assignToOrderAction Request: driverId - ' . $driverId . ' / orderId - ' . $orderId, (array) $request);
        try {
            $apiService = $this->get('food_api.api');
            $orderService = $this->get('food.order');
            $driver = $apiService->getDriverById($driverId);
            $order = $apiService->getOrderById($orderId);
            $orderService->setOrder($order);
            $orderService->setAutoAssignedDriver($driver);
            $response = ['success' => true];
        } catch (ApiException $e) {
            $this->get('logger')->error('Driver:assignToOrderAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Driver:assignToOrderAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Driver:assignToOrderAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Driver:assignToOrderAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Driver:assignToOrderAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * Driver picked order from restaurant
     *
     * @param integer $orderId
     * @param Request $request
     * @return JsonResponse
     */
    public function pickedAction($orderId, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Driver:assignToOrderAction Request: orderId - ' . $orderId, (array) $request);
        try {
            $apiService = $this->get('food_api.api');
            $order = $apiService->getOrderById($orderId);
            $orderService = $this->get('food.order');
            $orderService->setOrder($order);
            $orderService->logDeliveryEvent($orderService->getOrder(), 'order_pickedup');
            $orderService->getOrder()->setOrderPicked(true);
            $orderService->saveOrder();
            $orderService->sendOrderPickedMessage();
            $response = ['success' => true];
        } catch (ApiException $e) {
            $this->get('logger')->error('Driver:assignToOrderAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Driver:assignToOrderAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Driver:assignToOrderAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Driver:assignToOrderAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Driver:assignToOrderAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * Driver completed order
     *
     * @param integer $orderId
     * @param Request $request
     * @return JsonResponse
     */
    public function completedAction($orderId, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Driver:assignToOrderAction Request: orderId - ' . $orderId, (array) $request);
        try {
            $apiService = $this->get('food_api.api');
            $order = $apiService->getOrderById($orderId);
            $orderService = $this->get('food.order');
            $orderService->setOrder($order);
            $orderService->statusCompleted('driver_api');
            $orderService->saveOrder();
            $response = ['success' => true];
        } catch (ApiException $e) {
            $this->get('logger')->error('Driver:assignToOrderAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Driver:assignToOrderAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Driver:assignToOrderAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Driver:assignToOrderAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Driver:assignToOrderAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * Driver working state
     *
     * @param integer $id
     * @param integer $state
     * @param Request $request
     * @return JsonResponse
     */
    public function workingStateAction($id, $state, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Driver:assignToOrderAction Request: state - ' . $state, (array) $request);
        try {
            $driver = $this->get('food_api.api')->getDriverById($id);
            $driver->setActive($state);
            $em = $this->get('doctrine')->getManager();
            $em->persist($driver);
            $em->flush();
            $response = ['success' => true];
        } catch (ApiException $e) {
            $this->get('logger')->error('Driver:assignToOrderAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Driver:assignToOrderAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Driver:assignToOrderAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Driver:assignToOrderAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Driver:assignToOrderAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }
}
