<?php

namespace Food\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user_address")
 * @ORM\Entity(repositoryClass="UserAddressRepository")
 */
class UserAddress
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
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @var string
     * @ORM\Column(name="addressId", type="text", nullable=true)
     */
    private $addressId = null;

    /**
     * @var string
     * @ORM\Column(name="flat", type="text", nullable=true)
     */
    private $flat = null;

    /**
     * @var string
     * @ORM\Column(name="house", type="text", nullable=true)
     */
    private $house = null;

    /**
     * @var string
     * @ORM\Column(name="street", type="text", nullable=true)
     */
    private $street = null;

    /**
     * @var string
     * @ORM\Column(name="country", type="text", nullable=true)
     */
    private $country = null;

    /**
     * @var string
     * @ORM\Column(name="origin", type="text", nullable=true)
     */
    private $origin = null;

    /**
     * @var string
     *
     * @ORM\Column(name="lat", type="string", length=30, nullable=true)
     */
    private $lat;

    /**
     * @var string
     *
     * @ORM\Column(name="lon", type="string", length=30, nullable=true)
     */
    private $lon;

    /**
     * @var string
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment = null;

    /**
     * @var int
     *
     * @ORM\Column(name="is_default", type="integer", length=1)
     */
    private $default = 1;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="address")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;

    /**
     * @var \Food\AppBundle\Entity\City
     *
     * @ORM\ManyToOne(targetEntity="\Food\AppBundle\Entity\City")
     * @ORM\JoinColumn(name="city_id", referencedColumnName="id", nullable=true)
     **/
    private $cityId;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }

        $buffer = $this->getAddress().', ';
        if($city = $this->getCityId()) {
            $buffer .= $city->getTitle();
        }
        return $buffer;
    }



    /**
     * Set city
     *
     * @param string $city
     * @return UserAddress
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
     * @deprecated from 2017-05-04
     */
    public function getCity()
    {
        throw new \Exception('on UserAddress.php: Method getCity() is deprecated. Use getCityId() instead.');
        return $this->city;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return UserAddress
     */
    public function setAddress($address)
    {
        $this->address = $address;
    
        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        if($this->address == null) {
            return $this->getStreet(). ($this->getHouse() ? " ".$this->getHouse() : "").($this->getFlat() ? " - ".$this->getFlat() : "" );
        }
        return $this->address;
    }

    /**
     * Set coords
     *
     * @param string $coords
     * @return UserAddress
     */
    public function setCoords($coords)
    {
        $this->coords = $coords;
    
        return $this;
    }

    /**
     * Get coords
     *
     * @return string 
     */
    public function getCoords()
    {
        return $this->coords;
    }

    /**
     * Set default
     *
     * @param integer $default
     * @return UserAddress
     */
    public function setDefault($default)
    {
        $this->default = $default;
    
        return $this;
    }

    /**
     * Get default
     *
     * @return integer 
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set user
     *
     * @param \Food\UserBundle\Entity\User $user
     * @return UserAddress
     */
    public function setUser(\Food\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \Food\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set lat
     *
     * @param string $lat
     * @return UserAddress
     */
    public function setLat($lat)
    {
        $this->lat = $lat;
    
        return $this;
    }

    /**
     * Get lat
     *
     * @return string 
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * Set lon
     *
     * @param string $lon
     * @return UserAddress
     */
    public function setLon($lon)
    {
        $this->lon = $lon;
    
        return $this;
    }

    /**
     * Get lon
     *
     * @return string 
     */
    public function getLon()
    {
        return $this->lon;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return UserAddress
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    
        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set cityId
     *
     * @param \Food\AppBundle\Entity\City $cityId
     * @return UserAddress
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
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cityId = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add cityId
     *
     * @param \Food\AppBundle\Entity\City $cityId
     * @return UserAddress
     */
    public function addCityId(\Food\AppBundle\Entity\City $cityId)
    {
        $this->cityId[] = $cityId;
    
        return $this;
    }

    /**
     * Remove cityId
     *
     * @param \Food\AppBundle\Entity\City $cityId
     */
    public function removeCityId(\Food\AppBundle\Entity\City $cityId)
    {
        $this->cityId->removeElement($cityId);
    }

    public function toString()
    {
        return $this->__toString();
    }

    /**
     * Set addressId
     *
     * @param string $addressId
     * @return UserAddress
     */
    public function setAddressId($addressId)
    {
        $this->addressId = $addressId;

        return $this;
    }

    /**
     * Get addressId
     *
     * @return string 
     */
    public function getAddressId()
    {
        return $this->addressId;
    }

    /**
     * Set flat
     *
     * @param string $flat
     * @return UserAddress
     */
    public function setFlat($flat)
    {
        $this->flat = $flat;

        return $this;
    }

    /**
     * Get flat
     *
     * @return string 
     */
    public function getFlat()
    {
        return $this->flat;
    }

    /**
     * Set house
     *
     * @param string $house
     * @return UserAddress
     */
    public function setHouse($house)
    {
        $this->house = $house;

        return $this;
    }

    /**
     * Get house
     *
     * @return string 
     */
    public function getHouse()
    {
        return $this->house;
    }

    /**
     * Set street
     *
     * @param string $street
     * @return UserAddress
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
     * Set country
     *
     * @param string $country
     * @return UserAddress
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string 
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set origin
     *
     * @param string $origin
     * @return UserAddress
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * Get origin
     *
     * @return string 
     */
    public function getOrigin()
    {
        return $this->origin;
    }
}
