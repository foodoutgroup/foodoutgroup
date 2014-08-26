<?php

namespace Food\OrderBundle\Controller;

use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogisticsController extends Controller
{
    public function orderStatusAction(Request $request)
    {
        $logger = $this->get('logger');
        // TODO isimti sita bruda
        $logger->alert(
            sprintf('Kreipinys is logistikos i orderStatusAction su XML: %s', $request->getContent())
        );
        $logger->alert(
            sprintf('Requesto paramsai: %s', var_export($request->request->all(), true))
        );
        try {
            $logisticsService = $this->get('food.logistics');
            $orderService = $this->get('food.order');

            $statusData = $logisticsService->parseOrderStatusXml($request->getContent());

            foreach($statusData as $orderStatus) {
                $order = $orderService->getOrderById($orderStatus['order_id']);

                if ($order instanceof Order) {
                    $orderService->logOrder(
                        $order,
                        'logistics_api_status_update',
                        'Order status updated from logistics. Logstics status: '.$orderStatus['status']
                    );

                    if ($orderStatus['status'] == 'finished') {
                        $orderService->statusCompleted('LogisticsAPI');
                    } else if ($orderStatus['status'] == 'failed') {
                        $orderService->statusFailed('LogisticsAPI', $orderStatus['fail_reason']);
                    } else {
                        $logger->error(
                            sprintf('Order id: %d status change SKIPPED - Status unkown: %s', $orderStatus['order_id'], $orderStatus['status'])
                        );
                    }
                    $orderService->saveOrder();
                } else {
                    $logger->error(
                        sprintf('Order id: %d status change SKIPPED - Order does not exist', $orderStatus['order_id'])
                    );
                }
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
        $logger = $this->get('logger');
        // TODO isimti sita bruda
        $logger->alert(
            sprintf('Kreipinys is logistikos i driverAssignAction su XML: %s', $request->getContent())
        );
        $logger->alert(
            sprintf('Requesto paramsai: %s', var_export($request->request->all(), true))
        );
        try {
            $logisticsService = $this->get('food.logistics');
            $orderService = $this->get('food.order');

            $driverData = $logisticsService->parseDriverAssignXml($request->getContent());

            foreach ($driverData as $driver) {
                $order = $orderService->getOrderById($driver['order_id']);

                // Skip non existant and completed orders
                if ($order instanceof Order && $order->getOrderStatus() != OrderService::$status_completed) {
                    $logisticsService->assignDriver($driver['driver_id'], array($driver['order_id']));
                    $orderService->logOrder(
                        $order,
                        'logistics_api_driver_assign',
                        sprintf('Driver #%d assigned to order #%d from logitics', $driver['driver_id'], $driver['order_id'])
                    );
                } else {
                    // Log error
                    if (!$order) {
                        $reason = 'Order does not exist';
                    } else {
                        $reason = 'Order already completed';
                    }
                    $logger->error(
                        sprintf(
                            'Driver id: %d assign to order id: %d SKIPPED - %s',
                            $driver['driver_id'],
                            $driver['order_id']),
                            $reason
                    );
                }
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
