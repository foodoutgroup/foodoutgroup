<?php

namespace Food\OrderBundle\Controller\Decorators\SwedbankCreditCardGateway;

trait SharedDecorator
{
    protected function findOrder($id)
    {
        return $this->get('doctrine.orm.entity_manager')
                    ->getRepository('FoodOrderBundle:Order')
                    ->find($id);
    }

    protected function getReturnUrl()
    {
        return $this->generateUrl('swedbank_credit_card_gateway_success',
                                  [],
                                  true);
    }

    protected function getExpiryUrl()
    {
        return $this->generateUrl('swedbank_credit_card_gateway_failure',
                                  [],
                                  true);
    }

    protected function logPaidAndFinish($orderService, $order, $cartService)
    {
        $orderService->setPaymentStatus(
            $orderService::$paymentStatusComplete,
            'Swedbank Credit Card Gateway billed payment');
        $orderService->saveOrder();
        $orderService->informPlace();

        // Jei naudotas kuponas, paziurim ar nereikia jo deaktyvuoti
        $orderService->deactivateCoupon();

        // clear cart after success
        $cartService->clearCart($order->getPlace());
    }
}
