<?php

namespace Food\DishesBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * PlacePoint
 *
 * @ORM\Table(name="place_point", indexes={@ORM\Index(name="city_idx", columns={"city"}),@ORM\Index(name="active_idx", columns={"active"}),@ORM\Index(name="fast_idx", columns={"fast"}),@ORM\Index(name="public_idx", columns={"public"})})
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
     * @ORM\Column(name="company_code", type="string", length=20)
     */
    private $company_code;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=20)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="alt_phone1", type="string", length=20, nullable=true)
     */
    private $altPhone1;

    /**
     * @var string
     *
     * @ORM\Column(name="alt_phone2", type="string", length=20, nullable=true)
     */
    private $altPhone2;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=128, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="alt_email1", type="string", length=128, nullable=true)
     */
    private $altEmail1;

    /**
     * @var string
     *
     * @ORM\Column(name="alt_email2", type="string", length=128, nullable=true)
     */
    private $altEmail2;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_email", type="string", length=128, nullable=true)
     */
    private $invoiceEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="internal_code", type="string", length=10, nullable=true)
     */
    private $internal_code;

    /**
     * @var string
     *
     * @ORM\Column(name="lat", type="string", length=30)
     */
    private $lat;

    /**
     * @var string
     *
     * @ORM\Column(name="lon", type="string", length=30)
     */
    private $lon;

    /**
     * @var bool
     * @ORM\Column(name="active", type="boolean")
     */
    private $active = true;


    /**
     * @var bool
     * @ORM\Column(name="fast", type="boolean")
     */
    private $fast = false;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_time", type="string", length=255)
     */
    private $deliveryTime;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_time_info", type="string", nullable=true)
     */
    private $deliveryTimeInfo;

    /**
     * @var bool
     *
     * @ORM\Column(name="pick_up", type="boolean")
     */
    private $pickUp = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="delivery", type="boolean")
     */
    private $delivery = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="use_external_logistics", type="boolean")
     */
    private $useExternalLogistics = true;


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
     * @ORM\Column(name="wd1_end_long", type="string", length=5, nullable=true)
     */
    private $wd1_end_long;

    /**
     * @var string
     * @ORM\Column(name="wd2_end_long", type="string", length=5, nullable=true)
     */
    private $wd2_end_long;

    /**
     * @var string
     * @ORM\Column(name="wd3_end_long", type="string", length=5, nullable=true)
     */
    private $wd3_end_long;

    /**
     * @var string
     * @ORM\Column(name="wd4_end_long", type="string", length=5, nullable=true)
     */
    private $wd4_end_long;

    /**
     * @var string
     * @ORM\Column(name="wd5_end_long", type="string", length=5, nullable=true)
     */
    private $wd5_end_long;

    /**
     * @var string
     * @ORM\Column(name="wd6_end_long", type="string", length=5, nullable=true)
     */
    private $wd6_end_long;

    /**
     * @var string
     * @ORM\Column(name="wd7_end_long", type="string", length=5, nullable=true)
     */
    private $wd7_end_long;

    /**
     * @var bool
     *
     * @ORM\Column(name="public", type="boolean")
     */
    private $public = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="allow_cash", type="boolean")
     */
    private $allowCash = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="allow_card", type="boolean")
     */
    private $allowCard = true;

    /**
     * @ORM\OneToMany(targetEntity="PlacePointDeliveryZones", mappedBy="placePoint", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var PlacePointDeliveryZones[]
     */
    private $zones;

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
     * @var string
     *
     * @ORM\Column(name="parent_id", type="string", length=10, nullable=true)
     */
    private $parentId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="no_replication", type="boolean", nullable=true)
     */
    private $noReplication = false;

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

    public function getToString()
    {
        return $this->__toString();
    }

    /**
     * @return array
     */
    public function  __toArray()
    {
        if (!$this->getId()) {
            return array();
        }

        // TODO - kam ko reik - patys pasipildot!
        return array(
            'id' => $this->getId(),
            'placeId' => $this->getPlace()->getId(),
            'placeName' => $this->getPlace()->getName(),
            'address' => $this->getAddress(),
            'city' => $this->getCity(),
            'active' => $this->getActive(),
            'public' => $this->getPublic(),
            'delivery' => $this->getDelivery(),
            'deliveryTime' => $this->getDeliveryTime(),
            'pick_up' => $this->getPickUp(),
            'lat' => $this->getLat(),
            'lon' => $this->getLon(),
            'fast' => $this->getFast(),
            'allowCash' => $this->getAllowCash(),
            'allowCard' => $this->getAllowCard(),
            'workTime' => array(
                'wd1_start' => $this->getWd1Start(),
                'wd1_end' => $this->getWd1End(),
                'wd2_start' => $this->getWd2Start(),
                'wd2_end' => $this->getWd2End(),
                'wd3_start' => $this->getWd3Start(),
                'wd3_end' => $this->getWd3End(),
                'wd4_start' => $this->getWd4Start(),
                'wd4_end' => $this->getWd4End(),
                'wd5_start' => $this->getWd5Start(),
                'wd5_end' => $this->getWd5End(),
                'wd6_start' => $this->getWd6Start(),
                'wd6_end' => $this->getWd6End(),
                'wd7_start' => $this->getWd7Start(),
                'wd7_end' => $this->getWd7End(),
            ),
        );
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

    /**
     * Set lon
     *
     * @param string $lon
     * @return PlacePoint
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
     * Set lat
     *
     * @param string $lat
     * @return PlacePoint
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
     * Set fast
     *
     * @param boolean $fast
     * @return PlacePoint
     */
    public function setFast($fast)
    {
        $this->fast = $fast;
    
        return $this;
    }

    /**
     * Get fast
     *
     * @return boolean 
     */
    public function getFast()
    {
        return $this->fast;
    }

    /**
     * Set public
     *
     * @param boolean $public
     * @return PlacePoint
     */
    public function setPublic($public)
    {
        $this->public = $public;
    
        return $this;
    }

    /**
     * Get public
     *
     * @return boolean 
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * Set allowCash
     *
     * @param boolean $allowCash
     * @return PlacePoint
     */
    public function setAllowCash($allowCash)
    {
        $this->allowCash = $allowCash;
    
        return $this;
    }

    /**
     * Get allowCash
     *
     * @return boolean 
     */
    public function getAllowCash()
    {
        return $this->allowCash;
    }

    /**
     * Set allowCard
     *
     * @param boolean $allowCard
     * @return PlacePoint
     */
    public function setAllowCard($allowCard)
    {
        $this->allowCard = $allowCard;
    
        return $this;
    }

    /**
     * Get allowCard
     *
     * @return boolean 
     */
    public function getAllowCard()
    {
        return $this->allowCard;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return PlacePoint
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    
        return $this;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set company_code
     *
     * @param string $companyCode
     * @return PlacePoint
     */
    public function setCompanyCode($companyCode)
    {
        $this->company_code = $companyCode;
    
        return $this;
    }

    /**
     * Get company_code
     *
     * @return string 
     */
    public function getCompanyCode()
    {
        return $this->company_code;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return PlacePoint
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set altPhone1
     *
     * @param string $altPhone1
     * @return PlacePoint
     */
    public function setAltPhone1($altPhone1)
    {
        $this->altPhone1 = $altPhone1;
    
        return $this;
    }

    /**
     * Get altPhone1
     *
     * @return string 
     */
    public function getAltPhone1()
    {
        return $this->altPhone1;
    }

    /**
     * Set altPhone2
     *
     * @param string $altPhone2
     * @return PlacePoint
     */
    public function setAltPhone2($altPhone2)
    {
        $this->altPhone2 = $altPhone2;
    
        return $this;
    }

    /**
     * Get altPhone2
     *
     * @return string 
     */
    public function getAltPhone2()
    {
        return $this->altPhone2;
    }

    /**
     * Set altEmail1
     *
     * @param string $altEmail1
     * @return PlacePoint
     */
    public function setAltEmail1($altEmail1)
    {
        $this->altEmail1 = $altEmail1;
    
        return $this;
    }

    /**
     * Get altEmail1
     *
     * @return string 
     */
    public function getAltEmail1()
    {
        return $this->altEmail1;
    }

    /**
     * Set altEmail2
     *
     * @param string $altEmail2
     * @return PlacePoint
     */
    public function setAltEmail2($altEmail2)
    {
        $this->altEmail2 = $altEmail2;
    
        return $this;
    }

    /**
     * Get altEmail2
     *
     * @return string 
     */
    public function getAltEmail2()
    {
        return $this->altEmail2;
    }

    /**
     * Set deliveryTimeInfo
     *
     * @param string $deliveryTimeInfo
     * @return PlacePoint
     */
    public function setDeliveryTimeInfo($deliveryTimeInfo)
    {
        $this->deliveryTimeInfo = $deliveryTimeInfo;
    
        return $this;
    }

    /**
     * Get deliveryTimeInfo
     *
     * @return string 
     */
    public function getDeliveryTimeInfo()
    {
        return $this->deliveryTimeInfo;
    }

    /**
     * Returns place point phone formated in nice output for end user
     *
     * @return string
     */
    public function getPhoneNiceFormat()
    {
        if ($this->getId()) {
            $phone = $this->getPhone();
            $returnPhone = '';

            for ($i = 0; $i < strlen($phone); $i++) {
                $returnPhone .= $phone[$i];

                if ($i == 2 || $i == 5) {
                    $returnPhone .= ' ';
                }
            }

            return $returnPhone;
        } else {
            return '';
        }
    }

    /**
     * Set internal_code
     *
     * @param string $internalCode
     * @return PlacePoint
     */
    public function setInternalCode($internalCode)
    {
        $this->internal_code = $internalCode;
    
        return $this;
    }

    /**
     * Get internal_code
     *
     * @return string 
     */
    public function getInternalCode()
    {
        return $this->internal_code;
    }

    /**
     * Set invoiceEmail
     *
     * @param string $invoiceEmail
     * @return PlacePoint
     */
    public function setInvoiceEmail($invoiceEmail)
    {
        $this->invoiceEmail = $invoiceEmail;
    
        return $this;
    }

    /**
     * Get invoiceEmail
     *
     * @return string 
     */
    public function getInvoiceEmail()
    {
        return $this->invoiceEmail;
    }

    /**
     * Set wd1_end_long
     *
     * @param string $wd1EndLong
     * @return PlacePoint
     */
    public function setWd1EndLong($wd1EndLong)
    {
        $this->wd1_end_long = $wd1EndLong;
    
        return $this;
    }

    /**
     * Get wd1_end_long
     *
     * @return string 
     */
    public function getWd1EndLong()
    {
        return $this->wd1_end_long;
    }

    /**
     * Set wd2_end_long
     *
     * @param string $wd2EndLong
     * @return PlacePoint
     */
    public function setWd2EndLong($wd2EndLong)
    {
        $this->wd2_end_long = $wd2EndLong;
    
        return $this;
    }

    /**
     * Get wd2_end_long
     *
     * @return string 
     */
    public function getWd2EndLong()
    {
        return $this->wd2_end_long;
    }

    /**
     * Set wd3_end_long
     *
     * @param string $wd3EndLong
     * @return PlacePoint
     */
    public function setWd3EndLong($wd3EndLong)
    {
        $this->wd3_end_long = $wd3EndLong;
    
        return $this;
    }

    /**
     * Get wd3_end_long
     *
     * @return string 
     */
    public function getWd3EndLong()
    {
        return $this->wd3_end_long;
    }

    /**
     * Set wd4_end_long
     *
     * @param string $wd4EndLong
     * @return PlacePoint
     */
    public function setWd4EndLong($wd4EndLong)
    {
        $this->wd4_end_long = $wd4EndLong;
    
        return $this;
    }

    /**
     * Get wd4_end_long
     *
     * @return string 
     */
    public function getWd4EndLong()
    {
        return $this->wd4_end_long;
    }

    /**
     * Set wd5_end_long
     *
     * @param string $wd5EndLong
     * @return PlacePoint
     */
    public function setWd5EndLong($wd5EndLong)
    {
        $this->wd5_end_long = $wd5EndLong;
    
        return $this;
    }

    /**
     * Get wd5_end_long
     *
     * @return string 
     */
    public function getWd5EndLong()
    {
        return $this->wd5_end_long;
    }

    /**
     * Set wd6_end_long
     *
     * @param string $wd6EndLong
     * @return PlacePoint
     */
    public function setWd6EndLong($wd6EndLong)
    {
        $this->wd6_end_long = $wd6EndLong;
    
        return $this;
    }

    /**
     * Get wd6_end_long
     *
     * @return string 
     */
    public function getWd6EndLong()
    {
        return $this->wd6_end_long;
    }

    /**
     * Set wd7_end_long
     *
     * @param string $wd7EndLong
     * @return PlacePoint
     */
    public function setWd7EndLong($wd7EndLong)
    {
        $this->wd7_end_long = $wd7EndLong;
    
        return $this;
    }

    /**
     * Get wd7_end_long
     *
     * @return string 
     */
    public function getWd7EndLong()
    {
        return $this->wd7_end_long;
    }

    /**
     * Set useExternalLogistics
     *
     * @param boolean $useExternalLogistics
     * @return PlacePoint
     */
    public function setUseExternalLogistics($useExternalLogistics)
    {
        $this->useExternalLogistics = $useExternalLogistics;
    
        return $this;
    }

    /**
     * Get useExternalLogistics
     *
     * @return boolean 
     */
    public function getUseExternalLogistics()
    {
        return $this->useExternalLogistics;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->zones = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add zones
     *
     * @param \Food\DishesBundle\Entity\PlacePointDeliveryZones $zones
     * @return PlacePoint
     */
    public function addZone(\Food\DishesBundle\Entity\PlacePointDeliveryZones $zones)
    {
        $this->zones[] = $zones;
    
        return $this;
    }

    /**
     * Remove zones
     *
     * @param \Food\DishesBundle\Entity\PlacePointDeliveryZones $zones
     */
    public function removeZone(\Food\DishesBundle\Entity\PlacePointDeliveryZones $zones)
    {
        $this->zones->removeElement($zones);
    }

    /**
     * Get zones
     *
     * @return \Food\DishesBundle\Entity\PlacePointDeliveryZones[]
     */
    public function getZones()
    {
        return $this->zones;
    }

    /**
     * Set parentId
     *
     * @param string $parentId
     * @return PlacePoint
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    
        return $this;
    }

    /**
     * Get parentId
     *
     * @return string 
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set noReplication
     *
     * @param boolean $noReplication
     * @return PlacePoint
     */
    public function setNoReplication($noReplication)
    {
        $this->noReplication = $noReplication;
    
        return $this;
    }

    /**
     * Get noReplication
     *
     * @return boolean 
     */
    public function getNoReplication()
    {
        return $this->noReplication;
    }
}
