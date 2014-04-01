<?php

namespace Food\AppBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\BrowserKit\Response;

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

        return $this->render(
            'FoodAppBundle:Dispatcher:status_popup.html.twig',
            array(
                'orderStatuses' => $orderService::getOrderStatuses(),
                'currentStatus' => $order->getOrderStatus(),
            )
        );
    }

    public function setOrderStatusAction($orderId, $status)
    {
        // TODO
        return new Response('OK');
    }

    public function getDriverListAction()
    {
        // TODO
        return $this->render(
            'FoodAppBundle:Dispatcher:status_popup.html.twig',
            array(
//                'cities' => $availableCities,
//                'cityOrders' => $cityOrders,
            )
        );
    }
}
