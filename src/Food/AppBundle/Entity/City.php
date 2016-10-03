<?php

namespace Food\AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Food\AppBundle\Entity\City
 *
 * @ORM\Table(name="cities", indexes={@ORM\Index(name="title_idx", columns={"title"})})
 * @ORM\Entity(repositoryClass="Food\AppBundle\Entity\CityRepository")
 */
class City implements \JsonSerializable
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
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var boolean
     *
     * @ORM\Column(name="zavalas_on", type="boolean", nullable=true)
     */
    private $zavalasOn;

    /**
     * @var string
     *
     * @ORM\Column(name="zavalas_time", type="string", length=50, nullable=true)
     */
    private $zavalasTime;


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
     * Set title
     *
     * @param string $title
     * @return City
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set zavalasOn
     *
     * @param boolean $zavalasOn
     * @return City
     */
    public function setZavalasOn($zavalasOn)
    {
        $this->zavalasOn = $zavalasOn;
    
        return $this;
    }

    /**
     * Get zavalasOn
     *
     * @return boolean 
     */
    public function isZavalasOn()
    {
        return $this->zavalasOn;
    }

    /**
     * Set zavalasTime
     *
     * @param string $zavalasTime
     * @return City
     */
    public function setZavalasTime($zavalasTime)
    {
        $this->zavalasTime = $zavalasTime;
    
        return $this;
    }

    /**
     * Get zavalasTime
     *
     * @return string 
     */
    public function getZavalasTime()
    {
        return $this->zavalasTime;
    }

    /**
     * Return city title
     *
     * @return string
     */
    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }

        return $this->getTitle();
    }

    public function jsonSerialize()
    {
        return json_encode(array(
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'zavalasOn'=> $this->isZavalasOn(),
            'zavalasTime'=> $this->getZavalasTime(),
        ));
    }
}
