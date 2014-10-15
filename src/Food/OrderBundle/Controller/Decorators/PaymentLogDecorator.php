<?php

namespace Food\OrderBundle\Controller\Decorators;

trait PaymentLogDecorator
{
    protected function logPaidAndFinish($message,
                                        $orderService,
                                        $order,
                                        $cartService)
    {
        // is order already 'complete'? well then.. we have nothing to do here.
        if ($order->getPaymentStatus() ==
            $orderService::$paymentStatusComplete) return;

        $orderService->setPaymentStatus($orderService::$paymentStatusComplete,
                                        $message);
        $orderService->saveOrder();
        $orderService->informPlace();
        $orderService->deactivateCoupon();

        // clear cart after success
        $cartService->clearCart($order->getPlace());

        // insert order into nav
        $nav->insertOrder($nav->getOrderDataForNav($order));
    }

    protected function logFailureAndFinish($message, $orderService, $order)
    {
        $orderService->logPayment($order, $message, $message, $order);
        $orderService->setPaymentStatus($orderService::$paymentStatusCanceled,
                                        $message);
    }

    protected function logProcessingAndFinish($message,
                                              $orderService,
                                              $order,
                                              $cartService)
    {
        $orderService->logPayment($order, $message, $message, $order);
        $cartService->clearCart($order->getPlace());
    }
}
