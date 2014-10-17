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

            // clear cart
            // $cartService->clearCart($order->getPlace());
        } catch (OptimisticLockException $e) {
            // actually do nothing
        }
    }


    protected function logFailureAndFinish($message, $orderService, $order)
    {
        try {
            $orderService->logPayment($order, $message, $message, $order);
            $orderService->setPaymentStatus(
                $orderService::$paymentStatusCanceled,
                $message);
        } catch (OptimisticLockException $e) {
            // actually do nothing
        }
    }

    protected function logProcessingAndFinish($message,
                                              $orderService,
                                              $order,
                                              $cartService)
    {
        try {
            $orderService->logPayment($order, $message, $message, $order);

            // clear cart
            $cartService->clearCart($order->getPlace());
        } catch (OptimisticLockException $e) {
            // actually do nothing
        }
    }
}
