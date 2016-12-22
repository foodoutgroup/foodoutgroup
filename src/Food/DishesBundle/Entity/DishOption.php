<?php

namespace Food\DishesBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;

/**
 * Dish option
 *
 * @ORM\Table(name="dish_option", indexes={@ORM\Index(name="deleted_at_idx", columns={"deleted_at"}), @ORM\Index(name="hidden_deleted_idx", columns={"hidden", "deleted_at"})})
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 * @Gedmo\TranslationEntity(class="Food\DishesBundle\Entity\DishOptionLocalized")
 */
class DishOption implements Translatable
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
     * @var double
     *
     * @ORM\Column(name="price", type="decimal", scale=2)
     */
    private $price;

    /**
     * @var double
     *
     * @ORM\Column(name="price_old", type="decimal", scale=2, nullable=true)
     */
    private $priceOld;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=60)
     */
    private $name;

    /**
     * @var string
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="name_to_nav", type="string", length=32, nullable=true)
     */
    private $nameToNav = null;

    /**
     * @var bool
     *
     * @ORM\Column(name="infocode", type="boolean", nullable=true)
     */
    private $infocode;

    /**
     * @var string
     * @ORM\Column(name="code", type="string", length=45, nullable=true)
     */
    private $code;

    /**
     * @var bool
     * @ORM\Column(name="first_level",  type="boolean", nullable=true)
     */
    private $firstLevel;

    /**
     * @var string
     * @ORM\Column(name="sub_code", type="string", length=45, nullable=true)
     */
    private $subCode;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var bool
     *
     * @ORM\Column(name="hidden", type="boolean")
     */
    private $hidden = false;

    /**
     * @var \Food\DishesBundle\Entity\DishOptionLocalized
     *
     * @ORM\OneToMany(targetEntity="DishOptionLocalized", mappedBy="object", cascade={"persist", "remove"})
     **/
    private $translations;

    /**
     * @ORM\ManyToMany(targetEntity="Dish", mappedBy="options")
     */
    private $dishes;

    /**
     * @var \Food\DishesBundle\Entity\Place
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     */
    private $place;

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
     * @ORM\Column(name="single_select", type="boolean")
     */
    private $singleSelect = false;

    /**
     * @var string
     * @ORM\Column(name="group_name", type="string", length=45, nullable=true)
     */
    private $groupName;

    /**
     * @var DishOptionSizePrice[]
     *
     * @ORM\OneToMany(targetEntity="DishOptionSizePrice", mappedBy="dish_option", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $sizesPrices;

    /**
     * Returns the name
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
     * Constructor
     */
    public function __construct()
    {
        $this->localized = new \Doctrine\Common\Collections\ArrayCollection();
        $this->dishes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set price
     *
     * @param float $price
     * @return DishOption
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
     * @return mixed
     */
    public function getPriceLocalized()
    {
        return str_replace('.', ',', $this->price);
    }

    /**
     * Set name
     *
     * @param string $name
     * @return DishOption
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
     * Set hidden
     *
     * @param boolean $hidden
     * @return DishOption
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get hidden
     *
     * @return boolean
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return DishOption
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
     * @return DishOption
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
     * @return DishOption
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
     * Add dishes
     *
     * @param \Food\DishesBundle\Entity\Dish $dishes
     * @return DishOption
     */
    public function addDishe(\Food\DishesBundle\Entity\Dish $dishes)
    {
        $this->dishes[] = $dishes;

        return $this;
    }

    /**
     * Remove dishes
     *
     * @param \Food\DishesBundle\Entity\Dish $dishes
     */
    public function removeDishe(\Food\DishesBundle\Entity\Dish $dishes)
    {
        $this->dishes->removeElement($dishes);
    }

    /**
     * Get dishes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDishes()
    {
        return $this->dishes;
    }

    /**
     * Add translations
     *
     * @param \Food\DishesBundle\Entity\DishOptionLocalized $translations
     * @return DishOption
     */
    public function addTranslation(\Food\DishesBundle\Entity\DishOptionLocalized $translations)
    {
        if (method_exists($this->translations, 'contains')) {
            if (!$this->translations->contains($translations)) {
                $this->translations[] = $translations;
                $translations->setObject($this);
            }
        }

        return $this;
    }

    /**
     * Remove translations
     *
     * @param \Food\DishesBundle\Entity\DishOptionLocalized $translations
     */
    public function removeTranslation(\Food\DishesBundle\Entity\DishOptionLocalized $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return DishOption
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set createdBy
     *
     * @param \Food\UserBundle\Entity\User $createdBy
     * @return DishOption
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
     * @return DishOption
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
     * @return DishOption
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
     * Set place
     *
     * @param \Food\DishesBundle\Entity\Place $place
     * @return DishOption
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
     * Set code
     *
     * @param string $code
     * @return DishOption
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
     * Add dishes
     *
     * @param \Food\DishesBundle\Entity\Dish $dishes
     * @return DishOption
     */
    public function addDish(\Food\DishesBundle\Entity\Dish $dishes)
    {
        $this->dishes[] = $dishes;

        return $this;
    }

    /**
     * Remove dishes
     *
     * @param \Food\DishesBundle\Entity\Dish $dishes
     */
    public function removeDish(\Food\DishesBundle\Entity\Dish $dishes)
    {
        $this->dishes->removeElement($dishes);
    }

    /**
     * Set singleSelect
     *
     * @param integer $singleSelect
     * @return DishOption
     */
    public function setSingleSelect($singleSelect)
    {
        $this->singleSelect = $singleSelect;

        return $this;
    }

    /**
     * Get singleSelect
     *
     * @return integer
     */
    public function getSingleSelect()
    {
        return $this->singleSelect;
    }

    /**
     * Set groupName
     *
     * @param string $groupName
     * @return DishOption
     */
    public function setGroupName($groupName)
    {
        $this->groupName = $groupName;

        return $this;
    }

    /**
     * Get groupName
     *
     * @return string
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * Set infocode
     *
     * @param boolean $infocode
     * @return DishOption
     */
    public function setInfocode($infocode)
    {
        $this->infocode = $infocode;

        return $this;
    }

    /**
     * Get infocode
     *
     * @return boolean
     */
    public function getInfocode()
    {
        return $this->infocode;
    }

    /**
     * Set subCode
     *
     * @param string $subCode
     * @return DishOption
     */
    public function setSubCode($subCode)
    {
        $this->subCode = $subCode;

        return $this;
    }

    /**
     * Get subCode
     *
     * @return string
     */
    public function getSubCode()
    {
        return $this->subCode;
    }

    /**
     * Set priceOld
     *
     * @param string $priceOld
     * @return DishOption
     */
    public function setPriceOld($priceOld)
    {
        $this->priceOld = $priceOld;

        return $this;
    }

    /**
     * Get priceOld
     *
     * @return string
     */
    public function getPriceOld()
    {
        return $this->priceOld;
    }

    /**
     * Set firstLevel
     *
     * @param boolean $firstLevel
     * @return DishOption
     */
    public function setFirstLevel($firstLevel)
    {
        $this->firstLevel = $firstLevel;

        return $this;
    }

    /**
     * Get firstLevel
     *
     * @return boolean
     */
    public function getFirstLevel()
    {
        return $this->firstLevel;
    }

    /**
     * Add sizes
     *
     * @param \Food\DishesBundle\Entity\DishOptionSizePrice $sizes
     * @return Dish
     */
    public function addSizesPrice(\Food\DishesBundle\Entity\DishOptionSizePrice $sizePrice)
    {
        $this->sizesPrices[] = $sizePrice;

        return $this;
    }

    /**
     * Remove sizes
     *
     * @param \Food\DishesBundle\Entity\DishOptionSizePrice $sizes
     */
    public function removeSizesPrice(\Food\DishesBundle\Entity\DishOptionSizePrice $sizePrice)
    {
        $this->sizesPrices->removeElement($sizePrice);
    }

    /**
     * Get sizes
     *
     * @return DishOptionSizePrice[]
     */
    public function getSizesPrices()
    {
        return $this->sizesPrices;
    }

    /**
     * Set nameToNav
     *
     * @param string $nameToNav
     * @return DishOption
     */
    public function setNameToNav($nameToNav)
    {
        $this->nameToNav = $nameToNav;
    
        return $this;
    }

    /**
     * Get nameToNav
     *
     * @return string 
     */
    public function getNameToNav()
    {
        return $this->nameToNav;
    }
}