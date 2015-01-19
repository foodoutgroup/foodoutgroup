<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InvoiceToSendNavOnly
 *
 * @ORM\Table(name="invoice_to_send_nav_only")
 * @ORM\Entity
 */
class InvoiceToSendNavOnly
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
     *
     * @ORM\Column(name="date_added", type="datetime")
     */
    private $dateAdded;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_sent", type="datetime", nullable=true)
     */
    private $dateSent;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=64, nullable=false)
     */
    private $status = 'unsent';

    /**
     * @var string
     *
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
     * Set orderId
     *
     * @param integer $orderId
     * @return InvoiceToSendNavOnly
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
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return InvoiceToSendNavOnly
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
     * @return InvoiceToSendNavOnly
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
     * @return InvoiceToSendNavOnly
     */
    public function setStatus($status)
    {
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
     * Set lastError
     *
     * @param string $lastError
     * @return InvoiceToSendNavOnly
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
     * @return InvoiceToSendNavOnly
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
     * @return InvoiceToSend
     */
    public function markSent()
    {
        $this->setStatus('sent');

        return $this;
    }

    /**
     * @return InvoiceToSend
     */
    public function markUnsent()
    {
        $this->setStatus('unsent');

        return $this;
    }

    /**
     * @return InvoiceToSend
     */
    public function markError()
    {
        $this->setStatus('error');

        return $this;
    }
}
