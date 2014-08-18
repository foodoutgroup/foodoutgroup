<?php

namespace Food\OrderBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Food\OrderBundle\Entity\Order;

class SwedbankGatewayBiller extends ContainerAware
{
    private $order;
    private $locale;

    public function setOrder($order)
    {
        $this->order = $order;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function bill()
    {
        // services
        $router = $this->container->get('router');

        // get order
        $order = $this->getOrder();

        if (!($order instanceof Order)) {
            throw new \InvalidArgumentException('Sorry, You gave me ' .
                                                'someting, but not order :(');
        }

        $redirectUrl = $router->generate('swedbank_gateway_redirect',
                                         array('id' => $order->getId(),
                                               'locale' => $this->getLocale()));

        return $redirectUrl;
    }
}
