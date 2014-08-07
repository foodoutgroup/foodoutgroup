<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Food\ApiBundle\Common\JsonRequest;

class OrdersController extends Controller
{
    public function getOrdersAction(Request $request)
    {
        $requestJson = new JsonRequest($request);
        return new JsonResponse($this->get('food_api.order')->getPendingOrders($request, $requestJson));
    }

    public function createOrderAction(Request $request)
    {
        $requestJson = new JsonRequest($request);
        return new JsonResponse($this->get('food_api.order')->createOrder($request, $requestJson));
    }

    public function getOrderDetailsAction($id)
    {
        return new JsonResponse(
            $this->get('food_api.order')->getOrderForResponse(
                $this->get('food.order')->getOrderById($id)
            )
        );
    }

    public function confirmOrderAction($id)
    {

    }

    public function getOrderStatusAction($id)
    {

    }
}
