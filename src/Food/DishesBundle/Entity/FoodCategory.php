<?php

namespace Food\DishesBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;

/**
 * Food category
 *
 * @ORM\Table(name="food_category", indexes={@ORM\Index(name="active_idx", columns={"active"}), @ORM\Index(name="active_deleted_idx", columns={"active", "deleted_at"})})
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 * @Gedmo\TranslationEntity(class="Food\DishesBundle\Entity\FoodCategoryLocalized")
 */
class FoodCategory implements Translatable
{


    const SLUG_TYPE = 'food_category';

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
     * @var \Food\DishesBundle\Entity\Place
     *
     * @ORM\ManyToOne(targetEntity="Place", inversedBy="categories")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     */
    private $place;


    /**
     * @var bool
     *
     * @ORM\Column(name="texts_only", type="boolean", nullable=true)
     */
    private $textsOnly;

    /**
     * @var bool
     *
     * @ORM\Column(name="drinks", type="boolean")
     */
    private $drinks;

    /**
     * @var bool
     *
     * @ORM\Column(name="alcohol", type="boolean")
     */
    private $alcohol;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

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
     * @var
     * @ORM\Column(name="lineup", type="integer", nullable=true)
     */
    private $lineup;

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
     * @var \Food\DishesBundle\Entity\FoodCategoryLocalized
     *
     * @ORM\OneToMany(targetEntity="FoodCategoryLocalized", mappedBy="object", cascade={"persist", "remove"})
     **/
    private $translations;

    /**
     * @ORM\ManyToMany(targetEntity="Dish", mappedBy="categories")
     */
    private $dishes;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string")
     */
    private $slug;


    /**
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
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @param \Doctrine\ORM\EntityManager $em
     * @return string
     */
    public function getOrigName(\Doctrine\ORM\EntityManager $em)
    {
        $query = $em->createQuery("SELECT o.name FROM FoodDishesBundle:FoodCategory as o WHERE o.id=:id")
            ->setParameter('id', $this->getId());
        try{
            $res = ($query->getSingleResult());
            return $res['name'];
        } catch (\Doctrine\ORM\NoResultException $e) {
            return $this->getName();
        }
    }

    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return FoodCategory
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
     * @return FoodCategory
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
     * @return FoodCategory
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
     * @return FoodCategory
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
     * @return FoodCategory
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
     * @return FoodCategory
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
     * @return FoodCategory
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
     * @param $locale
     */
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Add translations
     *
     * @param \Food\DishesBundle\Entity\FoodCategoryLocalized $t
     * @return Dish
     */
    public function addTranslation(\Food\DishesBundle\Entity\FoodCategoryLocalized $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    /**
     * Remove translations
     *
     * @param \Food\DishesBundle\Entity\FoodCategoryLocalized $translations
     */
    public function removeTranslation(\Food\DishesBundle\Entity\FoodCategoryLocalized $translations)
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
     * Add dishes
     *
     * @param \Food\DishesBundle\Entity\Dish $dishes
     * @return FoodCategory
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
     * Set createdBy
     *
     * @param \Food\UserBundle\Entity\User $createdBy
     * @return FoodCategory
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
     * @return FoodCategory
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
     * @return FoodCategory
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
     * Add dishes
     *
     * @param \Food\DishesBundle\Entity\Dish $dishes
     * @return FoodCategory
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

    public function getActiveDishesCount()
    {
        $dishes = $this->getDishes()->filter(
            function($dish) {
                return $dish->getActive();
            }
        );
        return sizeof($dishes);
    }

    /**
     * Set drinks
     *
     * @param boolean $drinks
     * @return FoodCategory
     */
    public function setDrinks($drinks)
    {
        $this->drinks = $drinks;
    
        return $this;
    }

    /**
     * Get drinks
     *
     * @return boolean 
     */
    public function getDrinks()
    {
        return $this->drinks;
    }

    /**
     * Set alcohol
     *
     * @param boolean $alcohol
     * @return FoodCategory
     */
    public function setAlcohol($alcohol)
    {
        $this->alcohol = $alcohol;
    
        return $this;
    }

    /**
     * Get alcohol
     *
     * @return boolean 
     */
    public function getAlcohol()
    {
        return $this->alcohol;
    }

    /**
     * Set lineup
     *
     * @param integer $lineup
     * @return FoodCategory
     */
    public function setLineup($lineup)
    {
        $this->lineup = $lineup;
    
        return $this;
    }

    /**
     * Get lineup
     *
     * @return integer 
     */
    public function getLineup()
    {
        return $this->lineup;
    }

    /**
     * Set textsOnly
     *
     * @param boolean $textsOnly
     * @return FoodCategory
     */
    public function setTextsOnly($textsOnly)
    {
        $this->textsOnly = $textsOnly;
    
        return $this;
    }

    /**
     * Get textsOnly
     *
     * @return boolean 
     */
    public function getTextsOnly()
    {
        return $this->textsOnly;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return FoodCategory
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    
        return $this;
    }
}