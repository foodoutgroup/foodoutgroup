<?php

namespace Food\OrderBundle\Service\Events;

use Symfony\Component\EventDispatcher\Event;
use Food\UserBundle\Entity\User;

class BanklinkEvent extends Event
{
    const BANKLINK_REQUEST = 'banklink.request';
    const BANKLINK_RESPONSE = 'banklink.response';

    protected $orderId;
    protected $userId;
    protected $xml;
    protected $query;
    protected $request;

    public function __construct($orderId = 0,
                                $userId = 0,
                                $xml = '',
                                $query = '',
                                $request = '')
    {
        $this->setOrderId($orderId);
        $this->setUserId($userId);
        $this->setXml($xml);
        $this->setQuery($query);
        $this->setRequest($request);
    }

    public function setOrderId($orderId = 0)
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function setUserId($userId = 0)
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setXml($xml)
    {
        $xml = preg_replace('/(\<client\>)(.*?)(\<\/client\>)/i', '\1***\3', $xml);
        $xml = preg_replace('/(\<password\>)(.*?)(\<\/password\>)/i', '\1***\3', $xml);
        $this->xml = $xml;
        return $this;
    }

    public function getXml()
    {
        return $this->xml;
    }

    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    public function getRequest()
    {
        return $this->request;
    }
}
