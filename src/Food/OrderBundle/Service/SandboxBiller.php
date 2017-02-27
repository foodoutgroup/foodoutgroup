<?php

namespace Food\OrderBundle\Service;

use Food\OrderBundle\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerAware;

class SandboxBiller extends ContainerAware implements BillingInterface {

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

        return $this;
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
        $order = $this->getOrder();

        if (!($order instanceof Order)) {
            throw new \InvalidArgumentException('Sorry, You gave me someting, but not order :(');
        }

        $router = $this->container->get('router');

        $amount = $order->getTotal();

        $redirectUrl = $router->generate('sandbox_choose', array(), true);
        $redirectUrl.= "?ResNum=".urlencode($order->getOrderHash());
        $redirectUrl.= "&Amount=".$amount;
        $redirectUrl.= "&RedirectURL=".urlencode($router->generate('sandbox_return', array(), true));
        return $redirectUrl;
    }

    public function getRedirectorUrl()
    {
        return $this->bill();
    }

    public function rollback()
    {
        throw new \Exception('Not implemented yet');
    }

    public function billForWallet($amount, $user = null, $redirectUrl = null)
    {
        // TODO: Implement billForWallet() method.
    }

}