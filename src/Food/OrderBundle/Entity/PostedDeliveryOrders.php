<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="nav_posted_delivery_orders")
 * @ORM\Entity
 */
class PostedDeliveryOrders {

    /**
     * @var string
     * @ORM\Column(name="no", type="string", length=20)
     * @ORM\Id
     */
    private $no;

    /**
     * @ORM\Column(name="order_date", type="datetime")
     */
    private $order_date;

    /**
     * @var float
     * @ORM\Column(name="total", type="decimal", precision=8, scale=2, nullable=true)
     */
    private $total;

    /**
     * @var float
     * @ORM\Column(name="delivery", type="decimal", precision=8, scale=2, nullable=true)
     */
    private $delivery;

    /**
     * Set no
     *
     * @param string $no
     * @return PostedDeliveryOrders
     */
    public function setNo($no)
    {
        $this->no = $no;

        return $this;
    }

    /**
     * Get no
     *
     * @return string
     */
    public function getNo()
    {
        return $this->no;
    }

    /**
     * Set order_date
     *
     * @param \DateTime $orderDate
     * @return PostedDeliveryOrders
     */
    public function setOrderDate($orderDate)
    {
        $this->order_date = $orderDate;

        return $this;
    }

    /**
     * Get order_date
     *
     * @return \DateTime
     */
    public function getOrderDate()
    {
        return $this->order_date;
    }

    /**
     * Set total
     *
     * @param string $total
     * @return PostedDeliveryOrders
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return string
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set delivery
     *
     * @param string $delivery
     * @return PostedDeliveryOrders
     */
    public function setDelivery($delivery)
    {
        $this->delivery = $delivery;
    
        return $this;
    }

    /**
     * Get delivery
     *
     * @return string 
     */
    public function getDelivery()
    {
        return $this->delivery;
    }
}