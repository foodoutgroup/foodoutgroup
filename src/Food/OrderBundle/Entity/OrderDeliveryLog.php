<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="order_delivery_log")
 * @ORM\Entity
 */
class OrderDeliveryLog
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
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="orderStatusLog")
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
     * @ORM\Column(name="event", type="string", length=70)
     */
    private $event;

    /**
     * @var string
     * @ORM\Column(name="since_last", type="integer", length=10)
     */
    private $since_last;

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
     * Set event
     *
     * @param string $event
     * @return OrderDeliveryLog
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
     * Set order
     *
     * @param \Food\OrderBundle\Entity\Order $order
     * @return OrderDeliveryLog
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
     * Counts difference in minutes
     *
     * @param \DateTime $firstEvent
     * @param \DateTime $secondEvent
     *
     * @return integer
     */
    public function getDiff($firstEvent, $secondEvent)
    {
        $interval = $firstEvent->diff($secondEvent, true);
        return $interval->format('%i');
    }

    /**
     * Set since_last
     *
     * @param integer $sinceLast
     * @return OrderDeliveryLog
     */
    public function setSinceLast($sinceLast)
    {
        $this->since_last = $sinceLast;
    
        return $this;
    }

    /**
     * Get since_last
     *
     * @return integer 
     */
    public function getSinceLast()
    {
        return $this->since_last;
    }
}