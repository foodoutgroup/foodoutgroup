<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderEmail
 *
 * @ORM\Table(name="order_email_to_send")
 * @ORM\Entity(repositoryClass="Food\OrderBundle\Entity\OrderEmailRepository")
 */
class OrderEmail
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
     * @var integer
     *
     * @ORM\Column(name="order_id", type="integer",nullable=true)
     */
    private $orderId;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255,nullable=true)
     */
    private $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sent",type="boolean", options={"default"=false})
     */
    private $sent = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime",nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="send_on_date", type="datetime",nullable=true)
     */
    private $sendOnDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent_at", type="datetime",nullable=true)
     */
    private $sentAt;

    /**
     * @var string
     *
     * @ORM\Column(name="error", type="string", length=255,nullable=true)
     */
    private $error;

    /**
     * @var string
     *
     * @ORM\Column(name="template_id", type="string", length=255,nullable=true)
     */
    private $templateId;


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
     * Set orderId
     *
     * @param integer $orderId
     * @return OrderEmail
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return integer 
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return OrderEmail
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
     * Set sent
     *
     * @param string $sent
     * @return OrderEmail
     */
    public function setSent($sent)
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * Get sent
     *
     * @return string 
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return OrderEmail
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
     * Set sendOnDate
     *
     * @param \DateTime $sendOnDate
     * @return OrderEmail
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

    /**
     * Set sentAt
     *
     * @param \DateTime $sentAt
     * @return OrderEmail
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
     * Set error
     *
     * @param string $error
     * @return OrderEmail
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Get error
     *
     * @return string 
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set templateId
     *
     * @param string $templateId
     * @return OrderEmail
     */
    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;

        return $this;
    }

    /**
     * Get templateId
     *
     * @return string 
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }
}
