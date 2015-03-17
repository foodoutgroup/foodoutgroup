<?php

namespace Food\DishesBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * PlacePoint
 *
 * @ORM\Table(name="place_point_delivery_zones")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class PlacePointDeliveryZones
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
     * @ORM\ManyToOne(targetEntity="Place")
     * @ORM\JoinColumn(name="place", referencedColumnName="id")
     *
     * @var Place
     */
    private $place;


    /**
     * @ORM\ManyToOne(targetEntity="PlacePoint", inversedBy="zones")
     * @ORM\JoinColumn(name="place_point", referencedColumnName="id")
     *
     * @var Place
     */
    private $placePoint;


    /**
     * @var int
     *
     * @ORM\Column(name="distance", type="float")
     */
    private $distance;

    /**
     * @var int
     *
     * @ORM\Column(name="price", type="float")
     */
    private $price;

    /**
     * @var float
     * @ORM\Column(name="cart_size", type="decimal", precision=8, scale=2, nullable=true)
     */
    private $cartSize;

    /**
     * @var string
     *
     * @ORM\Column(name="time_from", type="string", length=5, nullable=true)
     */
    private $timeFrom;

    /**
     * @var string
     *
     * @ORM\Column(name="time_to", type="string", length=5, nullable=true)
     */
    private $timeTo;

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

    /**
     * @var bool
     * @ORM\Column(name="active", type="boolean")
     */
    private $active = true;

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
     * Set distance
     *
     * @param float $distance
     * @return PlacePointDeliveryZones
     */
    public function setDistance($distance)
    {
        $this->distance = $distance;
    
        return $this;
    }

    /**
     * Get distance
     *
     * @return float 
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * Set price
     *
     * @param float $price
     * @return PlacePointDeliveryZones
     */
    public function setPrice($price)
    {
        $this->price = $price;
    
        return $this;
    }

    /**
     * Get price
     *
     * @return float 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set timeFrom
     *
     * @param string $timeFrom
     * @return PlacePointDeliveryZones
     */
    public function setTimeFrom($timeFrom)
    {
        $this->timeFrom = $timeFrom;
    
        return $this;
    }

    /**
     * Get timeFrom
     *
     * @return string 
     */
    public function getTimeFrom()
    {
        return $this->timeFrom;
    }

    /**
     * Set timeTo
     *
     * @param string $timeTo
     * @return PlacePointDeliveryZones
     */
    public function setTimeTo($timeTo)
    {
        $this->timeTo = $timeTo;
    
        return $this;
    }

    /**
     * Get timeTo
     *
     * @return string 
     */
    public function getTimeTo()
    {
        return $this->timeTo;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return PlacePointDeliveryZones
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
     * @return PlacePointDeliveryZones
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
     * @return PlacePointDeliveryZones
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
     * Set placePoint
     *
     * @param \Food\DishesBundle\Entity\PlacePoint $placePoint
     * @return PlacePointDeliveryZones
     */
    public function setPlacePoint(\Food\DishesBundle\Entity\PlacePoint $placePoint = null)
    {
        $this->placePoint = $placePoint;
    
        return $this;
    }

    /**
     * Get placePoint
     *
     * @return \Food\DishesBundle\Entity\PlacePoint 
     */
    public function getPlacePoint()
    {
        return $this->placePoint;
    }

    /**
     * Set createdBy
     *
     * @param \Food\UserBundle\Entity\User $createdBy
     * @return PlacePointDeliveryZones
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
     * @return PlacePointDeliveryZones
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
     * @return PlacePointDeliveryZones
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

    public function getName()
    {
        return '';
    }


    /**
     * Set place
     *
     * @param \Food\DishesBundle\Entity\Place $place
     * @return PlacePointDeliveryZones
     */
    public function setPlace(\Food\DishesBundle\Entity\Place $place = null)
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
     * Set active
     *
     * @param boolean $active
     * @return PlacePointDeliveryZones
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
}