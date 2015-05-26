<?php

namespace Food\AppBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DispatcherAdminController extends Controller
{
    public function listAction()
    {
        $repo = $this->get('doctrine')->getRepository('FoodOrderBundle:Order');
        $placeService = $this->get('food.places');

        $cityOrders = array();
        $availableCities = $placeService->getAvailableCities();

        foreach ($availableCities as $city) {
            $cityOrders[$city] = array(
                'unapproved' => $repo->getOrdersUnapproved($city),
                'unassigned' => $repo->getOrdersUnassigned($city),
                'unconfirmed' => array(
                    'deliver' => $repo->getOrdersUnconfirmed($city),
                    'pickup' => $repo->getOrdersUnconfirmed($city, true),
                ),
                'not_finished' => $repo->getOrdersAssigned($city),
                'canceled' => $repo->getOrdersCanceled($city),
            );
        }

        return $this->render(
            'FoodAppBundle:Dispatcher:list.html.twig',
            array(
                'cities' => $availableCities,
                'cityOrders' => $cityOrders,
            )
        );
    }

    public function statusPopupAction($orderId)
    {
        $orderService = $this->get('food.order');
        $order = $orderService->getOrderById($orderId);

        switch ($order->getOrderStatus()) {
            case $orderService::$status_new:
                $orderStatuses = array(
                    $orderService::$status_accepted,
                    $orderService::$status_canceled,
                );
                break;

            case $orderService::$status_accepted:
            case $orderService::$status_finished:
            case $orderService::$status_delayed:
                $orderStatuses = $orderService::getOrderStatuses();
                $index = array_search('assigned', $orderStatuses);
                if (isset($orderStatuses[$index])) {
                    unset($orderStatuses[$index]);
                }
                break;

            default:
                $orderStatuses = $orderService::getOrderStatuses();
                break;
        }

        return $this->render(
            'FoodAppBundle:Dispatcher:status_popup.html.twig',
            array(
                'orderStatuses' => $orderStatuses,
                'currentStatus' => $order->getOrderStatus(),
            )
        );
    }

    public function approveOrderAction($orderId)
    {
        $orderService = $this->get('food.order');
        $orderService->getOrderById($orderId);

        $orderService->statusNew('approveOrderDispatcher');

        $orderService->informPlace(false);

        return new Response('OK');
    }

    public function setOrderStatusAction($orderId, $status)
    {
        $orderService = $this->get('food.order');

        try {
            $orderService->getOrderById($orderId);

            $method = 'status' . ucfirst($status);
            if (method_exists($orderService, $method)) {
                $orderService->$method('dispatcher');

                if ($method == 'statusCanceled') {
                    $orderService->informPlaceCancelAction();
                }
            }
            $orderService->saveOrder();
        } catch (OptimisticLockException $e) {
            // Retry
            $orderService->getOrderById($orderId);
            if (method_exists($orderService, $method)) {
                $orderService->$method('dispatcher');

                if ($method == 'statusCanceled') {
                    $orderService->informPlaceCancelAction();
                }
            }
            $orderService->saveOrder();
        } catch (\Exception $e) {
            // TODO normalus error return ir ispiesimas popupe
            $this->get('logger')->error('Error happened setting status: '.$e->getMessage());
            return new Response('Error: error occured');
        }

        return new Response('OK');
    }

    public function getDriverListAction($orders)
    {
        $orderService = $this->get('food.order');
        $logisticsService = $this->get('food.logistics');
        // TODO koordinates, etaxi pajungimas ir switchas duomenu pagal setinga - ar backup ar etaxi
        $orderIds = explode(',', $orders);
        // TODO kolkas imam pirmo orderio, po to galim sugalvoti, kaip issirinkti kurias koordinates naudoti
        $order = $orderService->getOrderById(reset($orderIds));
        $placePoint = $order->getPlacePoint();

        $drivers = $logisticsService->getDrivers(
            $placePoint->getLat(),
            $placePoint->getLon(),
            $placePoint->getCity()
        );


        return $this->render(
            'FoodAppBundle:Dispatcher:driver_list.html.twig',
            array(
                'drivers' => $drivers,
            )
        );
    }

    public function assignDriverAction(Request $request)
    {
        $driverId = $request->get('driverId');
        $ordersIds = $request->get('orderIds');

        $logisticsService = $this->get('food.logistics');

        try {
            $logisticsService->assignDriver($driverId, $ordersIds);
        } catch (\Exception $e) {
            // TODO normalus error return ir ispiesimas popupe
            $this->get('logger')->error('Error happened assigning a driver: '.$e->getMessage());
            return new Response('Error: error occured');
        }

        return new Response('OK');
    }

    public function checkNewOrdersAction(Request $request)
    {
        $repo = $this->get('doctrine')->getManager()->getRepository('FoodOrderBundle:Order');

        $orders = $request->get('orders');
        $needUpdate = false;

        if (!empty($orders)) {
            foreach($orders as $city => $orderData) {
                foreach ($orderData as $type => $recentId) {
                    switch($type) {
                        case 'unassigned':
                            if ($repo->hasNewUnassignedOrder($city, $recentId)) {
                                $needUpdate = true;
                                break 2;
                            }
                            break;

                        case 'unconfirmed':
                            if ($repo->hasNewUnconfirmedOrder($city, $recentId)) {
                                $needUpdate = true;
                                break 2;
                            }
                            break;
                    }
                }
            }
        }

        if ($needUpdate) {
            return new Response('YES');
        }

        return new Response('NO');
    }

    public function markOrderContactedAction(Request $request)
    {
        $orderService = $this->get('food.order');

        $orderId = $request->get('order');
        $status = $request->get('status');

        try {
            $order = $orderService->getOrderById($orderId);

            $order->setClientContacted((bool)$status);
            $orderService->saveOrder();

            $message = 'Order #'.$order->getId();
            if ($status) {
                $message .= ' client was contacted about cancel';
            } else {
                $message .= ' client was not contacted about cancel';
            }
            $orderService->logOrder($order, 'client_contacted', $message);
        } catch (Exception $e) {
            $this->get('logger')->error('Error occured while marking order as contacted. Error: '.$e->getMessage());

            return new Response('NO');
        }

        return new Response('YES');
    }
}
