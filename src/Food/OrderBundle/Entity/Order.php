<?php

namespace Food\OrderBundle\Entity;

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
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;

    /**
     * @var string
     * @ORM\Column(name="delivery_type", type="string", length=50, nullable=true)
     */
    private $deliveryType;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\UserAddress")
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     **/
    private $address_id;

    /**
     * @ORM\ManyToOne(targetEntity="OrderStatus")
     * @ORM\JoinColumn(name="order_status", referencedColumnName="id")
     **/
    private $order_status;

    /**
     * @var string
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment = null;

    /**
     * @var string
     * @ORM\Column(name="place_comment", type="text", nullable=true)
     */
    private $place_comment;

    /**
     * @var integer
     * @ORM\Column(name="vat", type="integer")
     */
    private $vat;

    /**
     * @var integer
     * @ORM\Column(name="order_hash", type="string", length=100)
     */
    private $order_hash;

    /**
     * @var string
     * @ORM\Column(name="payment_method", type="string", length=100, nullable=true)
     */
    private $paymentMethod = null;

    /**
     * @var string
     * @ORM\Column(name="payment_status", type="string")
     */
    private $paymentStatus = 'new';

    /**
     * @ORM\Column(name="submitted_for_payment", type="datetime", nullable=true)
     */
    private $submittedForPayment = null;

    /**
     * @ORM\Column(name="lastUpdated", type="datetime", nullable=true)
     */
    private $lastUpdated = null;

    /**
     * @var string
     * @ORM\Column(name="last_payment_error", type="string", nullable=true)
     */
    private $lastPaymentError = null;

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

    /**
     * Set vat
     *
     * @param integer $vat
     * @return Order
     */
    public function setVat($vat)
    {
        $this->vat = $vat;
    
        return $this;
    }

    /**
     * Get vat
     *
     * @return integer 
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * Set order_hash
     *
     * @param string $orderHash
     * @return Order
     */
    public function setOrderHash($orderHash)
    {
        $this->order_hash = $orderHash;
    
        return $this;
    }

    /**
     * Get order_hash
     *
     * @return string 
     */
    public function getOrderHash()
    {
        return $this->order_hash;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return Order
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    
        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set place_comment
     *
     * @param string $placeComment
     * @return Order
     */
    public function setPlaceComment($placeComment)
    {
        $this->place_comment = $placeComment;
    
        return $this;
    }

    /**
     * Get place_comment
     *
     * @return string 
     */
    public function getPlaceComment()
    {
        return $this->place_comment;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        // TODO susumuoti visu detailsu kainas ir grazinti ;)
        return '1.5';
    }

    /**
     * Set paymentStatus
     *
     * @param string $paymentStatus
     * @return Order
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;
    
        return $this;
    }

    /**
     * Get paymentStatus
     *
     * @return string 
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * Set lastPaymentError
     *
     * @param string $lastPaymentError
     * @return Order
     */
    public function setLastPaymentError($lastPaymentError)
    {
        $this->lastPaymentError = $lastPaymentError;
    
        return $this;
    }

    /**
     * Get lastPaymentError
     *
     * @return string 
     */
    public function getLastPaymentError()
    {
        return $this->lastPaymentError;
    }

    /**
     * Set paymentMethod
     *
     * @param string $paymentMethod
     * @return Order
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    
        return $this;
    }

    /**
     * Get paymentMethod
     *
     * @return string 
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * Set submittedForPayment
     *
     * @param \DateTime $submittedForPayment
     * @return Order
     */
    public function setSubmittedForPayment($submittedForPayment)
    {
        $this->submittedForPayment = $submittedForPayment;
    
        return $this;
    }

    /**
     * Get submittedForPayment
     *
     * @return \DateTime 
     */
    public function getSubmittedForPayment()
    {
        return $this->submittedForPayment;
    }

    /**
     * Set lastUpdated
     *
     * @param \DateTime $lastUpdated
     * @return Order
     */
    public function setLastUpdated($lastUpdated)
    {
        $this->lastUpdated = $lastUpdated;
    
        return $this;
    }

    /**
     * Get lastUpdated
     *
     * @return \DateTime 
     */
    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }

    /**
     * Set deliveryType
     *
     * @param string $deliveryType
     * @return Order
     */
    public function setDeliveryType($deliveryType)
    {
        $this->deliveryType = $deliveryType;
    
        return $this;
    }

    /**
     * Get deliveryType
     *
     * @return string 
     */
    public function getDeliveryType()
    {
        return $this->deliveryType;
    }
}