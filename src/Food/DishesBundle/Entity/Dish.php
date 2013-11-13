<?php

namespace Food\DishesBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;

/**
 * Dish
 *
 * @ORM\Table()
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 * @Gedmo\TranslationEntity(class="Food\DishesBundle\Entity\DishLocalized")
 */
class Dish implements Translatable
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
     * @var string
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="name", type="string", length=45)
     */
    private $name;

    /**
     * @var \Food\DishesBundle\Entity\Place
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place", inversedBy="place")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     */
    private $place;

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
     * @var integer TODO User Entity!
     *
     * @ORM\Column(name="created_by", type="integer")
     */
    private $createdBy;

    /**
     * @var integer TODO User Entity!
     *
     * @ORM\Column(name="edited_by", type="integer", nullable=true)
     */
    private $editedBy;

    /**
     * @var integer TODO User Entity!
     *
     * @ORM\Column(name="deleted_by", type="integer", nullable=true)
     */
    private $deletedBy;

    /**
     * @var \Food\DishesBundle\Entity\DishLocalized
     *
     * @ORM\OneToMany(targetEntity="DishLocalized", mappedBy="object", cascade={"persist", "remove"})
     **/
    private $translations;

    /**
     * @ORM\ManyToMany(targetEntity="FoodCategory", inversedBy="foodcategory")
     * @ORM\JoinTable(name="food_category_dish_map")
     */
    private $categories;

    /**
     * @ORM\ManyToOne(targetEntity="DishUnit", inversedBy="dishunit")
     */
    private $unit;

    /**
     * @ORM\ManyToMany(targetEntity="DishOption", inversedBy="dishoption")
     * @ORM\JoinTable(name="dish_option_map")
     */
    private $options;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * Constructor
     */
    public function __construct()
    {
        // This is just the beginning
        $this->setCreatedAt(new \DateTime('now'));

        $this->categories = new \Doctrine\Common\Collections\ArrayCollection();
        $this->place = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * TODO
     * @return string
     */
    public function __toString()
    {
        // TODO return localized
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
     * Set price
     *
     * @param float $price
     * @return Dish
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
     * Set name
     *
     * @param string $name
     * @return Dish
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
     * @return Dish
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
     * @return Dish
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
     * @return Dish
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
     * Set createdBy
     *
     * @param integer $createdBy
     * @return Dish
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    
        return $this;
    }

    /**
     * Get createdBy
     *
     * @return integer 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set editedBy
     *
     * @param integer $editedBy
     * @return Dish
     */
    public function setEditedBy($editedBy)
    {
        $this->editedBy = $editedBy;
    
        return $this;
    }

    /**
     * Get editedBy
     *
     * @return integer 
     */
    public function getEditedBy()
    {
        return $this->editedBy;
    }

    /**
     * Set deletedBy
     *
     * @param integer $deletedBy
     * @return Dish
     */
    public function setDeletedBy($deletedBy)
    {
        $this->deletedBy = $deletedBy;
    
        return $this;
    }

    /**
     * Get deletedBy
     *
     * @return integer 
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * Set place
     *
     * @param \Food\DishesBundle\Entity\Place $place
     * @return Dish
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
     * Add categories
     *
     * @param \Food\DishesBundle\Entity\FoodCategory $categories
     * @return Dish
     */
    public function addCategorie(\Food\DishesBundle\Entity\FoodCategory $categories)
    {
        $this->categories[] = $categories;
    
        return $this;
    }

    /**
     * Remove categories
     *
     * @param \Food\DishesBundle\Entity\FoodCategory $categories
     */
    public function removeCategorie(\Food\DishesBundle\Entity\FoodCategory $categories)
    {
        $this->categories->removeElement($categories);
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Add options
     *
     * @param \Food\DishesBundle\Entity\DishOption $options
     * @return Dish
     */
    public function addOption(\Food\DishesBundle\Entity\DishOption $options)
    {
        $this->options[] = $options;
    
        return $this;
    }

    /**
     * Remove options
     *
     * @param \Food\DishesBundle\Entity\DishOption $options
     */
    public function removeOption(\Food\DishesBundle\Entity\DishOption $options)
    {
        $this->options->removeElement($options);
    }

    /**
     * Get options
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOptions()
    {
        return $this->options;
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
     * @param \Food\DishesBundle\Entity\DishLocalized $t
     * @return Dish
     */
    public function addTranslation(\Food\DishesBundle\Entity\DishLocalized $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    /**
     * Remove translations
     *
     * @param \Food\DishesBundle\Entity\DishLocalized $translations
     */
    public function removeTranslation(\Food\DishesBundle\Entity\DishLocalized $translations)
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
     * Set unit
     *
     * @param \Food\DishesBundle\Entity\DishUnit $unit
     * @return Dish
     */
    public function setUnit(\Food\DishesBundle\Entity\DishUnit $unit = null)
    {
        $this->unit = $unit;
    
        return $this;
    }

    /**
     * Get unit
     *
     * @return \Food\DishesBundle\Entity\DishUnit 
     */
    public function getUnit()
    {
        return $this->unit;
    }
}