<?php

namespace Food\AppBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;

class DispatcherAdminController extends Controller
{
    public function listAction()
    {
        $orderService = $this->get('food.order');

        // TODO tabikai miestams :P
        $orders = $orderService->getOrdersUnassigned();

        return $this->render('FoodAppBundle:Dispatcher:list.html.twig', array('orders' => $orders));
    }

}
