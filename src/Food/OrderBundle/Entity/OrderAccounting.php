<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orders_accounting")
 * @ORM\Entity
 */
class OrderAccounting
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
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="accounting")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     */
    private $order;

    /**
     * @var string Possible: MAISTAS, GERIMAI, ALKOHOLIS, PRISTAT
     * @ORM\Column(name="category", type="string", length=128)
     */
    private $category;

    /**
     * @var string Possible: VARDENIS#PAVARDENIS, ETAXI
     * @ORM\Column(name="driver", type="string", length=256)
     */
    private $driver;

    /**
     * @var int Possible: KORTELE, PAYSERA, GRYNI
     * @ORM\Column(name="paymentType", type="string", length=256)
     */
    private $paymentType;

    /**
     * @var int
     * @ORM\Column(name="vat", type="integer", length=5)
     */
    private $vat;

    /**
     * @var float
     * @ORM\Column(name="sum", type="float", length=10)
     */
    private $sum;


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
     * Set category
     *
     * @param string $category
     * @return OrderAccounting
     */
    public function setCategory($category)
    {
        $this->category = $category;
    
        return $this;
    }

    /**
     * Get category
     *
     * @return string 
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set driver
     *
     * @param string $driver
     * @return OrderAccounting
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
    
        return $this;
    }

    /**
     * Get driver
     *
     * @return string 
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Set paymentType
     *
     * @param string $paymentType
     * @return OrderAccounting
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;
    
        return $this;
    }

    /**
     * Get paymentType
     *
     * @return string 
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * Set vat
     *
     * @param integer $vat
     * @return OrderAccounting
     */
    public function setVat($vat)
    {
        $this->vat = $vat;
    
        return $this;
    }

    /**
     * Get vat
     *
     * @return integer 
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * Set sum
     *
     * @param float $sum
     * @return OrderAccounting
     */
    public function setSum($sum)
    {
        $this->sum = $sum;
    
        return $this;
    }

    /**
     * Get sum
     *
     * @return float 
     */
    public function getSum()
    {
        return $this->sum;
    }

    /**
     * Set order
     *
     * @param \Food\OrderBundle\Entity\Order $order
     * @return OrderAccounting
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