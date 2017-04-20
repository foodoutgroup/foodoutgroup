<?php

namespace Food\AppBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Food\OrderBundle\Entity\OrderRepository;
use Food\OrderBundle\Service\OrderService;
use Food\PlacesBundle\Service\PlacesService;
use libphonenumber\PhoneNumberUtil;
use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class DispatcherAdminController extends Controller
{
    public function listAction()
    {
        /**
         * @var $repo OrderRepository
         */
        $repo = $this->get('doctrine')->getRepository('FoodOrderBundle:Order');
        $orderService = $this->get('food.order');
        $placeService = $this->get('food.places');
        $logisticsService = $this->get('food.logistics');
        $driverCollection = [];

        $cityOrderCollection = [];
//        $availableCities = $placeService->getAvailableCities();

        $cityCollection = $this->getDoctrine()
            ->getRepository('FoodAppBundle:City')
            ->getActive();

        // TODO This is first stage optimization - 3/4 times less querys, more php work. Next stage - one query??
        $unapproved = $repo->getOrdersUnapproved();

        $unassigned = $repo->getOrdersUnassigned();
        $unconfirmed = $repo->getOrdersUnconfirmed(null, null, true);
        $notFinished = $repo->getOrdersAssigned();
        $canceled = $repo->getOrdersCanceled();
        $navProblems = $repo->getOrdersProblems();

        $activeDriverCollection = $logisticsService->getAllActiveDrivers();



        // Preload city data
        foreach ($cityCollection as $city) {
            $cityOrderCollection[$city->getId()] = [
                'unapproved'   => [
                    'pickup'      => [],
                    'selfdeliver' => [],
                    'deliver'     => [],
                    'late'        => 0
                ],
                'unassigned'   => [
                    'pickup'      => [],
                    'selfdeliver' => [],
                    'deliver'     => [],
                    'late'        => 0
                ],
                'unconfirmed'  => [
                    'pickup'      => [],
                    'selfdeliver' => [],
                    'deliver'     => [],
                    'late'        => 0
                ],
                'not_finished' => [
                    'pickup'      => [],
                    'selfdeliver' => [],
                    'deliver'     => [],
                    'late'        => 0
                ],
                'canceled'     => [
                    'pickup'      => [],
                    'selfdeliver' => [],
                    'deliver'     => [],
                ],
                'nav_problems' => [
                    'pickup'      => [],
                    'selfdeliver' => [],
                    'deliver'     => [],
                ],
            ];

            $driverCollection[$city->getId()] = [];
        }

        foreach ($unapproved as $order) {

            $placePoint = $order->getPlacePoint();
            $city = $placePoint->getCityId();

            if(!$city) {
                continue;
            }

            if ($order->getDeliveryType() == OrderService::$deliveryPickup) {
                $cityOrderCollection[$city->getId()]['unapproved']['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $cityOrderCollection[$city->getId()]['unapproved']['selfdeliver'][] = $order;
            } else {
                $cityOrderCollection[$city->getId()]['unapproved']['deliver'][] = $order;
            }

            if ($orderService->isLate($order)) {
                ++$cityOrderCollection[$city->getId()]['unapproved']['late'];
            }
        }

        foreach ($unassigned as $order) {

            $placePoint = $order->getPlacePoint();
            $city = $placePoint->getCityId();

            if(!$city) {
                continue;
            }

            if ($order->getDeliveryType() == OrderService::$deliveryPickup) {
                $cityOrderCollection[$city->getId()]['unassigned']['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $cityOrderCollection[$city->getId()]['unassigned']['selfdeliver'][] = $order;
            } else {
                $cityOrderCollection[$city->getId()]['unassigned']['deliver'][] = $order;
            }

            if ($orderService->isLate($order)) {
                ++$cityOrderCollection[$city->getId()]['unassigned']['late'];
            }
        }

        foreach ($unconfirmed as $order) {

            $placePoint = $order->getPlacePoint();
            $city = $placePoint->getCityId();

            if(!$city) {
                continue;
            }

            if ($order->getDeliveryType() == OrderService::$deliveryPickup) {
                $cityOrderCollection[$city->getId()]['unconfirmed']['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $cityOrderCollection[$city->getId()]['unconfirmed']['selfdeliver'][] = $order;
            } else {
                $cityOrderCollection[$city->getId()]['unconfirmed']['deliver'][] = $order;
            }

            if ($orderService->isLate($order)) {
                ++$cityOrderCollection[$city->getId()]['unconfirmed']['late'];
            }
        }

        foreach ($notFinished as $order) {

            $placePoint = $order->getPlacePoint();
            $city = $placePoint->getCityId();

            if(!$city) {
                continue;
            }

            if ($order->getDeliveryType() == OrderService::$deliveryPickup) {
                $cityOrderCollection[$city->getId()]['not_finished']['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $cityOrderCollection[$city->getId()]['not_finished']['selfdeliver'][] = $order;
            } else {
                $cityOrderCollection[$city->getId()]['not_finished']['deliver'][] = $order;
            }

            //todo:@@@@@@@@@@@@@@@@@@@@
            if ($orderService->isLate($order)) {
                ++$cityOrderCollection[$city->getId()]['not_finished']['late'];
            }
        }

        foreach ($canceled as $order) {

            $placePoint = $order->getPlacePoint();
            $city = $placePoint->getCityId();

            if(!$city) {
                continue;
            }

            if ($order->getDeliveryType() == OrderService::$deliveryPickup) {
                $cityOrderCollection[$city->getId()]['canceled']['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $cityOrderCollection[$city->getId()]['canceled']['selfdeliver'][] = $order;
            } else {
                $cityOrderCollection[$city->getId()]['canceled']['deliver'][] = $order;
            }
        }

        foreach ($navProblems as $order) {

            $placePoint = $order->getPlacePoint();
            $city = $placePoint->getCityId();

            if(!$city) {
                continue;
            }

            if ($order->getDeliveryType() == OrderService::$deliveryPickup) {
                $cityOrderCollection[$city->getId()]['nav_problems']['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $cityOrderCollection[$city->getId()]['nav_problems']['selfdeliver'][] = $order;
            } else {
                $cityOrderCollection[$city->getId()]['nav_problems']['deliver'][] = $order;
            }
        }

        foreach ($activeDriverCollection as $driver) {
            $driverCollection[$driver['city_id']][] = $driver;
        }

        return $this->render(
            'FoodAppBundle:Dispatcher:list.html.twig',
            [
                'cityCollection'     => $cityCollection,
                'cityOrderCollection' => $cityOrderCollection,
                'driverCollection'    => $driverCollection,
            ]
        );
    }

    public function statusPopupAction($orderId)
    {
        $orderService = $this->get('food.order');
        $order = $orderService->getOrderById($orderId);
        $delayDurations = [15, 30, 45, 60, 90, 120];

        switch ($order->getOrderStatus()) {
            case $orderService::$status_new:
                $orderStatuses = [
                    $orderService::$status_accepted,
                    $orderService::$status_canceled
                ];
                if (!$order->getPlace()->getNavision()) {
                    $orderStatuses[] = $orderService::$status_forwarded;
                }
                break;

            case $orderService::$status_accepted:
            case $orderService::$status_finished:
            case $orderService::$status_delayed:
                $orderStatuses = $orderService::getOrderStatuses();
                $index = array_search($orderService::$status_assiged, $orderStatuses);
                if (isset($orderStatuses[$index])) {
                    unset($orderStatuses[$index]);
                }
                if ($order->getPlace()->getNavision()) {
                    $index = array_search($orderService::$status_forwarded, $orderStatuses);
                    if (isset($orderStatuses[$index])) {
                        unset($orderStatuses[$index]);
                    }
                }
                break;

            default:
                $orderStatuses = $orderService::getOrderStatuses();
                if ($order->getPlace()->getNavision()) {
                    $index = array_search($orderService::$status_forwarded, $orderStatuses);
                    if (isset($orderStatuses[$index])) {
                        unset($orderStatuses[$index]);
                    }
                }
                break;
        }

        return $this->render(
            'FoodAppBundle:Dispatcher:status_popup.html.twig',
            [
                'orderStatuses'        => $orderStatuses,
                'currentStatus'        => $order->getOrderStatus(),
                'delayDurations'       => $delayDurations,
                'currentDelayDuration' => $order->getDelayDuration(),
                'cancelReasons'        => $this->_getCancelReasons(),
                'pp_list'              => $orderService->getPPList()
            ]
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

            if ($orderService->getAllowToInform()) {
                $orderService->informPlace();
            }

            return new Response('OK');
        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error while approving order: #' . $orderId . ' error:' . $e->getMessage());

            return new Response('Error');
        }
    }

    /**
     * @TODO refactor!!!
     */
    public function setOrderStatusAction($orderId, $status, $delayDuration = false, Request $request)
    {
        $cancelReason = $request->get('cancelReason');
        $cancelReasonComment = $request->get('cancelReasonComment');

        $orderService = $this->get('food.order');
        $setOrderStatus = function ($orderId, $status, $delayDuration, $cancelReason, $cancelReasonComment, $request) use (&$orderService) {
            $em = $orderService->getEm();
            $orderService->getOrderById($orderId);
            $oldStatus = $orderService->getOrder()->getOrderStatus();
            $method = 'status' . ucfirst($status);
            $order = $orderService->getOrder();
            if (method_exists($orderService, $method)) {
                $orderService->$method('dispatcher');
                $orderDelayed = $order->getDelayed();
                if ($method == 'statusDelayed' && !empty($delayDuration)) {
                    $orderService->statusDelayed('dispatcher', 'delay duration: ' . $delayDuration);
                    $order->setDelayed(true);
                    $order->setDelayReason('Delayed');
                    $order->setDelayDuration($delayDuration);
                    $orderService->saveDelay();
                } else if ($orderDelayed) {
                    $order->setDelayed(false);
                    $order->setDelayReason(null);
                    $order->setDelayDuration(null);
                }
                if ($method == 'statusCanceled') {
                    if (OrderService::$status_canceled != $oldStatus) {
                        $orderService->informPlaceCancelAction();
                    }

                    $orderExtra = $order
                        ->getOrderExtra()
                    ;
                    $orderExtra->setCancelReason($cancelReason)
                        ->setCancelReasonComment($cancelReasonComment)
                    ;
                    $orderService->getEm()->persist($orderExtra);

                    $orderService->informAdminAboutCancelation();
                } elseif ($method == 'statusCanceled_produced') {
                    if (OrderService::$status_canceled_produced != $oldStatus) {
                        $orderService->informPlaceCancelAction();
                    }
                    $orderExtra = $order
                        ->getOrderExtra()
                    ;
                    $orderExtra->setCancelReason($cancelReason)
                        ->setCancelReasonComment($cancelReasonComment)
                    ;
                    $orderService->getEm()->persist($orderExtra);

                    $orderService->informAdminAboutCancelation();
                } elseif ($method == 'statusForwarded') {
                    $newPP = $request->get('forwardedPPList');
                    $reason = $request->get('forwardedReasonComment');

                    $pp = $em->getRepository('FoodDishesBundle:PlacePoint')->find($newPP);

                    $message = $order->getPlacePointAddress() . ' -> ' . $pp->getAddress();

                    $orderService->logOrder($order, 'change_placepoint', $message);
                    $orderService->logOrder($order, 'change_placepoint_reason', $reason);

                    $order->setPlacePoint($pp)
                        ->setPlacePointAddress($pp->getAddress())
                        ->setDriver(null)
                        ->setPlaceInformed(false);

                    if ($order->getOrderStatus() != $orderService::$status_preorder) {
                        $orderService->statusNew();

                        $orderService->informPlace();
                    }
                }
            }
            $orderService->saveOrder();
        };

        try {
            $setOrderStatus($orderId, $status, $delayDuration, $cancelReason, $cancelReasonComment, $request);
        } catch (OptimisticLockException $e) {
            // Retry
            $setOrderStatus($orderId, $status, $delayDuration, $cancelReason, $cancelReasonComment, $request);
        } catch (\Exception $e) {
            // TODO normalus error return ir ispiesimas popupe
            $this->get('logger')->error('Error happened setting status: ' . $e->getMessage());

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
            $this->get('logger')->error('Error happened sending dispatcher sms message: ' . $e->getMessage());

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
            $placePoint->getCityId()->getTitle()
        );


        return $this->render(
            'FoodAppBundle:Dispatcher:driver_list.html.twig',
            [
                'drivers' => $drivers,
            ]
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
            $this->get('logger')->error('Error happened assigning a driver: ' . $e->getMessage());

            return new Response('Error: error occured');
        }

        return new Response('OK');
    }

    public function assignDispatcherAction(Request $request)
    {
        $orderId = $request->get('orderId');
        $orderService = $this->get('food.order');
        $orderService->getOrderById($orderId);
        $currentUser = $this->user();

        try {
            $orderService->getOrder()->setDispatcherId($currentUser);
            $orderService->saveOrder();
        } catch (\Exception $e) {
            // TODO normalus error return ir ispiesimas popupe
            $this->get('logger')->error('Error happened assigning a dispatcher: ' . $e->getMessage());

            return new Response('Error: error occured');
        }

        return new Response('OK');
    }

    private function user()
    {
        $sc = $this->get('security.context');

        if (!$sc->isGranted('ROLE_USER')) {
            return null;
        }

        return $sc->getToken()->getUser();
    }

    public function checkNewOrdersAction(Request $request)
    {
        $repo = $this->get('doctrine')->getManager()->getRepository('FoodOrderBundle:Order');

        $lastCheck = $request->get('lastCheck');

        if (!empty($lastCheck)) {
            $needUpdate = $repo->hasNewerOrdersThan($lastCheck);;
        } else {
            $needUpdate = true;
        }

//        $orders = $request->get('orders');

//        if (!empty($orders)) {
//            foreach($orders as $city => $orderData) {
//                foreach ($orderData as $type => $recentId) {
//                    switch($type) {
//                        case 'unassigned':
//                            if ($repo->hasNewUnassignedOrder($city, $recentId)) {
//                                $needUpdate = true;
//                                break 2;
//                            }
//                            break;
//
//                        case 'unconfirmed':
//                            if ($repo->hasNewUnconfirmedOrder($city, $recentId, false)) {
//                                $needUpdate = true;
//                                break 2;
//                            }
//                            break;
//
//                        case 'unconfirmed-pickup':
//                            if ($repo->hasNewUnconfirmedOrder($city, $recentId, true)) {
//                                $needUpdate = true;
//                                break 2;
//                            }
//                            break;
//                        case 'unapproved':
//                            if ($repo->hasNewUnapprovedOrder($city, $recentId)) {
//                                $needUpdate = true;
//                                break 2;
//                            }
//                            break;
//                    }
//                }
//            }
//        } else {
//            if ($repo->hasNewUnassignedOrder()) {
//                $needUpdate = true;
//            }
//            if ($repo->hasNewUnconfirmedOrder()) {
//                $needUpdate = true;
//            }
//        }

        if ($needUpdate) {
            return new JsonResponse(['lastCheck' => date('U'), 'needUpdate' => 'YES']);
        }

        return new JsonResponse(['lastCheck' => date('U'), 'needUpdate' => 'NO']);
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

            $message = 'Order #' . $order->getId();
            if ($status) {
                $message .= ' client was contacted about cancel';
            } else {
                $message .= ' client was not contacted about cancel';
            }
            $orderService->logOrder($order, 'client_contacted', $message);
        } catch (\Exception $e) {
            $this->get('logger')->error('Error occured while marking order as contacted. Error: ' . $e->getMessage());

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

            $message = 'Order #' . $order->getId();
            if ($status) {
                $message .= ' problem marked solved';
            } else {
                $message .= ' problem marked not solved';
            }
            $orderService->logOrder($order, 'problem_solved', $message);
        } catch (\Exception $e) {
            $this->get('logger')->error('Error occured while marking order as problem solved. Error: ' . $e->getMessage());

            return new Response('NO');
        }

        return new Response('YES');
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getUserInfoByPhoneAction(Request $request)
    {
        $info = [];
        $phone = $request->get('phone', '');

        if (mb_strlen($phone, 'UTF-8') > 5) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $phone = $phoneUtil->parse($phone, strtoupper($this->container->getParameter('locale')));
            $orderExtraCollection = $this->getDoctrine()->getRepository('FoodOrderBundle:OrderExtra')->getUserByPhone($phone->getNationalNumber());
            $info = $this->get('food.user')->getInfoForCrm($orderExtraCollection, $info);
        }

        return new JsonResponse(['status' => 'ok', 'info' => $info]);
    }

    /**
     * @return array
     */
    private function _getCancelReasons()
    {
        $trans = $this->get('translator');

        return [
            $trans->trans('admin.dispatcher.cancel_reason.client_changed_his_mind', [], 'SonataAdminBundle'),
            $trans->trans('admin.dispatcher.cancel_reason.restaurant_dont_works', [], 'SonataAdminBundle'),
            $trans->trans('admin.dispatcher.cancel_reason.restaurant_has_no_dishes', [], 'SonataAdminBundle'),
            $trans->trans('admin.dispatcher.cancel_reason.too_long_production_time', [], 'SonataAdminBundle'),
            $trans->trans('admin.dispatcher.cancel_reason.software_error', [], 'SonataAdminBundle'),
            $trans->trans('admin.dispatcher.cancel_reason.changing_order', [], 'SonataAdminBundle'),
        ];
    }

    public function assignPlaceInformedAction(Request $request)
    {
        $orderId = $request->get('orderId');
        $orderService = $this->get('food.order');
        $order = $orderService->getOrderById($orderId);

        try {
            if (!$order->getPlaceInformed()) {
                $orderService->informPlace();
            }
        } catch (\Exception $e) {
            $this->get('logger')->error('Error happened assigning a place informed: ' . $e->getMessage());

            return new Response('Error: error occured');
        }

        return new Response('OK');
    }

    public function logCallEventAction(Request $request)
    {
        $dispatcherService = $this->get('food.dispatcher_service');
        $dispatcherService->saveCallLog(
            $request->get('type'),
            $request->get('number'),
            $request->get('orderId')
        );
        return new Response();
    }
}
