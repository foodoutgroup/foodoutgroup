<?php

namespace Food\AppBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Response;

class DispatcherAdminController extends Controller
{
    public function listAction()
    {
        $orderService = $this->get('food.order');
        $placeService = $this->get('food.places');

        $cityOrders = array();
        $availableCities = $placeService->getAvailableCities();

        foreach ($availableCities as $city) {
            $cityOrders[$city] = array(
                'unassigned' => $orderService->getOrdersUnassigned($city),
                'unconfirmed' => $orderService->getOrdersUnconfirmed($city),
                'not_finished' => $orderService->getOrdersAssigned($city),
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

    public function setOrderStatusAction($orderId, $status)
    {
        $orderService = $this->get('food.order');

        try {
            $orderService->getOrderById($orderId);

            $method = 'status'.ucfirst($status);
            if (method_exists($orderService, $method)) {
                $orderService->$method('dispatcher');
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

    public function assignDriverAction()
    {
        $request = $this->get('request');
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

    public function checkNewOrdersAction()
    {
        $request = $this->get('request');
        $orderService = $this->get('food.order');

        $orders = $request->get('orders');
        $needUpdate = false;

        if (!empty($orders)) {
            foreach($orders as $city => $orderData) {
                foreach ($orderData as $type => $recentId) {
                    switch($type) {
                        case 'unassigned':
                            if ($orderService->hasNewUnassignedOrder($city, $recentId)) {
                                $needUpdate = true;
                                break 2;
                            }
                            break;

                        case 'unconfirmed':
                            if ($orderService->hasNewUnconfirmedOrder($city, $recentId)) {
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
}
