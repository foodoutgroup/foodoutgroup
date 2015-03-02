<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="invoice_to_send")
 * @ORM\Entity(repositoryClass="Food\OrderBundle\Entity\InvoiceToSendRepository")
 */
class InvoiceToSend
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
     * @var string
     * @ORM\Column(name="last_error", type="text")
     */
    private $lastError = '';

    /**
     * @var bool
     * @ORM\Column(name="delete_from_nav", type="boolean", nullable=true)
     */
    private $deleteFromNav = false;

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

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return InvoiceToSend
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
     * @return InvoiceToSend
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
     * @return InvoiceToSend
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
     * @return InvoiceToSend
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
     * @return InvoiceToSend
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
     * Set deleteFromNav
     *
     * @param boolean $deleteFromNav
     * @return InvoiceToSend
     */
    public function setDeleteFromNav($deleteFromNav)
    {
        $this->deleteFromNav = $deleteFromNav;
    
        return $this;
    }

    /**
     * Get deleteFromNav
     *
     * @return boolean 
     */
    public function getDeleteFromNav()
    {
        return $this->deleteFromNav;
    }
}