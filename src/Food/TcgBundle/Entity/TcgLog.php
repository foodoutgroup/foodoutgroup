<?php

namespace Food\TcgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Food\OrderBundle\Entity\Order;
/**
 * TcgLog
 *
 * @ORM\Table(name="tcg_log")
 * @ORM\Entity(repositoryClass="Food\TcgBundle\Entity\TcgLogRepository")
 */
class TcgLog
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
     * @ORM\ManyToOne(targetEntity="\Food\OrderBundle\Entity\Order", inversedBy="pushMessages")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=true)
     */
    private $order;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="submitted_at", type="datetime")
     */
    private $submittedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="error", type="string", length=255)
     */
    private $error;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sent", type="boolean", options={"default"=false})
     */
    private $sent;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=255)
     */
    private $phone;


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
     * @return TcgLog
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return TcgLog
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
     * @return TcgLog
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
     * Set error
     *
     * @param string $error
     * @return TcgLog
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
     * @param string $sent
     * @return TcgLog
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
     * Set phone
     *
     * @param string $phone
     * @return TcgLog
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set order
     *
     * @param \Food\OrderBundle\Entity\Order $order
     * @return TcgLog
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
