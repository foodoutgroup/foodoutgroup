<?php

namespace Food\OrderBundle\Controller\Decorators\SwedbankCreditCardGateway;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\LockMode;

trait ReturnDecorator
{
    public function handleReturn(Request $request)
    {
        // services
        $orderService = $this->get('food.order');
        $cartService = $this->get('food.cart');
        $placeService = $this->get('food.places');
        $gateway = $this->get('pirminis_credit_card_gateway');
        $navService = $this->get('food.nav');
        $em = $this->get('doctrine')->getManager();
        $logger = $this->get('logger');

        // template
        $view = 'FoodOrderBundle:Payments:' .
            'swedbank_gateway/order_not_found.html.twig';

        // get order
        $transactionId = $gateway->order_id('swedbank', $request);

        if (empty($transactionId)) return $this->render($view);

        // extract actual order id. say thanks to swedbank requirements
        $transactionIdSplit = explode('_', $transactionId);
        $orderId = !empty($transactionIdSplit[0]) ? $transactionIdSplit[0] : 0;

        try {
            $order = $em->getRepository('FoodOrderBundle:Order')
                ->find($orderId, LockMode::OPTIMISTIC)
            ;
            $orderService->setOrder($order);
        } catch (\Exception $e) {
            return $this->render($view);
        }

        // is order paid? let's find out!
        if ($gateway->is_successful_payment('swedbank', $request)) {
            $view = 'FoodCartBundle:Default:payment_success.html.twig';

            $this->logPaidAndFinish(
                'Swedbank Credit Card Gateway billed payment',
                $orderService,
                $order,
                $cartService,
                $em,
                $navService,
                $logger,
                $placeService
            );

            return $this->render($view, ['order' => $order]);
        }

        $url = $this->generateUrl('food_cart',
            ['placeId' => $order->getPlace()->getId()]);
        $url .= '?hash=' . $order->getOrderHash();

        return $this->redirect($url);
    }
}
