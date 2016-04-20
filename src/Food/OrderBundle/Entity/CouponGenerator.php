<?php

namespace Food\OrderBundle\Entity;

use Food\DishesBundle\Entity\Place;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="coupons_generator")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class CouponGenerator
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
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    private $code = "";

    /**
     * @var string
     *
     * @ORM\Column(name="template_code", type="string", length=255)
     */
    private $templateCode;

    /**
     * @ORM\ManyToMany(targetEntity="\Food\DishesBundle\Entity\Place", inversedBy="places")
     */
    private $places;

    /**
     * @var bool
     *
     * @ORM\Column(name="randomize", type="boolean")
     */
    private $randomize = false;


    /**
     * @var int
     *
     * @ORM\Column(name="cart_amount", type="integer",  nullable=true)
     */
    private $cartAmount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="discount", type="integer",  nullable=true)
     */
    private $discount;

    /**
     * @var int
     * @ORM\Column(name="discount_sum", type="integer",  nullable=true)
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
    private $type = Coupon::TYPE_BOTH;

    /**
     * @var string
     *
     * @ORM\Column(name="method", type="string", length=8, options={"default" = "both"})
     */
    private $method = Coupon::METHOD_BOTH;

    /**
     * @var bool
     *
     * @ORM\Column(name="single_use", type="boolean")
     */
    private $singleUse = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active = false;

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
     * @ORM\Column(name="generate_from", type="datetime", nullable=true)
     */
    private $generateFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="generate_to", type="datetime", nullable=true)
     */
    private $generateTo;


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
     * Constructor
     */
    public function __construct()
    {
        $this->places = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * Set code
     *
     * @param string $code
     * @return CouponGenerator
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
     * Set active
     *
     * @param boolean $active
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * @return CouponGenerator
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
     * Set randomize
     *
     * @param boolean $randomize
     * @return CouponGenerator
     */
    public function setRandomize($randomize)
    {
        $this->randomize = $randomize;
    
        return $this;
    }

    /**
     * Get randomize
     *
     * @return boolean 
     */
    public function getRandomize()
    {
        return $this->randomize;
    }

    /**
     * Set generateFrom
     *
     * @param \DateTime $generateFrom
     * @return CouponGenerator
     */
    public function setGenerateFrom($generateFrom)
    {
        $this->generateFrom = $generateFrom;
    
        return $this;
    }

    /**
     * Get generateFrom
     *
     * @return \DateTime 
     */
    public function getGenerateFrom()
    {
        return $this->generateFrom;
    }

    /**
     * Set generateTo
     *
     * @param \DateTime $generateTo
     * @return CouponGenerator
     */
    public function setGenerateTo($generateTo)
    {
        $this->generateTo = $generateTo;
    
        return $this;
    }

    /**
     * Get generateTo
     *
     * @return \DateTime 
     */
    public function getGenerateTo()
    {
        return $this->generateTo;
    }

    /**
     * Set templateCode
     *
     * @param string $templateCode
     * @return CouponGenerator
     */
    public function setTemplateCode($templateCode)
    {
        $this->templateCode = $templateCode;
    
        return $this;
    }

    /**
     * Get templateCode
     *
     * @return string 
     */
    public function getTemplateCode()
    {
        return $this->templateCode;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return CouponGenerator
     */
    public function setType($type)
    {
        if (!in_array($type, array(Coupon::TYPE_BOTH, Coupon::TYPE_API, Coupon::TYPE_WEB))) {
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
     * Set method
     *
     * @param string $method
     * @return CouponGenerator
     */
    public function setMethod($method)
    {
        if (!in_array($method, array(Coupon::METHOD_BOTH, Coupon::METHOD_DELIVERY, Coupon::METHOD_PICKUP))) {
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
}
