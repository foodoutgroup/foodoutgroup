<?php

namespace Food\OrderBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Food\OrderBundle\Service\Events\NavOrderEvent;
use Food\OrderBundle\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NavOrderSyncListener extends ContainerAware
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function onMarkForSync(NavOrderEvent $event = null)
    {
        $navService = $this->container->get('food.nav');

        $data = $navService->getOrderDataForNav($event->getOrder());
        $navService->touchOrderAccData($event->getOrder(), $data);
    }
}
