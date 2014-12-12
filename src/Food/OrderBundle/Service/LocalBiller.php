<?php

namespace Food\OrderBundle\Service;

use Food\OrderBundle\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerAware;

class LocalBiller extends ContainerAware implements BillingInterface {

    /**
     * @var string
     */
    private $locale;

    /**
     * @var\ Order
     */
    private $order;

    /**
     * @param Order $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    public function bill()
    {
        /**
         * @var Logger $logger
         */
        $logger = $this->container->get('logger');
        $logger->alert('--====================================================');

        $order = $this->getOrder();

        if (!($order instanceof Order)) {
            throw new \InvalidArgumentException('Sorry, You gave me someting, but not order :(');
        }

        $orderService = $this->container->get('food.order');
        $orderService->setOrder($order);

        $logger->alert('++ Parinktas atsiskaitymas atsiimamt - skipinam bilinga. Uzsakymo ID: '.$order->getId());
        $logger->alert('-------------------------------------');

        // Kadangi jokio paymento nedarom - uzdarom paymento flow su sekme
        $orderService->setPaymentStatus($orderService::$paymentStatusComplete);
        $orderService->saveOrder();

        // Jei naudotas kuponas, paziurim ar nereikia jo deaktyvuoti
        $orderService->deactivateCoupon();

        $this->container->get('food.cart')->clearCart($order->getPlace());
        $orderService->informPlace();

        $router = $this->container->get('router');
        return $router->generate('food_cart_success', array('orderHash' => $order->getOrderHash()));
    }

    public function rollback()
    {
        throw new \Exception('Not implemented yet');
    }

}