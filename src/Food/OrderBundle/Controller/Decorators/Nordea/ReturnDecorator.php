<?php

namespace Food\OrderBundle\Controller\Decorators\Nordea;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\LockMode;

trait ReturnDecorator
{
    public function handleReturn(Request $request)
    {
        // services
        $orderService = $this->get('food.order');
        $cartService = $this->get('food.cart');
        $navService = $this->get('food.nav');
        $em = $this->get('doctrine')->getManager();

        // get order with an optimistic lock
        $orderId = (int)$request->query->get('RETURN_REF', 0);
        $order = $em->getRepository('FoodOrderBundle:Order')
                    ->find($orderId, LockMode::OPTIMISTIC);
        $orderService->setOrder($order);

        // verify
        $verified = $this->verify($request, $orderService, $order);

        // template
        $view = 'FoodOrderBundle:Payments:' .
                'nordea_banklink/something_wrong.html.twig';


        if ($verified) {
            $view = 'FoodCartBundle:Default:payment_success.html.twig';

            // success
            $this->logPaidAndFinish('Nordea banklink billed payment',
                                    $orderService,
                                    $order,
                                    $cartService,
                                    $em,
                                    $nav);
        } else {
            $view = 'FoodOrderBundle:Payments:' .
                    'nordea_banklink/fail.html.twig';

            // fail
            $this->logFailureAndFinish('Nordea banklink failed payment',
                                       $orderService,
                                       $order);
        }

        $data = ['order' => $order];
        return [$view, $data];
    }
}
