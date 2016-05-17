<?php
namespace Food\OrderBundle\Entity;
use Food\DishesBundle\Entity\Place;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\Table(name="coupons_range")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class CouponRange
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;
    /**
     * @ORM\ManyToMany(targetEntity="\Food\DishesBundle\Entity\Place")
     */
    private $places;
    /**
     * @var int
     *
     * @ORM\Column(name="cart_amount", type="integer", nullable=true)
     */
    private $cartAmount = 0;
    /**
     * @var string
     *
     * @ORM\Column(name="prefix", type="string", length=255, nullable=true)
     */
    private $prefix;
    /**
     * @var string
     *
     * @ORM\Column(name="suffix", type="string", length=255, nullable=true)
     */
    private $suffix;
    /**
     * @var int
     *
     * @ORM\Column(name="coupons_qty", type="integer", nullable=true)
     */
    private $couponsQty = 0;
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
     * @ORM\Column(name="online_payments_only", type="boolean", nullable=true)
     */
    private $onlinePaymentsOnly = false;

    /**
     * @var Coupon[]
     *
     * @ORM\OneToMany(targetEntity="Food\OrderBundle\Entity\Coupon", mappedBy="couponRange", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $coupons;
    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active = false;
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
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
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
     * Constructor
     */
    public function __construct()
    {
        $this->places = new \Doctrine\Common\Collections\ArrayCollection();
    }
    public function __toString()
    {
        $name = $this->getName();
        if (!empty($name)) {
            return $name;
        }
        return 'Untitled List - ' . date('Y-m-d');
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
     * @return CouponRange
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
     * Set discount
     *
     * @param integer $discount
     * @return CouponRange
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
     * Set discountSum
     *
     * @param integer $discountSum
     * @return CouponRange
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
     * Set freeDelivery
     *
     * @param boolean $freeDelivery
     * @return CouponRange
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
     * Set active
     *
     * @param boolean $active
     * @return CouponRange
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
     * Set validFrom
     *
     * @param \DateTime $validFrom
     * @return CouponRange
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
     * @return CouponRange
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return CouponRange
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
     * @return CouponRange
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
     * @return CouponRange
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
     * Add places
     *
     * @param \Food\DishesBundle\Entity\Place $places
     * @return CouponRange
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
     * @return Place[]
     */
    public function getPlaces()
    {
        return $this->places;
    }
    /**
     * Set createdBy
     *
     * @param \Food\UserBundle\Entity\User $createdBy
     * @return CouponRange
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
     * @return CouponRange
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
     * @return CouponRange
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
     * Set cartAmount
     *
     * @param integer $cartAmount
     * @return CouponRange
     */
    public function setCartAmount($cartAmount)
    {
        $this->cartAmount = $cartAmount;
        return $this;
    }
    /**
     * Get cartAmount
     *
     * @return integer
     */
    public function getCartAmount()
    {
        return $this->cartAmount;
    }
    /**
     * Set onlyNav
     *
     * @param boolean $onlyNav
     * @return CouponRange
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
     * Set noSelfDelivery
     *
     * @param boolean $noSelfDelivery
     * @return CouponRange
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
     * Set singleUse
     *
     * @param boolean $singleUse
     * @return CouponRange
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
     * Set enableValidateDate
     *
     * @param boolean $enableValidateDate
     * @return CouponRange
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
     * Set prefix
     *
     * @param string $prefix
     * @return CouponRange
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }
    /**
     * Get prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
    /**
     * Set suffix
     *
     * @param string $suffix
     * @return CouponRange
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;

        return $this;
    }
    /**
     * Get suffix
     *
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }
    /**
     * Set couponsQty
     *
     * @param integer $couponsQty
     * @return CouponRange
     */
    public function setCouponsQty($couponsQty)
    {
        $this->couponsQty = $couponsQty;

        return $this;
    }
    /**
     * Get couponsQty
     *
     * @return integer
     */
    public function getCouponsQty()
    {
        return $this->couponsQty;
    }
    /**
     * Set fullOrderCovers
     *
     * @param boolean $fullOrderCovers
     * @return CouponRange
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
     * Add coupons
     *
     * @param \Food\OrderBundle\Entity\Coupon $coupons
     * @return CouponRange
     */
    public function addCoupon(\Food\OrderBundle\Entity\Coupon $coupons)
    {
        $this->coupons[] = $coupons;

        return $this;
    }
    /**
     * Remove coupons
     *
     * @param \Food\OrderBundle\Entity\Coupon $coupons
     */
    public function removeCoupon(\Food\OrderBundle\Entity\Coupon $coupons)
    {
        $this->coupons->removeElement($coupons);
    }
    /**
     * Get coupons
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCoupons()
    {
        return $this->coupons;
    }

    /**
     * Set singleUsePerPerson
     *
     * @param boolean $singleUsePerPerson
     * @return CouponRange
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
     * @return CouponRange
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
}