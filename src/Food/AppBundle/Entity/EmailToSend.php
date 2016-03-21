<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Food\OrderBundle\Entity\Order;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Food\AppBundle\Entity\EmailToSend
 *
 * @ORM\Table(name="emails_to_send", indexes={@ORM\Index(name="sent_idx", columns={"sent"})})
 * @ORM\Entity
 */
class EmailToSend
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="\Food\OrderBundle\Entity\Order", inversedBy="smsMessages")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=true)
     */
    private $order;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=45)
     */
    private $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sent", type="boolean")
     */
    private $sent = false;

    /**
     * @var null|string
     *
     * @ORM\Column(name="error", type="string", length=160, nullable=true)
     */
    private $error = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="send_on_date", type="datetime")
     */
    private $sendOnDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent_at", type="datetime", nullable=true)
     */
    private $sentAt;

    /**
     * @ORM\Version
     * @ORM\Column(type="integer")
     */
    private $version;

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->getId()) {
            return $this->getId().'-'.$this->getType().'-'.$this->getOrder()->getId();
        }

        return '';
    }

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
     * Set type
     *
     * @param string $type
     * @return EmailToSend
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set version
     *
     * @param integer $version
     * @return EmailToSend
     */
    public function setVersion($version)
    {
        $this->version = $version;
    
        return $this;
    }

    /**
     * Get version
     *
     * @return integer 
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set order
     *
     * @param \Food\OrderBundle\Entity\Order $order
     * @return EmailToSend
     */
    public function setOrder(\Food\OrderBundle\Entity\Order $order = null)
    {
        $this->order = $order;
    
        return $this;
    }

    /**
     * Get order
     *
     * @return \Food\OrderBundle\Entity\Order 
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set sent
     *
     * @param boolean $sent
     * @return EmailToSend
     */
    public function setSent($sent)
    {
        $this->sent = $sent;
    
        return $this;
    }

    /**
     * Get sent
     *
     * @return boolean 
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * @return null|string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param string|null $error
     *
     * @return EmailToSend
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return EmailToSend
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set sentAt
     *
     * @param \DateTime $sentAt
     * @return EmailToSend
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;
    
        return $this;
    }

    /**
     * Get sentAt
     *
     * @return \DateTime 
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * Set sendOnDate
     *
     * @param \DateTime $sendOnDate
     * @return EmailToSend
     */
    public function setSendOnDate($sendOnDate)
    {
        $this->sendOnDate = $sendOnDate;
    
        return $this;
    }

    /**
     * Get sendOnDate
     *
     * @return \DateTime 
     */
    public function getSendOnDate()
    {
        return $this->sendOnDate;
    }
}