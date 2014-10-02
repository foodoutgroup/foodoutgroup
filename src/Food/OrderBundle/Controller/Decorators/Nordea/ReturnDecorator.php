<?php

namespace Food\OrderBundle\Controller\Decorators\Nordea;

use Symfony\Component\HttpFoundation\Request;

trait ReturnDecorator
{
    public function handleReturn(Request $request)
    {
        // services
        $orderService = $this->get('food.order');
        $cartService = $this->get('food.cart');

        // get order. we must use $orderService to find order
        $orderId = (int)$request->query->get('RETURN_REF', 0);
        $order = $orderService->getOrderById($orderId);

        // verify
        $verified = $this->verify($request, $orderService, $order);

        // template
        $view = 'FoodOrderBundle:Payments:' .
                'nordea_banklink/something_wrong.html.twig';


        if ($verified) {
            // $view = 'FoodOrderBundle:Payments:' .
            //         'nordea_banklink/success.html.twig';
            $view = 'FoodCartBundle:Default:payment_success.html.twig';

            // success
            $this->logPaidAndFinish($orderService, $order, $cartService);
        } else {
            $view = 'FoodOrderBundle:Payments:' .
                    'nordea_banklink/fail.html.twig';

            // fail
            $this->logFailureAndFinish($orderService, $order);
        }

        $data = ['order' => $order];
        return [$view, $data];
    }
}
