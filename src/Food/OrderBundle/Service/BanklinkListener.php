<?php

namespace Food\OrderBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Pirminis\GatewayBundle\Services\Events\BanklinkEvent;
use Food\OrderBundle\Entity\BanklinkLog;
use Food\OrderBundle\Entity\Order;

class BanklinkListener extends ContainerAware
{
    protected $container;

    public function onBanklinkRequest(BanklinkEvent $event)
    {
        $event->setUserId($this->getUserId());
        $this->log($event);
    }

    public function onBanklinkResponse(BanklinkEvent $event)
    {
        $event->setUserId($this->getUserId());
        $this->log($event);
    }

    protected function getUserId()
    {
        $sc = $this->container->get('security.context');

        if ($sc->isGranted('ROLE_USER')) {
            return $sc->getToken()->getUser()->getId();
        }

        return null;
    }

    protected function log(BanklinkEvent $event)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $entry = new BanklinkLog();
        $entry->setOrderId($event->getOrderId())
              ->setUserId($event->getUserId())
              ->setEventDate(new \DateTime())
              ->setXml($event->getXml())
              ->setQuery(var_export($event->getQuery(), true))
              ->setRequest(var_export($event->getQuery(), true));

        $em->persist($entry);
        $em->flush();
    }
}
