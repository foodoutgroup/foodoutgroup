<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OrdersController extends Controller
{
    public function getOrdersAction()
    {
        // @todo Implement me :D
    }

    public function createOrderAction(Request $request)
    {
        return new JsonResponse($this->get('food_api.order')->createOrder($request));
    }

    public function getOrderDetailsAction($id)
    {

    }

    public function confirmOrderAction($id)
    {

    }

    public function getOrderStatusAction($id)
    {

    }
}
