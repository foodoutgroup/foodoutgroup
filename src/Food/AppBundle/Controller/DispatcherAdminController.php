<?php

namespace Food\AppBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;

class DispatcherAdminController extends Controller
{
    public function listAction()
    {
        $orderService = $this->get('food.order');
        $placeService = $this->get('food.places');
        // TODO tabikai miestams :P

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
}
