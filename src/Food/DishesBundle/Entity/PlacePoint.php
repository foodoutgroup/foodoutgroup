<?php

namespace Food\DishesBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * PlacePoint
 *
 * @ORM\Table(name="place_point")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class PlacePoint
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
     * @ORM\Column(name="address", type="string", length=255)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="coords", type="string", length=255)
     */
    private $coords;

    /**
     * @var bool
     * @ORM\Column(name="active", type="boolean")
     */
    private $active =1;


    /**
     * @var string
     *
     * @ORM\Column(name="delivery_time", type="string", length=255)
     */
    private $deliveryTime;

    /**
     * @var bool
     *
     * @ORM\Column(name="pick_up", type="boolean")
     */
    private $pickUp;

    /**
     * @var bool
     *
     * @ORM\Column(name="delivery", type="boolean")
     */
    private $delivery;


    /**
     * @ORM\ManyToOne(targetEntity="Place", inversedBy="points")
     * @ORM\JoinColumn(name="place", referencedColumnName="id")
     *
     * @var Place
     */
    private $place;

    /**
     * @var string
     * @ORM\Column(name="wd1_start", type="string", length=5)
     */
    private $wd1_start;

    /**
     * @var string
     * @ORM\Column(name="wd1_end", type="string", length=5)
     */
    private $wd1_end;

    /**
     * @var string
     * @ORM\Column(name="wd2_start", type="string", length=5)
     */
    private $wd2_start;

    /**
     * @var string
     * @ORM\Column(name="wd2_end", type="string", length=5)
     */
    private $wd2_end;

    /**
     * @var string
     * @ORM\Column(name="wd3_start", type="string", length=5)
     */
    private $wd3_start;

    /**
     * @var string
     * @ORM\Column(name="wd3_end", type="string", length=5)
     */
    private $wd3_end;

    /**
     * @var string
     * @ORM\Column(name="wd4_start", type="string", length=5)
     */
    private $wd4_start;

    /**
     * @var string
     * @ORM\Column(name="wd4_end", type="string", length=5)
     */
    private $wd4_end;

    /**
     * @var string
     * @ORM\Column(name="wd5_start", type="string", length=5)
     */
    private $wd5_start;

    /**
     * @var string
     * @ORM\Column(name="wd5_end", type="string", length=5)
     */
    private $wd5_end;

    /**
     * @var string
     * @ORM\Column(name="wd6_start", type="string", length=5)
     */
    private $wd6_start;

    /**
     * @var string
     * @ORM\Column(name="wd6_end", type="string", length=5)
     */
    private $wd6_end;

    /**
     * @var string
     * @ORM\Column(name="wd7_start", type="string", length=5)
     */
    private $wd7_start;

    /**
     * @var string
     * @ORM\Column(name="wd7_end", type="string", length=5)
     */
    private $wd7_end;



    /**
     * @var string
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="edited_at", type="datetime", nullable=true)
     */
    private $editedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     **/
    private $createdBy;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="edited_by", referencedColumnName="id")
     */
    private $editedBy;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="deleted_by", referencedColumnName="id")
     */
    private $deletedBy;

    public function getElement()
    {
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
     * Set address
     *
     * @param string $address
     * @return PlacePoint
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
        return $this->address;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return PlacePoint
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
     * Set coords
     *
     * @param string $coords
     * @return PlacePoint
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
     * Set active
     *
     * @param boolean $active
     * @return PlacePoint
     */
    public function setActive($active)
    {
        $this->active = $active;
    
        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return PlacePoint
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set editedAt
     *
     * @param \DateTime $editedAt
     * @return PlacePoint
     */
    public function setEditedAt($editedAt)
    {
        $this->editedAt = $editedAt;
    
        return $this;
    }

    /**
     * Get editedAt
     *
     * @return \DateTime 
     */
    public function getEditedAt()
    {
        return $this->editedAt;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     * @return PlacePoint
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    
        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime 
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Set place
     *
     * @param Place $place
     * @return PlacePoint
     */
    public function setPlace(Place $place = null)
    {
        $this->place = $place;
    
        return $this;
    }

    /**
     * Get place
     *
     * @return \Food\DishesBundle\Entity\Place 
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Set wd1_start
     *
     * @param string $wd1Start
     * @return PlacePoint
     */
    public function setWd1Start($wd1Start)
    {
        $this->wd1_start = $wd1Start;
    
        return $this;
    }

    /**
     * Get wd1_start
     *
     * @return string 
     */
    public function getWd1Start()
    {
        return $this->wd1_start;
    }

    /**
     * Set wd1_end
     *
     * @param string $wd1End
     * @return PlacePoint
     */
    public function setWd1End($wd1End)
    {
        $this->wd1_end = $wd1End;
    
        return $this;
    }

    /**
     * Get wd1_end
     *
     * @return string 
     */
    public function getWd1End()
    {
        return $this->wd1_end;
    }

    /**
     * Set wd2_start
     *
     * @param string $wd2Start
     * @return PlacePoint
     */
    public function setWd2Start($wd2Start)
    {
        $this->wd2_start = $wd2Start;
    
        return $this;
    }

    /**
     * Get wd2_start
     *
     * @return string 
     */
    public function getWd2Start()
    {
        return $this->wd2_start;
    }

    /**
     * Set wd2_end
     *
     * @param string $wd2End
     * @return PlacePoint
     */
    public function setWd2End($wd2End)
    {
        $this->wd2_end = $wd2End;
    
        return $this;
    }

    /**
     * Get wd2_end
     *
     * @return string 
     */
    public function getWd2End()
    {
        return $this->wd2_end;
    }

    /**
     * Set wd3_start
     *
     * @param string $wd3Start
     * @return PlacePoint
     */
    public function setWd3Start($wd3Start)
    {
        $this->wd3_start = $wd3Start;
    
        return $this;
    }

    /**
     * Get wd3_start
     *
     * @return string 
     */
    public function getWd3Start()
    {
        return $this->wd3_start;
    }

    /**
     * Set wd3_end
     *
     * @param string $wd3End
     * @return PlacePoint
     */
    public function setWd3End($wd3End)
    {
        $this->wd3_end = $wd3End;
    
        return $this;
    }

    /**
     * Get wd3_end
     *
     * @return string 
     */
    public function getWd3End()
    {
        return $this->wd3_end;
    }

    /**
     * Set wd4_start
     *
     * @param string $wd4Start
     * @return PlacePoint
     */
    public function setWd4Start($wd4Start)
    {
        $this->wd4_start = $wd4Start;
    
        return $this;
    }

    /**
     * Get wd4_start
     *
     * @return string 
     */
    public function getWd4Start()
    {
        return $this->wd4_start;
    }

    /**
     * Set wd4_end
     *
     * @param string $wd4End
     * @return PlacePoint
     */
    public function setWd4End($wd4End)
    {
        $this->wd4_end = $wd4End;
    
        return $this;
    }

    /**
     * Get wd4_end
     *
     * @return string 
     */
    public function getWd4End()
    {
        return $this->wd4_end;
    }

    /**
     * Set wd5_start
     *
     * @param string $wd5Start
     * @return PlacePoint
     */
    public function setWd5Start($wd5Start)
    {
        $this->wd5_start = $wd5Start;
    
        return $this;
    }

    /**
     * Get wd5_start
     *
     * @return string 
     */
    public function getWd5Start()
    {
        return $this->wd5_start;
    }

    /**
     * Set wd5_end
     *
     * @param string $wd5End
     * @return PlacePoint
     */
    public function setWd5End($wd5End)
    {
        $this->wd5_end = $wd5End;
    
        return $this;
    }

    /**
     * Get wd5_end
     *
     * @return string 
     */
    public function getWd5End()
    {
        return $this->wd5_end;
    }

    /**
     * Set wd6_start
     *
     * @param string $wd6Start
     * @return PlacePoint
     */
    public function setWd6Start($wd6Start)
    {
        $this->wd6_start = $wd6Start;
    
        return $this;
    }

    /**
     * Get wd6_start
     *
     * @return string 
     */
    public function getWd6Start()
    {
        return $this->wd6_start;
    }

    /**
     * Set wd6_end
     *
     * @param string $wd6End
     * @return PlacePoint
     */
    public function setWd6End($wd6End)
    {
        $this->wd6_end = $wd6End;
    
        return $this;
    }

    /**
     * Get wd6_end
     *
     * @return string 
     */
    public function getWd6End()
    {
        return $this->wd6_end;
    }

    /**
     * Set wd7_start
     *
     * @param string $wd7Start
     * @return PlacePoint
     */
    public function setWd7Start($wd7Start)
    {
        $this->wd7_start = $wd7Start;
    
        return $this;
    }

    /**
     * Get wd7_start
     *
     * @return string 
     */
    public function getWd7Start()
    {
        return $this->wd7_start;
    }

    /**
     * Set wd7_end
     *
     * @param string $wd7End
     * @return PlacePoint
     */
    public function setWd7End($wd7End)
    {
        $this->wd7_end = $wd7End;
    
        return $this;
    }

    /**
     * Get wd7_end
     *
     * @return string 
     */
    public function getWd7End()
    {
        return $this->wd7_end;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }
        return $this->getAddress().', '.$this->getCity();
    }

    /**
     * Set createdBy
     *
     * @param \Food\UserBundle\Entity\User $createdBy
     * @return PlacePoint
     */
    public function setCreatedBy(\Food\UserBundle\Entity\User $createdBy = null)
    {
        $this->createdBy = $createdBy;
    
        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Food\UserBundle\Entity\User 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set editedBy
     *
     * @param \Food\UserBundle\Entity\User $editedBy
     * @return PlacePoint
     */
    public function setEditedBy(\Food\UserBundle\Entity\User $editedBy = null)
    {
        $this->editedBy = $editedBy;
    
        return $this;
    }

    /**
     * Get editedBy
     *
     * @return \Food\UserBundle\Entity\User 
     */
    public function getEditedBy()
    {
        return $this->editedBy;
    }

    /**
     * Set deletedBy
     *
     * @param \Food\UserBundle\Entity\User $deletedBy
     * @return PlacePoint
     */
    public function setDeletedBy(\Food\UserBundle\Entity\User $deletedBy = null)
    {
        $this->deletedBy = $deletedBy;
    
        return $this;
    }

    /**
     * Get deletedBy
     *
     * @return \Food\UserBundle\Entity\User 
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * Set deliveryTime
     *
     * @param string $deliveryTime
     * @return PlacePoint
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
     * Set pickUp
     *
     * @param boolean $pickUp
     * @return PlacePoint
     */
    public function setPickUp($pickUp)
    {
        $this->pickUp = $pickUp;
    
        return $this;
    }

    /**
     * Get pickUp
     *
     * @return boolean 
     */
    public function getPickUp()
    {
        return $this->pickUp;
    }

    /**
     * Set delivery
     *
     * @param boolean $delivery
     * @return PlacePoint
     */
    public function setDelivery($delivery)
    {
        $this->delivery = $delivery;
    
        return $this;
    }

    /**
     * Get delivery
     *
     * @return boolean 
     */
    public function getDelivery()
    {
        return $this->delivery;
    }
}