<?php

namespace Food\OrderBundle\Controller\Decorators\SwedbankBanklinkGateway;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\LockMode;

trait ReturnDecorator
{
    protected function handleReturn(Request $request)
    {
        // services
        $orderService = $this->get('food.order');
        $gateway = $this->get('pirminis_banklink_gateway');
        $cartService = $this->get('food.cart');
        $navService = $this->get('food.nav');
        $em = $this->get('doctrine')->getManager();
        $logger = $this->get('logger');

        // default template
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
                        ->find($orderId, LockMode::OPTIMISTIC);
            $orderService->setOrder($order);
        } catch (\Exception $e) {
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
            $this->logPaidAndFinish('Swedbank Banklink Gateway billed payment',
                                    $orderService,
                                    $order,
                                    $cartService,
                                    $em,
                                    $navService,
                                    $logger);

            if ($isEvent) {
                return new Response('<Response>OK</Response>');
            } else {
                $view = 'FoodCartBundle:Default:payment_success.html.twig';
                return $this->render($view, ['order' => $order]);
            }
        // is order payment accepted and is currently processing?
        } elseif ((!$isEvent &&
                   $gateway->requires_investigation('swedbank', $request)) ||
                  ($isEvent &&
                   $gateway->event_requires_investigation('swedbank', $request))
        ) {
            // log
            $logger->alert("==========================\nprocessing payment action for Swedbank Gateway Banklink came\n====================================\n");
            $logger->alert("Request data: ".var_export($request->query->all(), true));
            $logger->alert('-----------------------------------------------------------');

            // log
            $logger->alert('Processing order ' . $order->getId());

            $this->logProcessingAndFinish(
                'Swedbank Banklink Gateway payment started',
                $orderService,
                $order,
                $cartService);

            if ($isEvent) {
                return new Response('<Response>OK</Response>');
            } else {
                $view = 'FoodOrderBundle:Payments:' .
                        'swedbank_gateway/processing.html.twig';
                return $this->render($view, ['order' => $order]);
            }
        // is payment cancelled due to reasons?
        } elseif ((!$isEvent &&
                   $gateway->is_cancelled('swedbank', $request)) ||
                  ($isEvent &&
                   $gateway->is_event_cancelled('swedbank', $request))
        ) {
            // log
            $logger->alert("==========================\ncancel payment action for Swedbank Gateway Banklink came\n====================================\n");
            $logger->alert("Request data: ".var_export($request->query->all(), true));
            $logger->alert('-----------------------------------------------------------');

            // log
            $logger->alert('Cancelling order ' . $order->getId());

            $this->logFailureAndFinish(
                'Swedbank Banklink Gateway payment canceled',
                $orderService,
                $order,
                $logger);

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
            return $this->render($view, ['order' => $order]);
        // was there a communication error with/in bank?
        } elseif ($gateway->communication_error('swedbank', $request)) {
            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/communication_error.html.twig';
            return $this->render($view, ['order' => $order]);
        } else {
            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/something_wrong.html.twig';
        }

        return $this->render($view, ['order' => $order]);
    }

    /**
     * TODO Laikinai viskas cia - po to iskelinesime is Jonelio personal repos ir viska nafig refaktorinsim nes cia ne darbas...
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    protected function handleCalback(Request $request)
    {
        // services
        $orderService = $this->get('food.order');
        $cartService = $this->get('food.cart');
        $navService = $this->get('food.nav');
        $em = $this->get('doctrine')->getManager();
        $logger = $this->get('logger');

        $dom = simplexml_load_string($request->getContent());

        // get order
        $purchases = $dom->xpath('//Event//Purchase');

        if (empty($purchases)) {
            $logger->error("Swedbank callback gave XML without purchases part");
            throw new \Exception('Wrong XML format from Swedbank');
        }

        $purchase = reset($purchases);

        if (empty($purchase)) {
            $logger->error("Swedbank callback gave XML without purchase part");
            throw new \Exception('Wrong XML format from Swedbank');
        }

        $attributes = (array)$purchase->attributes();
        $transactionId = $attributes['@attributes']['TransactionId'];

        if (empty($transactionId)) {
            $logger->error("Swedbank callback without trans ID received :(");
            throw new \Exception("Wrong XML format from Swedbank");
        }

        // extract actual order id. say thanks to swedbank requirements
        $transactionIdSplit = explode('_', $transactionId);
        $orderId = !empty($transactionIdSplit[0]) ? $transactionIdSplit[0] : 0;

        try {
            $order = $em->getRepository('FoodOrderBundle:Order')
                        ->find($orderId, LockMode::OPTIMISTIC);
            $orderService->setOrder($order);
        } catch (\Exception $e) {
            $logger->error("Error during swedbank callback: ".$e->getMessage());
            throw $e;
        }

        $authorizeStatus = $dom->xpath('//Purchase//Status');
        $authorizeStatus = reset($authorizeStatus);

        // is order paid? let's find out!
        if ($authorizeStatus == 'AUTHORISED') {
            $this->logPaidAndFinish('Swedbank Banklink Gateway billed payment',
                                    $orderService,
                                    $order,
                                    $cartService,
                                    $em,
                                    $navService,
                                    $logger);

            return new Response('<Response>OK</Response>');
        // is order payment accepted and is currently processing?
        } elseif ($authorizeStatus == 'REQUIRES_INVESTIGATION') {
            // log
            $logger->alert("==========================\nprocessing payment action for Swedbank Gateway Banklink came\n====================================\n");
            $logger->alert("Request data: ".var_export($request->query->all(), true));
            $logger->alert('-----------------------------------------------------------');

            // log
            $logger->alert('Processing order ' . $order->getId());

            $this->logProcessingAndFinish(
                'Swedbank Banklink Gateway payment started',
                $orderService,
                $order,
                $cartService);

            return new Response('<Response>OK</Response>');
        // is payment cancelled due to reasons?
        } elseif ($authorizeStatus == 'CANCELLED') {
            // log
            $logger->alert("==========================\ncancel payment action for Swedbank Gateway Banklink came\n====================================\n");
            $logger->alert("Request data: ".var_export($request->query->all(), true));
            $logger->alert('-----------------------------------------------------------');

            // log
            $logger->alert('Cancelling order ' . $order->getId());

            $this->logFailureAndFinish(
                'Swedbank Banklink Gateway payment canceled',
                $orderService,
                $order,
                $logger);

            return new Response('<Response>OK</Response>');
        // did we get error from the bank? :(
//        } elseif ($gateway->is_error('swedbank', $request)) {
            // TODO handling
//            $view = 'FoodOrderBundle:Payments:' .
//                    'swedbank_gateway/error.html.twig';
//            return $this->render($view, ['order' => $order]);
        // was there a communication error with/in bank?
//        } elseif ($gateway->communication_error('swedbank', $request)) {
            // TODO handling
//            $view = 'FoodOrderBundle:Payments:' .
//                    'swedbank_gateway/communication_error.html.twig';
//            return $this->render($view, ['order' => $order]);
//        } else {
            // TODO handling
//            $view = 'FoodOrderBundle:Payments:' .
//                    'swedbank_gateway/something_wrong.html.twig';
        }

        return new Response('<Response>ERROR</Response>');
    }
}
