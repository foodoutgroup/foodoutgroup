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
        try {
            $logisticsService = $this->get('food.logistics');
            $orderService = $this->get('food.order');

            $statusData = $logisticsService->parseOrderStatusXml($request->getContent());

            foreach($statusData as $orderStatus) {
                $order = $orderService->getOrderById($orderStatus['order_id']);

                if ($orderStatus['status'] == 'finished') {
                    $newOrderStatus = $orderService::$status_completed;
                } else if ($orderStatus['status'] == 'failed') {
                    $newOrderStatus = $orderService::$status_failed;
                } else {
                    $logger->warning(
                        sprintf('Order id: %d status change SKIPPED - Status unkown: %s', $orderStatus['order_id'], $orderStatus['status'])
                    );

                    continue;
                }

                if ($order instanceof Order) {
                    if ($orderService->isValidOrderStatusChange($order->getOrderStatus(), $newOrderStatus)
                        && $order->getOrderStatus() != $newOrderStatus) {
                        $orderService->logOrder(
                            $order,
                            'logistics_api_status_update',
                            'Order status updated from logistics. Logstics status: '.$orderStatus['status']
                        );

                        if ($newOrderStatus == $orderService::$status_completed && $order->getOrderStatus() != OrderService::$status_canceled) {
                            $orderService->statusCompleted('LogisticsAPI');
                        } else if ($newOrderStatus == $orderService::$status_failed) {
                            $orderService->statusFailed('LogisticsAPI', $orderStatus['fail_reason']);
                        }
                        $orderService->saveOrder();
                    } else {
                        $logger->warning(
                            sprintf(
                                'Order id: %d status change SKIPPED - Invalid status change. From: %s To: %s',
                                $orderStatus['order_id'],
                                $order->getOrderStatus(),
                                $newOrderStatus
                            )
                        );
                    }
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
        try {
            $logisticsService = $this->get('food.logistics');
            $orderService = $this->get('food.order');

            $driverData = $logisticsService->parseDriverAssignXml($request->getContent());

            foreach ($driverData as $driver) {
                $order = $orderService->getOrderById($driver['order_id']);
                $errorReason = '';

                // Skip non existant and completed orders
                if ($order instanceof Order) {
                    if (
                        // Jei is validaus statuso i assigned, arba yra ssigned ir keiciamas vairuotojas
                        (
                            $orderService->isValidOrderStatusChange($order->getOrderStatus(), $orderService::$status_assiged)
                            && $order->getOrderStatus() != $orderService::$status_assiged
                        )
                        || (
                            $order->getOrderStatus() == $orderService::$status_assiged
                            && $order->getDriver()->getId() != $driver['driver_id'])
                        ) {
                        $logisticsService->assignDriver($driver['driver_id'], array($driver['order_id']), true);
                    } else {
                        $errorReason = 'Invalid status change. From: '.$order->getOrderStatus().' To: '.$orderService::$status_assiged;
                    }
                } else {
                    $errorReason = 'Order does not exist';
                }

                if (!empty($errorReason)) {
                    $logger->warning(
                        sprintf(
                            'Driver id: %d assign to order id: %d SKIPPED - %s',
                            $driver['driver_id'],
                            $driver['order_id'],
                            $errorReason
                        )
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
