<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SingalPush
 *
 * @ORM\Table(name="signal_push")
 * @ORM\Entity(repositoryClass="Food\OrderBundle\Entity\SignalPushRepository")
 */
class SingalPush
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
     * @ORM\Column(name="token", type="string", length=255)
     */
    private $token;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=255)
     */
    private $message;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="submited_at", type="datetime")
     */
    private $submitedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="error", type="string", length=255)
     */
    private $error;

    /**
     * @var string
     *
     * @ORM\Column(name="sent", type="boolean")
     */
    private $sent;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="integer")
     */
    private $orderId;


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
     * Set token
     *
     * @param string $token
     * @return SingalPush
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return SingalPush
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
     * @return SingalPush
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
     * Set submitedAt
     *
     * @param \DateTime $submitedAt
     * @return SingalPush
     */
    public function setSubmitedAt($submitedAt)
    {
        $this->submitedAt = $submitedAt;

        return $this;
    }

    /**
     * Get submitedAt
     *
     * @return \DateTime 
     */
    public function getSubmitedAt()
    {
        return $this->submitedAt;
    }

    /**
     * Set error
     *
     * @param string $error
     * @return SingalPush
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
     * Set sent
     *
     * @param integer $sent
     * @return SingalPush
     */
    public function setSent($sent)
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * Get sent
     *
     * @return integer
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * Set orderId
     *
     * @param integer $orderId
     * @return SingalPush
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
}
