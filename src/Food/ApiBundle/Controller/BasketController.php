<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BasketController extends Controller
{
    public function createBasketAction()
    {
        $this->get('food_api.basket')->createBasketFromRequest($this->getRequest());
    }

    public function getBasketAction($id)
    {
        $basket = $this->get('food_api.basket')->getBasket($id);
        return new JsonResponse($basket);
    }
}