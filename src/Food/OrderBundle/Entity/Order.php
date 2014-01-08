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
     * @var text
     * @ORM\Column(name="comment", type="text")
     */
    private $comment;

    /**
     * @var text
     * @ORM\Column(name="place_comment", type="text")
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
        return '15.5';
    }
}