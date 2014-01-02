<?php

namespace Food\OrderBundle\Service;

use Food\OrderBundle\Entity\Order;
use Symfony\Component\Security\Acl\Exception\Exception;

class OrderService
{
    public static $status_new = "new";
    public static $status_accepted = "accepted";
    public static $status_forwarded = "forwarded";
    public static $status_completed = "completed";
    public static $status_finished = "finished";
    public static $status_canceled = "canceled";

    /**
     * @var Order
     */
    private $order;

    /**
     * @return Order
     */
    public function createOrder()
    {
        $this->order = new Order();
        return $this->getOrder();
    }

    /**
     * @param $status
     */
    protected function chageOrderStatus($status)
    {
        $this->getOrder()->setOrderStatus($status);
    }

    /**
     * @return $this
     */
    public function statusNew()
    {
        $this->chageOrderStatus(self::$status_new);
        return $this;
    }

    /**
     * @return $this
     */
    public function statusAccepted()
    {
        $this->chageOrderStatus(self::$status_accepted);
        return $this;
    }

    /**
     * @return $this
     */
    public function statusForwarded()
    {
        $this->chageOrderStatus(self::$status_forwarded);
        return $this;
    }

    /**
     * @return $this
     */
    public function statusCompleted()
    {
        $this->chageOrderStatus(self::$status_completed);
        return $this;
    }

    /**
     * @return $this
     */
    public function statusFinished()
    {
        $this->chageOrderStatus(self::$status_finished);
        return $this;
    }

    /**
     * @return $this
     */
    public function statusCanceled()
    {
        $this->chageOrderStatus(self::$status_canceled);
        return $this;
    }


    /**
     * @return Order
     * @throws \Symfony\Component\Security\Acl\Exception\Exception
     */
    public function getOrder()
    {
        if (empty($this->order))
        {
            throw new Exception("Dude - no order here :)");
        }
        return $this->order;
    }

    /**
     * @param $id
     */
    public function getOrderById($id)
    {

    }

    public function getOrderByHash($hash)
    {

    }
}