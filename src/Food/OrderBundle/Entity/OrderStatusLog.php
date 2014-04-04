<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="order_status_log")
 * @ORM\Entity
 */
class OrderStatusLog
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
     * @ORM\ManyToOne(targetEntity="Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     */
    private $order;

    /**
     * @var \DateTime
     * @ORM\Column(name="event_date", type="datetime")
     */
    private $event_date;

    /**
     * @var string
     * @ORM\Column(name="old_status", type="string", length=50)
     */
    private $oldStatus;

    /**
     * @var string
     * @ORM\Column(name="new_status", type="string", length=50)
     */
    private $newStatus;

    /**
     * @var string
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    public function __construct()
    {
        $this->event_date = new \DateTime("now");
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
     * Set event_date
     *
     * @param \DateTime $eventDate
     * @return OrderStatusLog
     */
    public function setEventDate($eventDate)
    {
        $this->event_date = $eventDate;
    
        return $this;
    }

    /**
     * Get event_date
     *
     * @return \DateTime 
     */
    public function getEventDate()
    {
        return $this->event_date;
    }

    /**
     * Set oldStatus
     *
     * @param string $oldStatus
     * @return OrderStatusLog
     */
    public function setOldStatus($oldStatus)
    {
        $this->oldStatus = $oldStatus;
    
        return $this;
    }

    /**
     * Get oldStatus
     *
     * @return string 
     */
    public function getOldStatus()
    {
        return $this->oldStatus;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return OrderStatusLog
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
     * Set order
     *
     * @param \Food\OrderBundle\Entity\Order $order
     * @return OrderStatusLog
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
     * Set newStatus
     *
     * @param string $newStatus
     * @return OrderStatusLog
     */
    public function setNewStatus($newStatus)
    {
        $this->newStatus = $newStatus;
    
        return $this;
    }

    /**
     * Get newStatus
     *
     * @return string 
     */
    public function getNewStatus()
    {
        return $this->newStatus;
    }
}