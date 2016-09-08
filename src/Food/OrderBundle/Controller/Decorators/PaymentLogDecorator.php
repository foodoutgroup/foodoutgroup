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
                                        $navService,
                                        $logger,
                                        $placeService)
    {
        // if order is already complete - cancel
        if ($order->getPaymentStatus() ==
            $orderService::$paymentStatusComplete) {
            // log
            $logger->alert('Order is already complete, so returning without marking order complete and sending emails "logPaidAndFinish()".');

            return;
        }

        $orderService->setPaymentStatusWithoutSave(
            $order,
            $orderService::$paymentStatusComplete,
            $message);
        $order->setLastUpdated(new \DateTime('now'));

        // try saving order with optimistic lock on
        try {
            // throws exception on optimistic lock check failure
            $em->flush();

            // inform stuff
            if ($orderService->getAllowToInform()) {
                $orderService->informPlace();
            }

            // Send Message To User About Successfully Created Order
            $orderService->sendOrderCreatedMessage();

            // Jei naudotas kuponas, paziurim ar nereikia jo deaktyvuoti
            $orderService->deactivateCoupon();

            // log order data (if we have listeners)
            $orderService->markOrderForNav($order);

            // clear cart
            $cartService->clearCart($order->getPlace());

            // log
            $logger->alert('Completed order ' . $order->getId() . ', informed place.');
        } catch (OptimisticLockException $e) {
            // actually reset entity manager
            $this->get('doctrine')->resetManager();

            // log
            $logger->alert('Optimistic lock failed, so order ' . $order->getId() . ' was not marked completed, which means place was NOT informed, which is good.');
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
            // actually reset entity manager
            $this->get('doctrine')->resetManager();
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
            // actually reset entity manager
            $this->get('doctrine')->resetManager();
        }
    }
}
