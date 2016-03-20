<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orders_to_logistics")
 * @ORM\Entity(repositoryClass="Food\OrderBundle\Entity\OrderToLogisticsRepository")
 */
class OrderToLogistics
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
     * @ORM\ManyToOne(targetEntity="\Food\OrderBundle\Entity\Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     **/
    private $order;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_added", type="datetime")
     */
    private $dateAdded;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_sent", type="datetime", nullable=true)
     */
    private $dateSent;

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=64, nullable=false)
     */
    private $status = 'unsent';

    /**
     * @var int
     * @ORM\Column(name="times_sent", type="integer", length=3)
     */
    private $timesSent = 0;

    /**
     * @var string
     * @ORM\Column(name="last_error", type="text")
     */
    private $lastError = '';

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
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return OrderToLogistics
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;
    
        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime 
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set dateSent
     *
     * @param \DateTime $dateSent
     * @return OrderToLogistics
     */
    public function setDateSent($dateSent)
    {
        $this->dateSent = $dateSent;
    
        return $this;
    }

    /**
     * Get dateSent
     *
     * @return \DateTime 
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @throws \InvalidArgumentException
     * @return OrderToLogistics
     */
    public function setStatus($status)
    {
        if (!in_array($status, array('sent', 'unsent', 'error'))) {
            throw new \InvalidArgumentException('Unknown OrderToLogistic status: '.$status);
        }
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set timesSend
     *
     * @param integer $timesSent
     * @return OrderToLogistics
     */
    public function setTimesSent($timesSent)
    {
        $this->timesSent = $timesSent;
    
        return $this;
    }

    /**
     * Get timesSend
     *
     * @return integer 
     */
    public function getTimesSent()
    {
        return $this->timesSent;
    }

    /**
     * Set lastError
     *
     * @param string $lastError
     * @return OrderToLogistics
     */
    public function setLastError($lastError)
    {
        $this->lastError = $lastError;
    
        return $this;
    }

    /**
     * Get lastError
     *
     * @return string 
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Set order
     *
     * @param \Food\OrderBundle\Entity\Order $order
     * @return OrderToLogistics
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
     * @return OrderToLogistics
     */
    public function markSent()
    {
        $this->setStatus('sent');

        return $this;
    }

    /**
     * @return OrderToLogistics
     */
    public function markUnsent()
    {
        $this->setStatus('unsent');

        return $this;
    }

    /**
     * @return OrderToLogistics
     */
    public function markError()
    {
        $this->setStatus('error');

        return $this;
    }
}
