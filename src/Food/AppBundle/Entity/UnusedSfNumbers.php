<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Food\AppBundle\Entity\UnusedSfNumbers
 *
 * @ORM\Table(name="unused_sf_numbers")
 * @ORM\Entity(repositoryClass="Food\AppBundle\Entity\UnusedSfNumbersRepository")
 * @UniqueEntity("sf_number")
 */
class UnusedSfNumbers
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer $sfNumber
     * @ORM\Column(name="sf_number", type="integer")
     */
    private $sfNumber;

    /**
     * @ORM\Version
     * @ORM\Column(type="integer")
     */
    private $version;

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->getId()) {
            return $this->getId().'-'.$this->getSfNumber();
        }

        return '';
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
     * Set sfNumber
     *
     * @param integer $sfNumber
     * @return UnusedSfNumbers
     */
    public function setSfNumber($sfNumber)
    {
        $this->sfNumber = $sfNumber;
    
        return $this;
    }

    /**
     * Get sfNumber
     *
     * @return integer 
     */
    public function getSfNumber()
    {
        return $this->sfNumber;
    }

    /**
     * Set version
     *
     * @param integer $version
     * @return UnusedSfNumbers
     */
    public function setVersion($version)
    {
        $this->version = $version;
    
        return $this;
    }

    /**
     * Get version
     *
     * @return integer 
     */
    public function getVersion()
    {
        return $this->version;
    }
}