<?php

namespace Food\OrderBundle\Entity;

use Food\DishesBundle\Entity\Place;
use Food\UserBundle\Entity\User;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="coupons")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class Coupon
{
    const TYPE_BOTH = 'both';
    const TYPE_API = 'api';
    const TYPE_WEB = 'web';

    const METHOD_BOTH = 'both';
    const METHOD_DELIVERY = 'delivery';
    const METHOD_PICKUP = 'pickup';

    const B2B_BOTH = 'both';
    const B2B_YES = 'yes';
    const B2B_NO = 'no';

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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="discount", type="integer", nullable=true)
     */
    private $discount;

    /**
     * @var int
     * @ORM\Column(name="discount_sum", type="integer", nullable=true)
     */
    private $discountSum;

    /**
     * @var CouponRange
     *
     * @ORM\ManyToOne(targetEntity="\Food\OrderBundle\Entity\CouponRange", inversedBy="coupons", cascade={"persist"})
     * @ORM\JoinColumn(name="coupon_range", referencedColumnName="id")
     */
    private $couponRange;

    /**
     * @var bool
     *
     * @ORM\Column(name="free_delivery", type="boolean")
     */
    private $freeDelivery = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="full_order_cover", type="boolean")
     */
    private $fullOrderCovers = false;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255)
     */
    private $code;

    /**
     * @ORM\ManyToMany(targetEntity="\Food\DishesBundle\Entity\Place")
     */
    private $places;

    /**
     * @var bool
     *
     * @ORM\Column(name="only_nav", type="boolean")
     */
    private $onlyNav = false;


    /**
     * @var bool
     *
     * @ORM\Column(name="no_self_delivery", type="boolean")
     */
    private $noSelfDelivery = false;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=4, options={"fixed" = true, "default" = "both"})
     */
    private $type = self::TYPE_BOTH;

    /**
     * @var string
     *
     * @ORM\Column(name="method", type="string", length=8, options={"default" = "both"})
     */
    private $method = self::METHOD_BOTH;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="single_use", type="boolean")
     */
    private $singleUse = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="single_use_per_person", type="boolean", nullable=true)
     */
    private $singleUsePerPerson = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="inverse", type="boolean", nullable=true)
     */
    private $inverse = false;

    /**
     * @ORM\OneToMany(targetEntity="Food\OrderBundle\Entity\CouponUser", mappedBy="coupon")
     */
    private $couponUsers;

    /**
     * @var bool
     *
     * @ORM\Column(name="online_payments_only", type="boolean", nullable=true)
     */
    private $onlinePaymentsOnly = false;


    /**
     * @var bool
     *
     * @ORM\Column(name="enable_validate_date", type="boolean")
     */
    private $enableValidateDate = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="valid_from", type="datetime", nullable=true)
     */
    private $validFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="valid_to", type="datetime", nullable=true)
     */
    private $validTo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="valid_hourly_from", type="time", nullable=true)
     */
    private $validHourlyFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="valid_hourly_to", type="time", nullable=true)
     */
    private $validHourlyTo;

    /**
     * @var string
     *
     * @ORM\Column(name="b2b", type="string", length=4, options={"default" = "both"})
     */
    private $b2b = self::B2B_BOTH;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="edited_at", type="datetime", nullable=true)
     */
    private $editedAt;

    /**
     * @var \DateTime|null
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
     *
     * @ORM\Column(name="ignore_cart_price", type="boolean", nullable=true)
     */
    private $ignoreCartPrice = false;


    /**
     * @var bool
     *
     * @ORM\Column(name="include_delivery", type="boolean", nullable=true)
     */
    private $includeDelivery = false;




    public function __toString()
    {
        if ($this->getId()) {
            $places = $this->getPlaces();
            $placeNames = [];
            if (count($places)) {
                foreach ($places as $place) {
                    $placeNames[] = $place->getName();
                }
            } else {
                $placeNames[] = 'global';
            }

            return $this->getId() . '-' . $this->getName() . '-' . implode('-', $placeNames);
        }

        return '';
    }

    /**
     * @return array
     */
    public function __toArray()
    {
        if ($this->getId()) {
            $placeIds = [];
            $places = $this->getPlaces();
            if (count($places)) {
                foreach ($places as $place) {
                    $placeIds[] = $place->getId();
                }
            }

            return [
                'id'           => $this->getId(),
                'code'         => $this->getCode(),
                'place_ids'    => $placeIds,
                'discount'     => $this->getDiscount(),
                'discount_sum' => $this->getDiscountSum(),
                'active'       => $this->getActive(),
                'single_use'   => $this->getSingleUse(),
            ];
        }

        return [];
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
     * Set name
     *
     * @param string $name
     *
     * @return Coupon
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return Coupon
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
     *
     * @return Coupon
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
     * @param \DateTime|null $editedAt
     *
     * @return Coupon
     */
    public function setEditedAt($editedAt)
    {
        $this->editedAt = $editedAt;

        return $this;
    }

    /**
     * Get editedAt
     *
     * @return \DateTime|null
     */
    public function getEditedAt()
    {
        return $this->editedAt;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime|null $deletedAt
     *
     * @return Coupon
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime|null
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Set createdBy
     *
     * @param \Food\UserBundle\Entity\User $createdBy
     *
     * @return Coupon
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
     *
     * @return Coupon
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
     *
     * @return Coupon
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
     * Set code
     *
     * @param string $code
     *
     * @return Coupon
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
     * Set singleUse
     *
     * @param boolean $singleUse
     *
     * @return Coupon
     */
    public function setSingleUse($singleUse)
    {
        $this->singleUse = $singleUse;

        return $this;
    }

    /**
     * Get singleUse
     *
     * @return boolean
     */
    public function getSingleUse()
    {
        return $this->singleUse;
    }

    /**
     * Set discount
     *
     * @param integer $discount
     *
     * @return Coupon
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount
     *
     * @return integer
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set onlyNav
     *
     * @param boolean $onlyNav
     *
     * @return Coupon
     */
    public function setOnlyNav($onlyNav)
    {
        $this->onlyNav = $onlyNav;

        return $this;
    }

    /**
     * Get onlyNav
     *
     * @return boolean
     */
    public function getOnlyNav()
    {
        return $this->onlyNav;
    }

    /**
     * Set freeDelivery
     *
     * @param boolean $freeDelivery
     *
     * @return Coupon
     */
    public function setFreeDelivery($freeDelivery)
    {
        $this->freeDelivery = $freeDelivery;

        return $this;
    }

    /**
     * Get freeDelivery
     *
     * @return boolean
     */
    public function getFreeDelivery()
    {
        return $this->freeDelivery;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->places = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set discountSum
     *
     * @param integer $discountSum
     *
     * @return Coupon
     */
    public function setDiscountSum($discountSum)
    {
        $this->discountSum = $discountSum;

        return $this;
    }

    /**
     * Get discountSum
     *
     * @return integer
     */
    public function getDiscountSum()
    {
        return $this->discountSum;
    }

    /**
     * Set noSelfDelivery
     *
     * @param boolean $noSelfDelivery
     *
     * @return Coupon
     */
    public function setNoSelfDelivery($noSelfDelivery)
    {
        $this->noSelfDelivery = $noSelfDelivery;

        return $this;
    }

    /**
     * Get noSelfDelivery
     *
     * @return boolean
     */
    public function getNoSelfDelivery()
    {
        return $this->noSelfDelivery;
    }

    /**
     * Set enableValidateDate
     *
     * @param boolean $enableValidateDate
     *
     * @return Coupon
     */
    public function setEnableValidateDate($enableValidateDate)
    {
        $this->enableValidateDate = $enableValidateDate;

        return $this;
    }

    /**
     * Get enableValidateDate
     *
     * @return boolean
     */
    public function getEnableValidateDate()
    {
        return $this->enableValidateDate;
    }

    /**
     * Set validFrom
     *
     * @param \DateTime $validFrom
     *
     * @return Coupon
     */
    public function setValidFrom($validFrom)
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    /**
     * Get validFrom
     *
     * @return \DateTime
     */
    public function getValidFrom()
    {
        return $this->validFrom;
    }

    /**
     * Set validTo
     *
     * @param \DateTime $validTo
     *
     * @return Coupon
     */
    public function setValidTo($validTo)
    {
        $this->validTo = $validTo;

        return $this;
    }

    /**
     * Get validTo
     *
     * @return \DateTime
     */
    public function getValidTo()
    {
        return $this->validTo;
    }

    /**
     * Add places
     *
     * @param \Food\DishesBundle\Entity\Place $places
     *
     * @return Coupon
     */
    public function addPlace(\Food\DishesBundle\Entity\Place $places)
    {
        $this->places[] = $places;

        return $this;
    }

    /**
     * Remove places
     *
     * @param \Food\DishesBundle\Entity\Place $places
     */
    public function removePlace(\Food\DishesBundle\Entity\Place $places)
    {
        $this->places->removeElement($places);
    }

    /**
     * Get places
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * Set fullOrderCovers
     *
     * @param boolean $fullOrderCovers
     *
     * @return Coupon
     */
    public function setFullOrderCovers($fullOrderCovers)
    {
        $this->fullOrderCovers = $fullOrderCovers;

        return $this;
    }

    /**
     * Get fullOrderCovers
     *
     * @return boolean
     */
    public function getFullOrderCovers()
    {
        return $this->fullOrderCovers;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Coupon
     */
    public function setType($type)
    {
        if (!in_array($type, [self::TYPE_BOTH, self::TYPE_API, self::TYPE_WEB])) {
            throw new \InvalidArgumentException('Wrong type defined');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Checks is this cupon is allowed in api
     *
     * @return mixed
     */
    public function isAllowedForApi()
    {
        return in_array($this->getType(), [self::TYPE_BOTH, self::TYPE_API]);
    }

    /**
     * Checks is this cupon is allowed in web
     *
     * @return mixed
     */
    public function isAllowedForWeb()
    {
        return in_array($this->getType(), [self::TYPE_BOTH, self::TYPE_WEB]);
    }

    /**
     * Set method
     *
     * @param string $method
     *
     * @return Coupon
     */
    public function setMethod($method)
    {
        if (!in_array($method, [self::METHOD_BOTH, self::METHOD_DELIVERY, self::METHOD_PICKUP])) {
            throw new \InvalidArgumentException('Wrong method defined');
        }

        $this->method = $method;

        return $this;
    }

    /**
     * Get method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Checks is this coupon is allowed when delivery
     *
     * @return mixed
     */
    public function isAllowedForDelivery()
    {
        return in_array($this->getMethod(), [self::METHOD_BOTH, self::METHOD_DELIVERY]);
    }

    /**
     * Checks is this coupon is allowed when pickup
     *
     * @return mixed
     */
    public function isAllowedForPickup()
    {
        return in_array($this->getMethod(), [self::METHOD_BOTH, self::METHOD_PICKUP]);
    }


    /**
     * Set couponRange
     *
     * @param \Food\OrderBundle\Entity\CouponRange $couponRange
     *
     * @return Coupon
     */
    public function setCouponRange(\Food\OrderBundle\Entity\CouponRange $couponRange = null)
    {
        $this->couponRange = $couponRange;

        return $this;
    }

    /**
     * Get couponRange
     *
     * @return \Food\OrderBundle\Entity\CouponRange
     */
    public function getCouponRange()
    {
        return $this->couponRange;
    }

    /**
     * Set singleUsePerPerson
     *
     * @param boolean $singleUsePerPerson
     *
     * @return Coupon
     */
    public function setSingleUsePerPerson($singleUsePerPerson)
    {
        $this->singleUsePerPerson = $singleUsePerPerson;

        return $this;
    }

    /**
     * Get singleUsePerPerson
     *
     * @return boolean
     */
    public function getSingleUsePerPerson()
    {
        return $this->singleUsePerPerson;
    }

    /**
     * Set onlinePaymentsOnly
     *
     * @param boolean $onlinePaymentsOnly
     *
     * @return Coupon
     */
    public function setOnlinePaymentsOnly($onlinePaymentsOnly)
    {
        $this->onlinePaymentsOnly = $onlinePaymentsOnly;

        return $this;
    }

    /**
     * Get onlinePaymentsOnly
     *
     * @return boolean
     */
    public function getOnlinePaymentsOnly()
    {
        return $this->onlinePaymentsOnly;
    }

    /**
     * Set validHourlyFrom
     *
     * @param \DateTime $validHourlyFrom
     *
     * @return Coupon
     */
    public function setValidHourlyFrom($validHourlyFrom)
    {
        $this->validHourlyFrom = $validHourlyFrom;

        return $this;
    }

    /**
     * Get validHourlyFrom
     *
     * @return \DateTime
     */
    public function getValidHourlyFrom()
    {
        return $this->validHourlyFrom;
    }

    /**
     * Set validHourlyTo
     *
     * @param \DateTime $validHourlyTo
     *
     * @return Coupon
     */
    public function setValidHourlyTo($validHourlyTo)
    {
        $this->validHourlyTo = $validHourlyTo;

        return $this;
    }

    /**
     * Get validHourlyTo
     *
     * @return \DateTime
     */
    public function getValidHourlyTo()
    {
        return $this->validHourlyTo;
    }

    /**
     * Add couponUsers
     *
     * @param \Food\OrderBundle\Entity\CouponUser $couponUsers
     *
     * @return Coupon
     */
    public function addCouponUser(\Food\OrderBundle\Entity\CouponUser $couponUsers)
    {
        $this->couponUsers[] = $couponUsers;

        return $this;
    }

    /**
     * Remove couponUsers
     *
     * @param \Food\OrderBundle\Entity\CouponUser $couponUsers
     */
    public function removeCouponUser(\Food\OrderBundle\Entity\CouponUser $couponUsers)
    {
        $this->couponUsers->removeElement($couponUsers);
    }

    /**
     * Get couponUsers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCouponUsers()
    {
        return $this->couponUsers;
    }

    /**
     * Set ignoreCartPrice
     *
     * @param boolean $ignoreCartPrice
     *
     * @return Coupon
     */
    public function setIgnoreCartPrice($ignoreCartPrice)
    {
        $this->ignoreCartPrice = $ignoreCartPrice;

        return $this;
    }

    /**
     * Get ignoreCartPrice
     *
     * @return boolean
     */
    public function getIgnoreCartPrice()
    {
        return $this->ignoreCartPrice;
    }


    /**
     * Set b2b
     *
     * @param string $b2b
     *
     * @return Coupon
     */
    public function setB2b($b2b)
    {
        if (!in_array($b2b, [self::B2B_BOTH, self::B2B_YES, self::B2B_NO])) {
            throw new \InvalidArgumentException('Wrong method defined');
        }
        $this->b2b = $b2b;

        return $this;
    }

    /**
     * Get b2b
     *
     * @return string
     */
    public function getB2b()
    {
        return $this->b2b;
    }

    /**
     * Set includeDelivery
     *
     * @param boolean $includeDelivery
     * @return Coupon
     */
    public function setIncludeDelivery($includeDelivery)
    {
        $this->includeDelivery = $includeDelivery;
    
        return $this;
    }

    /**
     * Get includeDelivery
     *
     * @return boolean 
     */
    public function getIncludeDelivery()
    {
        return $this->includeDelivery;
    }

    /**
     * Set inverse
     *
     * @param boolean $inverse
     * @return Coupon
     */
    public function setInverse($inverse)
    {
        $this->inverse = $inverse;
    
        return $this;
    }

    /**
     * Get inverse
     *
     * @return boolean 
     */
    public function getInverse()
    {
        return $this->inverse;
    }
}