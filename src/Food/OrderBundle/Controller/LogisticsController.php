<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogisticsController extends Controller
{
    public function orderStatusAction(Request $request)
    {
        // TODO isimti sita bruda
        $this->get('logger')->alert(
            sprintf('Kreipinys is logistikos i orderStatusAction su XML: %s', $request->getContent())
        );
        $this->get('logger')->alert(
            sprintf('Requesto paramsai: %s', var_export($request->request->all(), true))
        );
        try {
            $logisticsService = $this->get('food.logistics');
            $orderService = $this->get('food.order');

            $statusData = $logisticsService->parseOrderStatusXml($request->getContent());

            $orderService->getOrderById($statusData['order_id']);
            $orderService->logOrder(
                $orderService->getOrder(),
                'logistics_api_status_update',
                'Order status updated from logistics. Logstics status: '.$statusData['status']
            );

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
        // TODO isimti sita bruda
        $this->get('logger')->alert(
            sprintf('Kreipinys is logistikos i driverAssignAction su XML: %s', $request->getContent())
        );
        $this->get('logger')->alert(
            sprintf('Requesto paramsai: %s', var_export($request->request->all(), true))
        );
        try {
            $logisticsService = $this->get('food.logistics');
            $orderService = $this->get('food.order');

            $driverData = $logisticsService->parseDriverAssignXml($request->getContent());

            foreach ($driverData as $driver) {
                $logisticsService->assignDriver($driver['driver_id'], array($driver['order_id']));
                $orderService->logOrder(
                    $orderService->getOrderById($driver['order_id']),
                    'logistics_api_driver_assign',
                    sprintf('Driver #%d assigned to order #%d from logitics', $driver['driver_id'], $driver['order_id'])
                );
            }

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
