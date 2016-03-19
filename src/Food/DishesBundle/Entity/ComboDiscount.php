<?php

namespace Food\DishesBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;
use Food\AppBundle\Entity\Uploadable;

use Doctrine\ORM\EntityManager;


/**
 * Kitchen
 *
 * @ORM\Table(name="combo_discount", indexes={@ORM\Index(name="active_idx", columns={"active"})})
 * @ORM\Entity
 */
class ComboDiscount
{

    const OPT_COMBO_TYPE_DISCOUNT = "discount";
    const OPT_COMBO_TYPE_FREE = "free";

    const OPT_COMBO_APPLY_UNIT = "unit";
    const OPT_COMBO_APPLY_CATEGORY = "category";

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
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active = true;


    /**
     * @var \Food\DishesBundle\Entity\Place
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place", inversedBy="combo")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     */
    private $place;


    /**
     * @var \Food\DishesBundle\Entity\FoodCategory
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\FoodCategory", inversedBy="combo")
     * @ORM\JoinColumn(name="dish_category", referencedColumnName="id", nullable=true)
     */
    private $dishCategory;


    /**
     * @var \Food\DishesBundle\Entity\DishUnit
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\DishUnit")
     * @ORM\JoinColumn(name="dish_unit", referencedColumnName="id", nullable=true)
     */
    private $dishUnit = null;

    /**
     * @var string
     *
     * @ORM\Column(name="apply_by", type="string", length=30)
     */
    private $applyBy = null;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_type", type="string", length=30)
     */
    private $discountType;


    /**
     * @var string
     *
     * @ORM\Column(name="discount_size", type="integer", length=3)
     */
    private $discountSize;

    /**
     * @var string
     *
     * @ORM\Column(name="amount", type="integer", length=3)
     */
    private $amount;


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

    function __construct()
    {
        $this->dishCategory = null;
    }


    /**
     * Convert object to string
     *
     * @return string
     */
    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }
        return $this->getName();
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
     * @return ComboDiscount
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
     * @return ComboDiscount
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
     * Set discountType
     *
     * @param string $discountType
     * @return ComboDiscount
     */
    public function setDiscountType($discountType)
    {
        $this->discountType = $discountType;

        return $this;
    }

    /**
     * Get discountType
     *
     * @return string 
     */
    public function getDiscountType()
    {
        return $this->discountType;
    }

    /**
     * Set discountSize
     *
     * @param integer $discountSize
     * @return ComboDiscount
     */
    public function setDiscountSize($discountSize)
    {
        $this->discountSize = $discountSize;

        return $this;
    }

    /**
     * Get discountSize
     *
     * @return integer 
     */
    public function getDiscountSize()
    {
        return $this->discountSize;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     * @return ComboDiscount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ComboDiscount
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
     * @return ComboDiscount
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
     * @return ComboDiscount
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
     * @param \Food\DishesBundle\Entity\Place $place
     * @return ComboDiscount
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
     * Set dishCategory
     *
     * @param \Food\DishesBundle\Entity\FoodCategory $dishCategory
     * @return ComboDiscount
     */
    public function setDishCategory(\Food\DishesBundle\Entity\FoodCategory $dishCategory = null)
    {
        $this->dishCategory = $dishCategory;

        return $this;
    }

    /**
     * Get dishCategory
     *
     * @return \Food\DishesBundle\Entity\FoodCategory 
     */
    public function getDishCategory()
    {
        return $this->dishCategory;
    }

    /**
     * Set dishUnit
     *
     * @param \Food\DishesBundle\Entity\DishUnit $dishUnit
     * @return ComboDiscount
     */
    public function setDishUnit(\Food\DishesBundle\Entity\DishUnit $dishUnit = null)
    {
        $this->dishUnit = $dishUnit;

        return $this;
    }

    /**
     * Get dishUnit
     *
     * @return \Food\DishesBundle\Entity\DishUnit 
     */
    public function getDishUnit()
    {
        return $this->dishUnit;
    }

    /**
     * Set createdBy
     *
     * @param \Food\UserBundle\Entity\User $createdBy
     * @return ComboDiscount
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
     * @return ComboDiscount
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
     * @return ComboDiscount
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
     * Set applyBy
     *
     * @param string $applyBy
     * @return ComboDiscount
     */
    public function setApplyBy($applyBy)
    {
        $this->applyBy = $applyBy;

        return $this;
    }

    /**
     * Get applyBy
     *
     * @return string 
     */
    public function getApplyBy()
    {
        return $this->applyBy;
    }
}
