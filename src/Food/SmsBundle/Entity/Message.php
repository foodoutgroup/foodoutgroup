<?php

namespace Food\SmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Food category
 *
 * @ORM\Table(name="sms_message")
 * @ORM\Entity
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
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="submitted_at", type="datetime", nullable=true)
     */
    private $submittedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="received_at", type="datetime", nullable=true)
     */
    private $receivedAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="dlr_id", type="integer", nullable=true)
     */
    private $dlrId;

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
     * Set dlrId
     *
     * @param integer $dlrId
     * @return Message
     */
    public function setDlrId($dlrId)
    {
        $this->dlrId = $dlrId;
    
        return $this;
    }

    /**
     * Get dlrId
     *
     * @return integer 
     */
    public function getDlrId()
    {
        return $this->dlrId;
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
}