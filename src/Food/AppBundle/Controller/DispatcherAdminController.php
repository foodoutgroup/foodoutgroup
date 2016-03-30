<?php

namespace Food\AppBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Food\OrderBundle\Service\OrderService;
use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DispatcherAdminController extends Controller
{
    public function listAction()
    {
        $repo = $this->get('doctrine')->getRepository('FoodOrderBundle:Order');
        $placeService = $this->get('food.places');
        $logisticsService = $this->get('food.logistics');

        $cityOrders = array();
        $availableCities = $placeService->getAvailableCities();

        // TODO This is first stage optimization - 3/4 times less querys, more php work. Next stage - one query??
        $unapproved = $repo->getOrdersUnapproved();
        $unassigned = $repo->getOrdersUnassigned();
        $unconfirmed = $repo->getOrdersUnconfirmed(null, null, true);
        $notFinished = $repo->getOrdersAssigned();
        $canceled = $repo->getOrdersCanceled();
        $navProblems = $repo->getOrdersProblems();

        $driversList = $logisticsService->getAllActiveDrivers();

        // Preload city data
        foreach($availableCities as $city) {
            $cityOrders[$city] = array(
                'unapproved' => array(
                    'pickup' => array(),
                    'selfdeliver' => array(),
                    'deliver' => array(),
                ),
                'unassigned' => array(
                    'pickup' => array(),
                    'selfdeliver' => array(),
                    'deliver' => array(),
                ),
                'unconfirmed' => array(
                    'pickup' => array(),
                    'selfdeliver' => array(),
                    'deliver' => array(),
                ),
                'not_finished' => array(
                    'pickup' => array(),
                    'selfdeliver' => array(),
                    'deliver' => array(),
                ),
                'canceled' => array(
                    'pickup' => array(),
                    'selfdeliver' => array(),
                    'deliver' => array(),
                ),
                'nav_problems' => array(
                    'pickup' => array(),
                    'selfdeliver' => array(),
                    'deliver' => array(),
                ),
            );

            $drivers[$city] = array();
        }

        foreach($unapproved as $order) {
            if ($order->getDeliveryType() == OrderService::$deliveryPickup) {
                $cityOrders[$order->getPlacePointCity()]['unapproved']['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $cityOrders[$order->getPlacePointCity()]['unapproved']['selfdeliver'][] = $order;
            } else {
                $cityOrders[$order->getPlacePointCity()]['unapproved']['deliver'][] = $order;
            }
        }

        foreach($unassigned as $order) {
            if ($order->getDeliveryType() == OrderService::$deliveryPickup) {
                $cityOrders[$order->getPlacePointCity()]['unassigned']['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $cityOrders[$order->getPlacePointCity()]['unassigned']['selfdeliver'][] = $order;
            } else {
                $cityOrders[$order->getPlacePointCity()]['unassigned']['deliver'][] = $order;
            }
        }

        foreach($unconfirmed as $order) {
            if ($order->getDeliveryType() == OrderService::$deliveryPickup) {
                $cityOrders[$order->getPlacePointCity()]['unconfirmed']['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $cityOrders[$order->getPlacePointCity()]['unconfirmed']['selfdeliver'][] = $order;
            } else {
                $cityOrders[$order->getPlacePointCity()]['unconfirmed']['deliver'][] = $order;
            }
        }

        foreach($notFinished as $order) {
            if ($order->getDeliveryType() == OrderService::$deliveryPickup) {
                $cityOrders[$order->getPlacePointCity()]['not_finished']['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $cityOrders[$order->getPlacePointCity()]['not_finished']['selfdeliver'][] = $order;
            } else {
                $cityOrders[$order->getPlacePointCity()]['not_finished']['deliver'][] = $order;
            }
        }

        foreach($canceled as $order) {
            if ($order->getDeliveryType() == OrderService::$deliveryPickup) {
                $cityOrders[$order->getPlacePointCity()]['canceled']['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $cityOrders[$order->getPlacePointCity()]['canceled']['selfdeliver'][] = $order;
            } else {
                $cityOrders[$order->getPlacePointCity()]['canceled']['deliver'][] = $order;
            }
        }

        foreach($navProblems as $order) {
            if ($order->getDeliveryType() == OrderService::$deliveryPickup) {
                $cityOrders[$order->getPlacePointCity()]['nav_problems']['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $cityOrders[$order->getPlacePointCity()]['nav_problems']['selfdeliver'][] = $order;
            } else {
                $cityOrders[$order->getPlacePointCity()]['nav_problems']['deliver'][] = $order;
            }
        }

        foreach($driversList as $driver) {
            $city = ucfirst($driver['city']);
            $drivers[$city][] = $driver;
        }

        // Old slow code
//        foreach ($availableCities as $city) {
//            $cityOrders[$city] = array(
//                'unapproved' => $repo->getOrdersUnapproved($city),
//                'unassigned' => $repo->getOrdersUnassigned($city),
//                'unconfirmed' => array(
//                    'deliver' => $repo->getOrdersUnconfirmed($city),
//                    'pickup' => $repo->getOrdersUnconfirmed($city, true),
//                ),
//                'not_finished' => $repo->getOrdersAssigned($city),
//                'canceled' => $repo->getOrdersCanceled($city),
//                'nav_problems' => $repo->getOrdersProblems($city),
//            );
//        }

        return $this->render(
            'FoodAppBundle:Dispatcher:list.html.twig',
            array(
                'cities' => $availableCities,
                'cityOrders' => $cityOrders,
                'drivers' => $drivers,
            )
        );
    }

    public function statusPopupAction($orderId)
    {
        $orderService = $this->get('food.order');
        $order = $orderService->getOrderById($orderId);
        $delayDurations = array(30, 45, 60, 90, 120);

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
                'delayDurations' => $delayDurations,
                'currentDelayDuration' => $order->getDelayDuration(),
            )
        );
    }

    public function approveOrderAction($orderId)
    {
        try {
            $orderService = $this->get('food.order');
            $orderService->getOrderById($orderId);

            if (!$orderService->getOrder()->getPreorder()) {
                $orderService->statusNew('approveOrderDispatcher');
            } else {
                $orderService->statusNewPreorder('approveOrderDispatcher');
            }

            $orderService->saveOrder();

            if (!$orderService->getOrder()->getPreorder() || $orderService->getOrder()->getPlace()->getNavision()) {
                $orderService->informPlace(false);
            }

            return new Response('OK');
        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error while approving order: #'.$orderId.' error:'.$e->getMessage());
            return new Response('Error');
        }
    }

    public function setOrderStatusAction($orderId, $status, $delayDuration = false)
    {
        $orderService = $this->get('food.order');
        $setOrderStatus = function ($orderId, $status, $delayDuration) use (&$orderService) {
            $orderService->getOrderById($orderId);
            $method = 'status' . ucfirst($status);
            if (method_exists($orderService, $method)) {
                $orderService->$method('dispatcher');
                $orderDelayed = $orderService->getOrder()->getDelayed();
                if ($method == 'statusDelayed' && !empty($delayDuration)) {
                    $orderService->statusDelayed('dispatcher', 'delay duration: ' . $delayDuration);
                    $orderService->getOrder()->setDelayed(true);
                    $orderService->getOrder()->setDelayReason('Delayed');
                    $orderService->getOrder()->setDelayDuration($delayDuration);
                    $orderService->saveDelay();
                } else if ($orderDelayed) {
                    $orderService->getOrder()->setDelayed(false);
                    $orderService->getOrder()->setDelayReason(null);
                    $orderService->getOrder()->setDelayDuration(null);
                }
                if ($method == 'statusCanceled') {
                    $orderService->informPlaceCancelAction();
                }
            }
            $orderService->saveOrder();
        };

        try {
            $setOrderStatus($orderId, $status, $delayDuration);
        } catch (OptimisticLockException $e) {
            // Retry
            $setOrderStatus($orderId, $status, $delayDuration);
        } catch (\Exception $e) {
            // TODO normalus error return ir ispiesimas popupe
            $this->get('logger')->error('Error happened setting status: '.$e->getMessage());
            return new Response('Error: error occured');
        }

        return new Response('OK');
    }

    public function sendOrderMessageAction($orderId, $message)
    {
        $orderService = $this->get('food.order');
        $messagingService = $this->get('food.messages');
        $sender = $this->container->getParameter('sms.sender');

        try {
            if (!empty($message)) {
                $orderService->getOrderById($orderId);
                $messagingService->addMessageToSend(
                    $sender,
                    $orderService->getOrder()->getOrderExtra()->getPhone(),
                    $message,
                    $orderService->getOrder()
                );
            }
        } catch (\Exception $e) {
            // TODO normalus error return ir ispiesimas popupe
            $this->get('logger')->error('Error happened sending dispatcher sms message: '.$e->getMessage());
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
                            if ($repo->hasNewUnconfirmedOrder($city, $recentId, false)) {
                                $needUpdate = true;
                                break 2;
                            }
                            break;

                        case 'unconfirmed-pickup':
                            if ($repo->hasNewUnconfirmedOrder($city, $recentId, true)) {
                                $needUpdate = true;
                                break 2;
                            }
                            break;
                        case 'unapproved':
                            if ($repo->hasNewUnapprovedOrder($city, $recentId)) {
                                $needUpdate = true;
                                break 2;
                            }
                            break;
                    }
                }
            }
        } else {
            if ($repo->hasNewUnassignedOrder()) {
                $needUpdate = true;
            }
            if ($repo->hasNewUnconfirmedOrder()) {
                $needUpdate = true;
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

    public function markOrderSolvedAction(Request $request)
    {
        $orderService = $this->get('food.order');

        $orderId = $request->get('order');
        $status = $request->get('status');

        try {
            $order = $orderService->getOrderById($orderId);

            $order->setProblemSolved((bool)$status);
            $orderService->saveOrder();

            $message = 'Order #'.$order->getId();
            if ($status) {
                $message .= ' problem marked solved';
            } else {
                $message .= ' problem marked not solved';
            }
            $orderService->logOrder($order, 'problem_solved', $message);
        } catch (Exception $e) {
            $this->get('logger')->error('Error occured while marking order as problem solved. Error: '.$e->getMessage());

            return new Response('NO');
        }

        return new Response('YES');
    }
}
