<?php

namespace Food\OrderBundle\Controller\Decorators\SwedbankBanklinkGateway;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait ReturnDecorator
{
    protected function handleReturn(Request $request)
    {
        // services
        $orderService = $this->get('food.order');
        $gateway = $this->get('pirminis_banklink_gateway');
        $cartService = $this->get('food.cart');

        $view = 'FoodOrderBundle:Payments:' .
                'swedbank_gateway/something_wrong.html.twig';

        // get order
        $transactionId = $gateway->order_id('swedbank', $request);

        if (empty($transactionId)) return $this->render($view);

        // extract actual order id. say thanks to swedbank requirements
        $transactionIdSplit = explode('_', $transactionId);
        $orderId = !empty($transactionIdSplit[0]) ? $transactionIdSplit[0] : 0;
        $order = $orderService->getOrderById($orderId);

        if (!$order) {
            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/order_not_found.html.twig';
            return $this->render($view);
        }

        // is this event (callback from bank) or just ordinary customer?
        $isEvent = $gateway->is_event('swedbank', $request);

        // is order paid? let's find out!
        if ((!$isEvent &&
             $gateway->is_authorized('swedbank', $request)) ||
            ($isEvent &&
             $gateway->is_event_authorized('swedbank', $request))
        ) {
            $this->logPaidAndFinish($orderService, $order, $cartService);

            if ($isEvent) {
                return new Response('<Response>OK</Response>');
            } else {
                $view = 'FoodCartBundle:Default:payment_success.html.twig';
            }
        // is order payment accepted and is currently processing?
        } elseif ((!$isEvent &&
                   $gateway->requires_investigation('swedbank', $request)) ||
                  ($isEvent &&
                   $gateway->event_requires_investigation('swedbank', $request))
        ) {
            $this->logProcessingAndFinish($orderService, $order, $cartService);

            if ($isEvent) {
                return new Response('<Response>OK</Response>');
            } else {
                $view = 'FoodOrderBundle:Payments:' .
                        'swedbank_gateway/processing.html.twig';
            }
        // is payment cancelled due to reasons?
        } elseif ((!$isEvent &&
                   $gateway->is_cancelled('swedbank', $request)) ||
                  ($isEvent &&
                   $gateway->is_event_cancelled('swedbank', $request))
        ) {
            $this->logFailureAndFinish($orderService, $order);

            if ($isEvent) {
                return new Response('<Response>OK</Response>');
            } else {
                $url = $this->generateUrl(
                    'food_cart',
                    ['placeId' => $order->getPlace()->getId()]);
                $url .= '?hash=' . $order->getOrderHash();

                return $this->redirect($url);
            }
        // did we get error from the bank? :(
        } elseif ($gateway->is_error('swedbank', $request)) {
            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/error.html.twig';
        // was there a communication error with/in bank?
        } elseif ($gateway->communication_error('swedbank', $request)) {
            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/communication_error.html.twig';
        }

        return $this->render($view, ['order' => $order]);
    }
}
