<?php

namespace Food\SmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Food\OrderBundle\Entity\Order;

/**
 * Food category
 *
 * @ORM\Table(name="sms_message")
 * @ORM\Entity(repositoryClass="Food\SmsBundle\Entity\MessageRepository")
 */
class Message
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
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="\Food\OrderBundle\Entity\Order", inversedBy="smsMessages")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=true)
     */
    private $order;

    /**
     * @var string
     *
     * @ORM\Column(name="sender", type="string", length=45)
     */
    private $sender;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient", type="string", length=45)
     */
    private $recipient;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text")
     */
    private $message = '';

    /**
     * @var string
     *
     * @ORM\Column(name="smsc", type="string", length=45, nullable=true)
     */
    private $smsc = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="submitted_at", type="datetime", nullable=true)
     */
    private $submittedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="received_at", type="datetime", nullable=true)
     */
    private $receivedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_error_date", type="datetime", nullable=true)
     */
    private $lastErrorDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="last_sending_error", type="string", nullable=true)
     */
    private $lastSendingError = null;

    /**
     * @var int
     *
     * @ORM\Column(name="times_sent", type="integer")
     */
    private $timesSent = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sent", type="boolean")
     */
    private $sent = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="delivered", type="boolean")
     */
    private $delivered = false;

    /**
     * @var string
     *
     * @ORM\Column(name="ext_id", type="string", length=45, nullable=true)
     */
    private $extId = null;
    /**
     * @var string
     *
     * @ORM\Column(name="secondary_ext_id", type="string", length=45, nullable=true)
     */
    private $secondaryExtId = null;

    function __construct()
    {
        if (!isset($this->createdAt)) {
            $this->createdAt = new \DateTime("now");
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }
        return $this->getRecipient().'-'.$this->getId();
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
     * Set sender
     *
     * @param string $sender
     * @return Message
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
    
        return $this;
    }

    /**
     * Get sender
     *
     * @return string 
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Set recipient
     *
     * @param string $recipient
     * @return Message
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
    
        return $this;
    }

    /**
     * Get recipient
     *
     * @return string 
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    
        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Message
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
     * Set submittedAt
     *
     * @param \DateTime $submittedAt
     * @return Message
     */
    public function setSubmittedAt($submittedAt)
    {
        $this->submittedAt = $submittedAt;
    
        return $this;
    }

    /**
     * Get submittedAt
     *
     * @return \DateTime 
     */
    public function getSubmittedAt()
    {
        return $this->submittedAt;
    }

    /**
     * Set sent
     *
     * @param boolean $sent
     * @return Message
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
     * Set delivered
     *
     * @param boolean $delivered
     * @return Message
     */
    public function setDelivered($delivered)
    {
        $this->delivered = $delivered;
    
        return $this;
    }

    /**
     * Get delivered
     *
     * @return boolean 
     */
    public function getDelivered()
    {
        return $this->delivered;
    }

    /**
     * Set receivedAt
     *
     * @param \DateTime $receivedAt
     * @return Message
     */
    public function setReceivedAt($receivedAt)
    {
        $this->receivedAt = $receivedAt;
    
        return $this;
    }

    /**
     * Get receivedAt
     *
     * @return \DateTime 
     */
    public function getReceivedAt()
    {
        return $this->receivedAt;
    }

    /**
     * Set lastSendingError
     *
     * @param string $lastSendingError
     * @return Message
     */
    public function setLastSendingError($lastSendingError)
    {
        $this->lastSendingError = $lastSendingError;
    
        return $this;
    }

    /**
     * Get lastSendingError
     *
     * @return string 
     */
    public function getLastSendingError()
    {
        return $this->lastSendingError;
    }

    /**
     * Set extId
     *
     * @param string $extId
     * @return Message
     */
    public function setExtId($extId)
    {
        $this->extId = $extId;
    
        return $this;
    }

    /**
     * Get extId
     *
     * @return string 
     */
    public function getExtId()
    {
        return $this->extId;
    }

    /**
     * Set lastErrorDate
     *
     * @param \DateTime $lastErrorDate
     * @return Message
     */
    public function setLastErrorDate($lastErrorDate)
    {
        $this->lastErrorDate = $lastErrorDate;
    
        return $this;
    }

    /**
     * Get lastErrorDate
     *
     * @return \DateTime 
     */
    public function getLastErrorDate()
    {
        return $this->lastErrorDate;
    }

    /**
     * Set timesSent
     *
     * @param integer $timesSent
     * @return Message
     */
    public function setTimesSent($timesSent)
    {
        $this->timesSent = $timesSent;
    
        return $this;
    }

    /**
     * Get timesSent
     *
     * @return integer 
     */
    public function getTimesSent()
    {
        return $this->timesSent;
    }

    /**
     * Set smsc
     *
     * @param string $smsc
     * @return Message
     */
    public function setSmsc($smsc)
    {
        $this->smsc = $smsc;
    
        return $this;
    }

    /**
     * Get smsc
     *
     * @return string 
     */
    public function getSmsc()
    {
        return $this->smsc;
    }

    /**
     * Set secondaryExtId
     *
     * @param string $secondaryExtId
     * @return Message
     */
    public function setSecondaryExtId($secondaryExtId)
    {
        $this->secondaryExtId = $secondaryExtId;
    
        return $this;
    }

    /**
     * Get secondaryExtId
     *
     * @return string 
     */
    public function getSecondaryExtId()
    {
        return $this->secondaryExtId;
    }

    /**
     * Set order
     *
     * @param \Food\OrderBundle\Entity\Order $order
     * @return Message
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
}