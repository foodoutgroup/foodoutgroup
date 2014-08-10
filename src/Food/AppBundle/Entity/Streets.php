<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="nav_streets")
 * @ORM\Entity
 */
class Streets {

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
     * @ORM\Column(name="street_name", type="string", length=100, nullable=true)
     */
    private $streetName;

    /**
     * @var string
     * @ORM\Column(name="number_from", type="string", length=4, nullable=true)
     */
    private $numberFrom;

    /**
     * @var string
     * @ORM\Column(name="zip_code", type="string", length=20, nullable=true)
     */
    private $zipCode;

    /**
     * @var string
     * @ORM\Column(name="number_to", type="string", length=4, nullable=true)
     */
    private $numberTo;

    /**
     * @var string
     * @ORM\Column(name="grid", type="string", length=10, nullable=true)
     */
    private $grid;

    /**
     * @var string
     * @ORM\Column(name="restaurant", type="string", length=10, nullable=true)
     */
    private $restaurant;

    /**
     * @var string
     * @ORM\Column(name="street_name2", type="string", length=100, nullable=true)
     */
    private $streetName2;

    /**
     * @var string
     * @ORM\Column(name="delivery_time", type="string", length=5, nullable=true)
     */
    private $deliveryTime;

    /**
     * @var string
     * @ORM\Column(name="x", type="string", length=100, nullable=true)
     */
    private $varX;

    /**
     * @var string
     * @ORM\Column(name="y", type="string", length=100, nullable=true)
     */
    private $varY;

    /**
     * @var string
     * @ORM\Column(name="plink", type="string", length=100, nullable=true)
     */
    private $pLink;

    /**
     * @var string
     * @ORM\Column(name="pdist", type="string", length=100, nullable=true)
     */
    private $pDist;

    /**
     * @var string
     * @ORM\Column(name="importer", type="string", length=100, nullable=true)
     */
    private $importer;

    /**
     * @var string
     * @ORM\Column(name="pnode", type="string", length=100, nullable=true)
     */
    private $pNode;

    /**
     * @var string
     * @ORM\Column(name="delivery_price_code", type="string", length=100, nullable=true)
     */
    private $deliveryPriceCode;

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
     * Set timestamp
     *
     * @param string $timestamp
     * @return Streets
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

    /**
     * Set streetName
     *
     * @param string $streetName
     * @return Streets
     */
    public function setStreetName($streetName)
    {
        $this->streetName = $streetName;
    
        return $this;
    }

    /**
     * Get streetName
     *
     * @return string 
     */
    public function getStreetName()
    {
        return $this->streetName;
    }

    /**
     * Set numberFrom
     *
     * @param string $numberFrom
     * @return Streets
     */
    public function setNumberFrom($numberFrom)
    {
        $this->numberFrom = $numberFrom;
    
        return $this;
    }

    /**
     * Get numberFrom
     *
     * @return string 
     */
    public function getNumberFrom()
    {
        return $this->numberFrom;
    }

    /**
     * Set zipCode
     *
     * @param string $zipCode
     * @return Streets
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
     * Set numberTo
     *
     * @param string $numberTo
     * @return Streets
     */
    public function setNumberTo($numberTo)
    {
        $this->numberTo = $numberTo;
    
        return $this;
    }

    /**
     * Get numberTo
     *
     * @return string 
     */
    public function getNumberTo()
    {
        return $this->numberTo;
    }

    /**
     * Set grid
     *
     * @param string $grid
     * @return Streets
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
     * Set restaurant
     *
     * @param string $restaurant
     * @return Streets
     */
    public function setRestaurant($restaurant)
    {
        $this->restaurant = $restaurant;
    
        return $this;
    }

    /**
     * Get restaurant
     *
     * @return string 
     */
    public function getRestaurant()
    {
        return $this->restaurant;
    }

    /**
     * Set streetName2
     *
     * @param string $streetName2
     * @return Streets
     */
    public function setStreetName2($streetName2)
    {
        $this->streetName2 = $streetName2;
    
        return $this;
    }

    /**
     * Get streetName2
     *
     * @return string 
     */
    public function getStreetName2()
    {
        return $this->streetName2;
    }

    /**
     * Set deliveryTime
     *
     * @param string $deliveryTime
     * @return Streets
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->deliveryTime = $deliveryTime;
    
        return $this;
    }

    /**
     * Get deliveryTime
     *
     * @return string 
     */
    public function getDeliveryTime()
    {
        return $this->deliveryTime;
    }

    /**
     * Set varX
     *
     * @param string $varX
     * @return Streets
     */
    public function setVarX($varX)
    {
        $this->varX = $varX;
    
        return $this;
    }

    /**
     * Get varX
     *
     * @return string 
     */
    public function getVarX()
    {
        return $this->varX;
    }

    /**
     * Set varY
     *
     * @param string $varY
     * @return Streets
     */
    public function setVarY($varY)
    {
        $this->varY = $varY;
    
        return $this;
    }

    /**
     * Get varY
     *
     * @return string 
     */
    public function getVarY()
    {
        return $this->varY;
    }

    /**
     * Set pLink
     *
     * @param string $pLink
     * @return Streets
     */
    public function setPLink($pLink)
    {
        $this->pLink = $pLink;
    
        return $this;
    }

    /**
     * Get pLink
     *
     * @return string 
     */
    public function getPLink()
    {
        return $this->pLink;
    }

    /**
     * Set pDist
     *
     * @param string $pDist
     * @return Streets
     */
    public function setPDist($pDist)
    {
        $this->pDist = $pDist;
    
        return $this;
    }

    /**
     * Get pDist
     *
     * @return string 
     */
    public function getPDist()
    {
        return $this->pDist;
    }

    /**
     * Set importer
     *
     * @param string $importer
     * @return Streets
     */
    public function setImporter($importer)
    {
        $this->importer = $importer;
    
        return $this;
    }

    /**
     * Get importer
     *
     * @return string 
     */
    public function getImporter()
    {
        return $this->importer;
    }

    /**
     * Set pNode
     *
     * @param string $pNode
     * @return Streets
     */
    public function setPNode($pNode)
    {
        $this->pNode = $pNode;
    
        return $this;
    }

    /**
     * Get pNode
     *
     * @return string 
     */
    public function getPNode()
    {
        return $this->pNode;
    }

    /**
     * Set deliveryPriceCode
     *
     * @param string $deliveryPriceCode
     * @return Streets
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
}