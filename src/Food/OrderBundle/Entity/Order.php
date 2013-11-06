<?php

namespace Food\OrderBundle\Entity;

use Symfony\Bridge\Doctrine;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orders")
 * @ORM\Entity
 */
class Order
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="order_date", type="datetime")
     */
    private $order_date;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User", inversedBy="user")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\UserAddress", inversedBy="address_id")
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     **/
    private $address_id;

    /**
     * @ORM\ManyToOne(targetEntity="OrderStatus", inversedBy="order_status")
     * @ORM\JoinColumn(name="order_status", referencedColumnName="id")
     **/
    private $order_status;



    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set order_date
     *
     * @param \DateTime $orderDate
     * @return Order
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
     * Set user
     *
     * @param \Food\UserBundle\Entity\User $user
     * @return Order
     */
    public function setUser(\Food\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \Food\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set address_id
     *
     * @param \Food\UserBundle\Entity\UserAddress $addressId
     * @return Order
     */
    public function setAddressId(\Food\UserBundle\Entity\UserAddress $addressId = null)
    {
        $this->address_id = $addressId;
    
        return $this;
    }

    /**
     * Get address_id
     *
     * @return \Food\UserBundle\Entity\UserAddress 
     */
    public function getAddressId()
    {
        return $this->address_id;
    }

    /**
     * Set order_status
     *
     * @param \Food\OrderBundle\Entity\OrderStatus $orderStatus
     * @return Order
     */
    public function setOrderStatus(\Food\OrderBundle\Entity\OrderStatus $orderStatus = null)
    {
        $this->order_status = $orderStatus;
    
        return $this;
    }

    /**
     * Get order_status
     *
     * @return \Food\OrderBundle\Entity\OrderStatus 
     */
    public function getOrderStatus()
    {
        return $this->order_status;
    }
}