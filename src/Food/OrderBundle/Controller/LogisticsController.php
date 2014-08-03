<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogisticsController extends Controller
{
    public function orderStatusAction(Request $request)
    {
        try {
            $logisticsService = $this->get('food.logistics');
            $orderService = $this->get('food.order');

            $statusData = $logisticsService->parseOrderStatusXml($request->getContent());

            $orderService->getOrderById($statusData['order_id']);

            if ($statusData['status'] == 'finished') {
                $orderService->statusCompleted('LogisticsAPI');
            } else if ($statusData['status'] == 'failed') {
                $orderService->statusFailed('LogisticsAPI', $statusData['FailReason']);
            }

            return new Response('OK', 200);
        } catch (\Exception $e) {
            $this->get('logger')->error(
                sprintf(
                    'Error in logistics status action: %s. | Received XML: %s',
                    $e->getMessage(),
                    $request->getContent()
                )
            );

            return new Response('Error: '.$e->getMessage(), 500);
        }
    }

    public function driverAssignAction(Request $request)
    {
        try {
            $logisticsService = $this->get('food.logistics');

            $driverData = $logisticsService->parseDriverAssignXml($request->getContent());

            $logisticsService->assignDriver($driverData['driver_id'], array($driverData['order_id']));

            return new Response('OK', 200);
        } catch (\Exception $e) {
            $this->get('logger')->error(
                sprintf(
                    'Error in logistics driver assign action: %s. | Received XML: %s',
                    $e->getMessage(),
                    $request->getContent()
                )
            );

            return new Response('Error: '.$e->getMessage(), 500);
        }
    }
}
