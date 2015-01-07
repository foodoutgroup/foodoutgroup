<?php

namespace Food\OrderBundle\Service\Events;

use Symfony\Component\EventDispatcher\Event;

class SoapFaultEvent extends Event
{
    const SOAP_FAULT = 'food.soap.fault';

    protected $exception;

    public function __construct(\SoapFault $e = null)
    {
        $this->setException($e);
    }

    public function setException(\SoapFault $e = null)
    {
        $this->exception = $e;
    }

    public function getException()
    {
        return $this->exception;
    }
}
