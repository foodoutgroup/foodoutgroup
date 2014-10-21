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
        $logger = $this->container->get('logger');

        // log
        $logger->alert('--====================================================');

        // get order
        $order = $this->getOrder();

        // log
        $logger->alert('++ Bandom bilinti orderi su Id: ' . $order->getId());
        $logger->alert('-------------------------------------');

        if (!($order instanceof Order)) {
            throw new \InvalidArgumentException('Sorry, You gave me ' .
                                                'someting, but not order :(');
        }

        $redirectUrl = $router->generate('swedbank_gateway_redirect',
                                         array('id' => $order->getId(),
                                               'locale' => $this->getLocale()));

        // log
        $logger->alert('-------------------------------------');
        $logger->alert('Suformuotas url: '.$redirectUrl);
        $logger->alert('-------------------------------------');

        return $redirectUrl;
    }
}
