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
        $logger = $this->get('logger');

        // template
        $view = 'FoodOrderBundle:Payments:nordea_banklink/fail.html.twig';

        // order id
        $orderId = (int)$request->query->get('RETURN_REF', 0);

        try {
            $order = $em->getRepository('FoodOrderBundle:Order')
                        ->find($orderId, LockMode::OPTIMISTIC);
            $orderService->setOrder($order);
        } catch (\Exception $e) {
            return [$view, []];
        }

        // verify
        $verified = $this->verify($request, $orderService, $order);

        if ($verified) {
            $view = 'FoodCartBundle:Default:payment_success.html.twig';

            // success
            $this->logPaidAndFinish('Nordea banklink billed payment',
                                    $orderService,
                                    $order,
                                    $cartService,
                                    $em,
                                    $navService,
                                    $logger);
        } else {
            // fail
            $this->logFailureAndFinish('Nordea banklink failed payment',
                                       $orderService,
                                       $order);
        }

        $data = ['order' => $order];
        return [$view, $data];
    }
}
