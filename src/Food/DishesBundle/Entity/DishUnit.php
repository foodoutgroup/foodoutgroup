<?php

namespace Food\DishesBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;

/**
 * Dish unit
 *
 * @ORM\Table(name="dish_unit")
 * @ORM\Entity
 * @Gedmo\TranslationEntity(class="Food\DishesBundle\Entity\DishUnitsLocalized")
 */
class DishUnit implements Translatable
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
     * @Gedmo\Translatable
     * @ORM\Column(name="name", type="string", length=45)
     */
    private $name;

    /**
     * @var string
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="name_to_nav", type="string", length=32, nullable=true)
     */
    private $nameToNav;

    /**
     * @var string
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="short_name", type="string", length=45)
     */
    private $shortName;


    /**
     * @ORM\ManyToOne(targetEntity="Place")
     * @ORM\JoinColumn(name="place", referencedColumnName="id")
     *
     * @var Place
     */
    private $place;

    /**
     * @var DishUnitCategory
     * @ORM\ManyToOne(targetEntity="DishUnitCategory")
     * @ORM\JoinColumn(name="unitCategory", referencedColumnName="id")
     */
    private $unitCategory;

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
     * @var \Food\DishesBundle\Entity\DishUnitsLocalized
     *
     * @ORM\OneToMany(targetEntity="DishUnitsLocalized", mappedBy="object", cascade={"persist", "remove"})
     **/
    private $translations;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * @var string|null
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

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
        $this->dishes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return string
     */
    public function getGroupAndCompany()
    {
        return $this->getPlace()->getName() . " - " . $this->getUnitCategory()->getName();
    }

    /**
     * Get units group name
     * @return string
     */
    public function getGroup()
    {
        return $this->getUnitCategory()->getName();
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return DishUnit
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * @return DishUnit
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return DishUnit
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
     * @return DishUnit
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
     * @param $locale
     */
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Add translations
     *
     * @param \Food\DishesBundle\Entity\DishUnitsLocalized $t
     * @return Dish
     */
    public function addTranslation(\Food\DishesBundle\Entity\DishUnitsLocalized $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    /**
     * Remove translations
     *
     * @param \Food\DishesBundle\Entity\DishUnitsLocalized $translations
     */
    public function removeTranslation(\Food\DishesBundle\Entity\DishUnitsLocalized $translations)
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
     * Set createdBy
     *
     * @param \Food\UserBundle\Entity\User $createdBy
     * @return DishUnit
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
     * @return DishUnit
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
     * Set place
     *
     * @param \Food\DishesBundle\Entity\Place $place
     * @return DishUnit
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
     * Set unitCategory
     *
     * @param \Food\DishesBundle\Entity\DishUnitCategory $unitCategory
     * @return DishUnit
     */
    public function setUnitCategory(\Food\DishesBundle\Entity\DishUnitCategory $unitCategory = null)
    {
        $this->unitCategory = $unitCategory;

        return $this;
    }

    /**
     * Get unitCategory
     *
     * @return \Food\DishesBundle\Entity\DishUnitCategory
     */
    public function getUnitCategory()
    {
        return $this->unitCategory;
    }

    /**
     * Set shortName
     *
     * @param string $shortName
     * @return DishUnit
     */
    public function setShortName($shortName)
    {
        $this->shortName = $shortName;

        return $this;
    }

    /**
     * Get shortName
     *
     * @return string
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * Set nameToNav
     *
     * @param string $nameToNav
     * @return DishUnit
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

    /**
     * @return null|string
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param null|string $deletedAt
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }
}