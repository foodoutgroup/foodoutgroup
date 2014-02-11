<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="payment_log")
 * @ORM\Entity
 */
class PaymentLog
{
    /**
     * @ORM\ManyToOne(targetEntity="Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $order;

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
     * @ORM\Column(name="payment_status", type="text", length=50, nullable=true)
     */
    private $payment_status;

    /**
     * @var string
     * @ORM\Column(name="debug_data", type="text", nullable=true)
     */
    private $debug_data;

    /**
     * Set event_date
     *
     * @param \DateTime $eventDate
     * @return PaymentLog
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
     * @return PaymentLog
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
     * Set payment_status
     *
     * @param string $paymentStatus
     * @return PaymentLog
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->payment_status = $paymentStatus;
    
        return $this;
    }

    /**
     * Get payment_status
     *
     * @return string 
     */
    public function getPaymentStatus()
    {
        return $this->payment_status;
    }

    /**
     * Set debug_data
     *
     * @param string $debugData
     * @return PaymentLog
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
     * @return PaymentLog
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
     * @return PaymentLog
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
}
