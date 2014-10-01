<?php

namespace Food\OrderBundle\Controller\Decorators\SwedbankCreditCardGateway;

use Symfony\Component\HttpFoundation\Request;

trait ReturnDecorator
{
    public function handleReturn(Request $request)
    {
        // services
        $orderService = $this->get('food.order');
        $gateway = $this->get('pirminis_credit_card_gateway');

        $view = 'FoodOrderBundle:Payments:' .
                'swedbank_gateway/something_wrong.html.twig';

        // get order
        $transactionId = $gateway->order_id('swedbank', $request);

        if (empty($transactionId)) return [$view, []];

        // extract actual order id. say thanks to swedbank requirements
        $transactionIdSplit = explode('_', $transactionId);
        $orderId = !empty($transactionIdSplit[0]) ? $transactionIdSplit[0] : 0;
        $order = $orderService->getOrderById($orderId);

        if (!$order) {
            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/order_not_found.html.twig';
            return [$view, []];
        }

        // is order paid? let's find out!
        if ($gateway->is_successful_payment('swedbank', $request)) {
            $view = 'FoodCartBundle:Default:payment_success.html.twig';

            $this->logPaidAndFinish($orderService);
        }

        $data = ['order' => $order];
        return [$view, $data];
    }
}
