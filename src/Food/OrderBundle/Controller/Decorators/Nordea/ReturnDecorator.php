<?php

namespace Food\OrderBundle\Controller\Decorators\Nordea;

use Symfony\Component\HttpFoundation\Request;

trait ReturnDecorator
{
    public function handleReturn(Request $request)
    {
        // services
        $orderService = $this->container->get('food.order');

        // get order. we must use $orderService to find order
        $orderId = (int)$request->query->get('RETURN_REF', 0);
        $order = $orderService->getOrderById($orderId);

        // verify
        $verified = $this->verify($request, $orderService, $order);

        // template
        $view = 'FoodOrderBundle:Payments:' .
                'nordea_banklink/something_wrong.html.twig';


        if ($verified) {
            $view = 'FoodOrderBundle:Payments:' .
                    'nordea_banklink/success.html.twig';

            // success
            $this->logSuccessAndFinish($orderService);
        } else {
            $view = 'FoodOrderBundle:Payments:' .
                    'nordea_banklink/fail.html.twig';

            // fail
            $this->logFailureAndFinish($orderService, $order);
        }

        $data = [];
        return [$view, $data];
    }
}
