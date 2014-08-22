<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="nav_codes")
 * @ORM\Entity
 */
class Codes {

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
     * @ORM\Column(name="code", type="string", length=50, nullable=true)
     */
    private $code;

    /**
     * @var string
     * @ORM\Column(name="city", type="string", length=50, nullable=true)
     */
    private $city;

    /**
     * @var string
     * @ORM\Column(name="search_city", type="string", length=50, nullable=true)
     */
    private $searchCity;

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
     * @return Codes
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
     * Set code
     *
     * @param string $code
     * @return Codes
     */
    public function setCode($code)
    {
        $this->code = $code;
    
        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return Codes
     */
    public function setCity($city)
    {
        $this->city = $city;
    
        return $this;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set searchCity
     *
     * @param string $searchCity
     * @return Codes
     */
    public function setSearchCity($searchCity)
    {
        $this->searchCity = $searchCity;
    
        return $this;
    }

    /**
     * Get searchCity
     *
     * @return string 
     */
    public function getSearchCity()
    {
        return $this->searchCity;
    }
}