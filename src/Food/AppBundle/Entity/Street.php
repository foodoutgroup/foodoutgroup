<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Food\AppBundle\Entity\Street
 *
 * @ORM\Table(name="city_streets")
 * @ORM\Entity
 */
class Street
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
     * @var string
     * @ORM\Column(name="street", type="string", length=164)
     */
    private $street;

    /**
     * @var string
     * @ORM\Column(name="city", type="string", length=128)
     */
    private $city;

    /**
     * @var \Food\AppBundle\Entity\City
     *
     * @ORM\ManyToOne(targetEntity="\Food\AppBundle\Entity\City")
     * @ORM\JoinColumn(name="city_id", referencedColumnName="id")
     **/
    private $cityId;

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->getId()) {
            return $this->getId().'-'.$this->getCityId()->getTitle().'-'.$this->getStreet();
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
     * Set city
     *
     * @param string $city
     * @return Street
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
     * Set street
     *
     * @param string $street
     * @return Street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    
        return $this;
    }

    /**
     * Get street
     *
     * @return string 
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set cityId
     *
     * @param \Food\AppBundle\Entity\City $cityId
     * @return Street
     */
    public function setCityId(\Food\AppBundle\Entity\City $cityId = null)
    {
        $this->cityId = $cityId;
    
        return $this;
    }

    /**
     * Get cityId
     *
     * @return \Food\AppBundle\Entity\City 
     */
    public function getCityId()
    {
        return $this->cityId;
    }
}