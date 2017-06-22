<?php

namespace Food\OrderBundle\Service;

use Food\OrderBundle\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerAware;

class LocalBiller extends ContainerAware implements BillingInterface
{

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

        $router = $this->container->get('router');
        if ($order->getPaymentStatus() == 'complete') {
            $logger->critical('Trying to set complete on complete order');
            $logger->critical(print_r(debug_backtrace(2), true));
            return $router->generate('food_cart_success', ['orderHash' => $order->getOrderHash()]);

        }

        $orderService = $this->container->get('food.order');
        $placeService = $this->container->get('food.places');
        $orderService->setOrder($order);

        $logger->alert('++ Parinktas atsiskaitymas atsiimamt - skipinam bilinga. Uzsakymo ID: ' . $order->getId());
        $logger->alert('-------------------------------------');

        // Kadangi jokio paymento nedarom - uzdarom paymento flow su sekme
        $orderService->setPaymentStatus($orderService::$paymentStatusComplete);
        $orderService->saveOrder();


        // Jei naudotas kuponas, paziurim ar nereikia jo deaktyvuoti
        $orderService->deactivateCoupon();

        $this->container->get('food.cart')->clearCart($order->getPlace());

        // Unapproved logic should not affect mobile users
        if (!$order->getMobile()) {
            $possibleNewUser = $this->container->get('food.app.utils.misc')->isNewOrSuspectedUser($order->getUser());
        } else {
            $possibleNewUser = false;
        }

        if ($possibleNewUser) {
            $orderService->statusUnapproved('new_order', 'Possible new unreliable user or suspected fraud');
            $orderService->informUnapproved();
        } else {
            // If pre order - do not inform (only if it is a NAV order - the NAV is responsible for pre)
            if ($orderService->getAllowToInform()) {
                $orderService->informPlace();
            }

            if ($order->getPreorder()) {
                $orderService->statusNewPreorder('local accept');
            } else {
                $orderService->statusNew('local accept');
            }
        }

        return $router->generate('food_cart_success', ['orderHash' => $order->getOrderHash()]);
    }

    public function rollback()
    {
        throw new \Exception('Not implemented yet');
    }

}
