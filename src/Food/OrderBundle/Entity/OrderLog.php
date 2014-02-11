<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="order_log")
 * @ORM\Entity
 */
class OrderLog
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
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;

    /**
     * @var \DateTime
     * @ORM\Column(name="event_date", type="datetime")
     */
    private $event_date;

    /**
     * @var string
     * @ORM\Column(name="event", type="string", length=50)
     */
    private $event;

    /**
     * @var string
     * @ORM\Column(name="message", type="string", nullable=true)
     */
    private $message;

    /**
     * @var string
     * @ORM\Column(name="order_status", type="string", length=50, nullable=true)
     */
    private $order_status;

    /**
     * @var string
     * @ORM\Column(name="debug_data", type="text", nullable=true)
     */
    private $debug_data;

    public function __construct()
    {
        $this->event_date = new \DateTime("now");
    }

    /**
     * Set event_date
     *
     * @param \DateTime $eventDate
     * @return OrderLog
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
     * Set event
     *
     * @param string $event
     * @return OrderLog
     */
    public function setEvent($event)
    {
        $this->event = $event;
    
        return $this;
    }

    /**
     * Get event
     *
     * @return string 
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set order_status
     *
     * @param string $orderStatus
     * @return OrderLog
     */
    public function setOrderStatus($orderStatus)
    {
        $this->order_status = $orderStatus;
    
        return $this;
    }

    /**
     * Get order_status
     *
     * @return string 
     */
    public function getOrderStatus()
    {
        return $this->order_status;
    }

    /**
     * Set debug_data
     *
     * @param string $debugData
     * @return OrderLog
     */
    public function setDebugData($debugData)
    {
        $this->debug_data = $debugData;
    
        return $this;
    }

    /**
     * Get debug_data
     *
     * @return string 
     */
    public function getDebugData()
    {
        return $this->debug_data;
    }

    /**
     * Set order
     *
     * @param \Food\OrderBundle\Entity\Order $order
     * @return OrderLog
     */
    public function setOrder(\Food\OrderBundle\Entity\Order $order)
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
     * Set message
     *
     * @param string $message
     * @return OrderLog
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
     * Set user
     *
     * @param \Food\UserBundle\Entity\User $user
     * @return OrderLog
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
}