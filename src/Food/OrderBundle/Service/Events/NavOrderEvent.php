<?php

namespace Food\OrderBundle\Service\Events;

use Symfony\Component\EventDispatcher\Event;
use Food\OrderBundle\Entity\Order;

class NavOrderEvent extends Event
{
    const LOG_ORDER = 'nav.order.log_for_sync';

    protected $order;

    public function __construct(Order $order = null)
    {
        $this->setOrder($order);
    }

    public function setOrder(Order $order = null)
    {
        $this->order = $order;
    }

    public function getOrder()
    {
        return $this->order;
    }
}
