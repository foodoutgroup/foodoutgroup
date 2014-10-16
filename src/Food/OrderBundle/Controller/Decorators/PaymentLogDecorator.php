<?php

namespace Food\OrderBundle\Controller\Decorators;

use Doctrine\ORM\OptimisticLockException;

trait PaymentLogDecorator
{
    protected function logPaidAndFinish($message,
                                        $orderService,
                                        $order,
                                        $cartService,
                                        $em,
                                        $navService)
    {
        $orderService->setPaymentStatusWithoutSave(
            $order,
            $orderService::$paymentStatusComplete,
            $message);
        $order->setLastUpdated(new \DateTime('now'));

        // try saving order with optimistic lock on
        try {
            $em->flush();

            // inform stuff
            $orderService->informPlace();
            $orderService->deactivateCoupon();

            // insert order into nav
            $navService->insertOrder($navService->getOrderDataForNav($order));
        } catch (OptimisticLockException $e) {
            // actually do nothing
        }

        $cartService->clearCart($order->getPlace());
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
