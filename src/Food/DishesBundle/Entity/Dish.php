<?php

namespace Food\DishesBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;

/**
 * Dish
 *
 * @ORM\Table(name="dish")
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
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var \Food\DishesBundle\Entity\Place
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place", inversedBy="dishes")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     */
    private $place;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DishSize", mappedBy="dish", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $sizes;

    /**
     * @var string
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

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
     * @var string|null
     *
     * @ORM\Column(name="edited_at", type="datetime", nullable=true)
     */
    private $editedAt;

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
     * @var \Food\DishesBundle\Entity\DishLocalized
     *
     * @ORM\OneToMany(targetEntity="DishLocalized", mappedBy="object", cascade={"persist", "remove"})
     **/
    private $translations;

    /**
     * @ORM\ManyToMany(targetEntity="FoodCategory", inversedBy="dishes")
     * @ORM\JoinTable(name="food_category_dish_map")
     */
    private $categories;

    /**
     * @ORM\ManyToMany(targetEntity="DishOption", inversedBy="dishes")
     * @ORM\JoinTable(name="dish_option_map")
     */
    private $options;

    /**
     * @var bool
     *
     * @ORM\Column(name="recomended", type="boolean")
     */
    private $recomended = false;

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
     * Returns the name
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

    /**
     * Set createdBy
     *
     * @param \Food\UserBundle\Entity\User $createdBy
     * @return Dish
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
     * @return Dish
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
     * @return Dish
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
     * Set recomended
     *
     * @param boolean $recomended
     * @return Dish
     */
    public function setRecomended($recomended)
    {
        $this->recomended = $recomended;
    
        return $this;
    }

    /**
     * Get recomended
     *
     * @return boolean 
     */
    public function getRecomended()
    {
        return $this->recomended;
    }

    /**
     * Add sizes
     *
     * @param \Food\DishesBundle\Entity\DishSize $sizes
     * @return Dish
     */
    public function addSize(\Food\DishesBundle\Entity\DishSize $sizes)
    {
        $this->sizes[] = $sizes;
    
        return $this;
    }

    /**
     * Remove sizes
     *
     * @param \Food\DishesBundle\Entity\DishSize $sizes
     */
    public function removeSize(\Food\DishesBundle\Entity\DishSize $sizes)
    {
        $this->sizes->removeElement($sizes);
    }

    /**
     * Get sizes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSizes()
    {
        return $this->sizes;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Dish
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
}