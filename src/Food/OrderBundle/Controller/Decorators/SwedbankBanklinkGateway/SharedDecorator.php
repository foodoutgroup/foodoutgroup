<?php

namespace Food\OrderBundle\Controller\Decorators\SwedbankBanklinkGateway;

trait SharedDecorator
{
    protected function findOrder($id)
    {
        return $this->get('doctrine.orm.entity_manager')
                    ->getRepository('FoodOrderBundle:Order')
                    ->find($id);
    }

    protected function getSuccessUrl($locale)
    {
        return $this->generateUrl('swedbank_gateway_success',
                                  ['_locale' => $locale],
                                  true);
    }

    protected function getFailureUrl($locale)
    {
        return $this->generateUrl('swedbank_gateway_failure',
                                  ['_locale' => $locale],
                                  true);
    }

    protected function logPaidAndFinish($orderService, $order, $cartService)
    {
        // is order already 'complete'? well then.. we have nothing to do here.
        if ($order->getPaymentStatus() ==
            $orderService::$paymentStatusComplete) return;

        $orderService->setPaymentStatus(
            $orderService::$paymentStatusComplete,
            'Swedbank Banklink Gateway billed payment');
        $orderService->saveOrder();
        $orderService->informPlace();

        // Jei naudotas kuponas, paziurim ar nereikia jo deaktyvuoti
        $orderService->deactivateCoupon();

        // clear cart after success
        $cartService->clearCart($order->getPlace());
    }

    protected function logProcessingAndFinish($orderService,
                                              $order,
                                              $cartService)
    {
        $orderService->logPayment(
            $order,
            'Swedbank Banklink Gateway payment started',
            'Swedbank Banklink Gateway payment accepted. Waiting for funds to be billed',
            $order
        );

        // clear cart after success
        $cartService->clearCart($order->getPlace());
    }

    protected function logFailureAndFinish($orderService, $order)
    {
        $orderService->logPayment(
            $order,
            'Swedbank Banklink Gateway payment canceled',
            'Swedbank Banklink Gateway canceled in Swedbank',
            $order
        );

        $orderService->setPaymentStatus(
            $orderService::$paymentStatusCanceled,
            'User canceled payment in Swedbank Banklink Gateway');
    }
}
