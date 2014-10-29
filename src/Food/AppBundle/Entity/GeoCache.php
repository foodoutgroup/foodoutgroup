<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="geo_cache", options={"engine"="MyISAM"})
 * @ORM\Entity
 */
class GeoCache {

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="request_data", type="string", length=200, nullable=true)
     */
    private $requestData;

    /**
     * @var string
     * @ORM\Column(name="request_address", type="string", length=100, nullable=true)
     */
    private $requestAddress;

    /**
     * @var string
     * @ORM\Column(name="request_city", type="string", length=50, nullable=true)
     */
    private $requestCity;

    /**
     * @var string
     * @ORM\Column(name="request_country", type="string", length=50, nullable=true)
     */
    private $requestCountry;

    /**
     * @var string
     * @ORM\Column(name="response_body", type="text", nullable=true)
     */
    private $ressponseBody;

    /**
     * @ORM\Column(name="request_date", type="datetime")
     */
    private $requestDate;


    /**
     * @var int
     *
     * @ORM\Column(name="counter", type="integer")
     */
    private $counter;

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
     * Set requestData
     *
     * @param string $requestData
     * @return GeoCache
     */
    public function setRequestData($requestData)
    {
        $this->requestData = $requestData;
    
        return $this;
    }

    /**
     * Get requestData
     *
     * @return string 
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * Set requestAddress
     *
     * @param string $requestAddress
     * @return GeoCache
     */
    public function setRequestAddress($requestAddress)
    {
        $this->requestAddress = $requestAddress;
    
        return $this;
    }

    /**
     * Get requestAddress
     *
     * @return string 
     */
    public function getRequestAddress()
    {
        return $this->requestAddress;
    }

    /**
     * Set requestCity
     *
     * @param string $requestCity
     * @return GeoCache
     */
    public function setRequestCity($requestCity)
    {
        $this->requestCity = $requestCity;
    
        return $this;
    }

    /**
     * Get requestCity
     *
     * @return string 
     */
    public function getRequestCity()
    {
        return $this->requestCity;
    }

    /**
     * Set requestCountry
     *
     * @param string $requestCountry
     * @return GeoCache
     */
    public function setRequestCountry($requestCountry)
    {
        $this->requestCountry = $requestCountry;
    
        return $this;
    }

    /**
     * Get requestCountry
     *
     * @return string 
     */
    public function getRequestCountry()
    {
        return $this->requestCountry;
    }

    /**
     * Set ressponseBody
     *
     * @param string $ressponseBody
     * @return GeoCache
     */
    public function setRessponseBody($ressponseBody)
    {
        $this->ressponseBody = $ressponseBody;
    
        return $this;
    }

    /**
     * Get ressponseBody
     *
     * @return string 
     */
    public function getRessponseBody()
    {
        return $this->ressponseBody;
    }

    /**
     * Set requestDate
     *
     * @param \DateTime $requestDate
     * @return GeoCache
     */
    public function setRequestDate($requestDate)
    {
        $this->requestDate = $requestDate;
    
        return $this;
    }

    /**
     * Get requestDate
     *
     * @return \DateTime 
     */
    public function getRequestDate()
    {
        return $this->requestDate;
    }

    /**
     * Set counter
     *
     * @param integer $counter
     * @return GeoCache
     */
    public function setCounter($counter)
    {
        $this->counter = $counter;
    
        return $this;
    }

    /**
     * Get counter
     *
     * @return integer 
     */
    public function getCounter()
    {
        return $this->counter;
    }
}