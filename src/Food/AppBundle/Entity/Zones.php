<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="nav_zones")
 * @ORM\Entity
 */
class Zones {

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
     * @ORM\Column(name="timestamp", type="string", length=30, nullable=true)
     */
    private $timestamp;

    /**
     * @var string
     * @ORM\Column(name="grid", type="string", length=50, nullable=true)
     */
    private $grid;

    /**
     * @var string
     * @ORM\Column(name="description", type="string", length=100, nullable=true)
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(name="zip_code", type="string", length=20, nullable=true)
     */
    private $zipCode;


    /**
     * @var string
     * @ORM\Column(name="delivery_price_code", type="string", length=20, nullable=true)
     */
    private $deliveryPriceCode;

    /**
     * @var string
     * @ORM\Column(name="delivery_company", type="string", length=50, nullable=true)
     */
    private $deliveryCompany;

    /**
     * @var string
     * @ORM\Column(name="delivery_region", type="string", length=50, nullable=true)
     */
    private $deliveryRegion;

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
     * Set grid
     *
     * @param string $grid
     * @return Zones
     */
    public function setGrid($grid)
    {
        $this->grid = $grid;
    
        return $this;
    }

    /**
     * Get grid
     *
     * @return string 
     */
    public function getGrid()
    {
        return $this->grid;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Zones
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set zipCode
     *
     * @param string $zipCode
     * @return Zones
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
    
        return $this;
    }

    /**
     * Get zipCode
     *
     * @return string 
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Set deliveryPriceCode
     *
     * @param string $deliveryPriceCode
     * @return Zones
     */
    public function setDeliveryPriceCode($deliveryPriceCode)
    {
        $this->deliveryPriceCode = $deliveryPriceCode;
    
        return $this;
    }

    /**
     * Get deliveryPriceCode
     *
     * @return string 
     */
    public function getDeliveryPriceCode()
    {
        return $this->deliveryPriceCode;
    }

    /**
     * Set deliveryCompany
     *
     * @param string $deliveryCompany
     * @return Zones
     */
    public function setDeliveryCompany($deliveryCompany)
    {
        $this->deliveryCompany = $deliveryCompany;
    
        return $this;
    }

    /**
     * Get deliveryCompany
     *
     * @return string 
     */
    public function getDeliveryCompany()
    {
        return $this->deliveryCompany;
    }

    /**
     * Set deliveryRegion
     *
     * @param string $deliveryRegion
     * @return Zones
     */
    public function setDeliveryRegion($deliveryRegion)
    {
        $this->deliveryRegion = $deliveryRegion;
    
        return $this;
    }

    /**
     * Get deliveryRegion
     *
     * @return string 
     */
    public function getDeliveryRegion()
    {
        return $this->deliveryRegion;
    }

    /**
     * Set timestamp
     *
     * @param string $timestamp
     * @return Zones
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    
        return $this;
    }

    /**
     * Get timestamp
     *
     * @return string 
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}