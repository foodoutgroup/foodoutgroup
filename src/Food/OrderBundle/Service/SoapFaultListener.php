<?php

namespace Food\OrderBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Food\OrderBundle\Service\Events\SoapFaultEvent;

class SoapFaultListener extends ContainerAware
{
    protected $container;

    // SoapFaultEvent::SOAP_FAULT, food.soap.fault
    public function onSoapFault(SoapFaultEvent $event)
    {
        $exception = $event->getException();

        $message = sprintf('%s:%d: %s',
                           $exception->getFile(),
                           $exception->getLine(),
                           $exception->getMessage());

        $this->container->get('logger')->crit($message);
    }
}
