<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Food category
 *
 * @ORM\Table(name="food_category")
 * @ORM\Entity
 */
class FoodCategory
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
     * @ORM\Column(name="name", type="string", length=45)
     */
    private $name;

    /**
     * @var \Food\DishesBundle\Entity\Place
     *
     * @ORM\ManyToOne(targetEntity="Place", inversedBy="place")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     */
    private $place;

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
     * @var \Food\DishesBundle\Entity\FoodCategoryLocalized
     *
     * @ORM\OneToMany(targetEntity="FoodCategoryLocalized", mappedBy="id")
     **/
    private $localized;

    /**
     * @ORM\ManyToMany(targetEntity="Dish", mappedBy="foodcategories")
     */
    private $dishes;

    public function __toString()
    {
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
     * Set createdBy
     *
     * @param integer $createdBy
     * @return FoodCategory
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
     * @return FoodCategory
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
     * @return FoodCategory
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
     * Add localized
     *
     * @param \Food\DishesBundle\Entity\FoodCategoryLocalized $localized
     * @return FoodCategory
     */
    public function addLocalized(\Food\DishesBundle\Entity\FoodCategoryLocalized $localized)
    {
        $this->localized[] = $localized;
    
        return $this;
    }

    /**
     * Remove localized
     *
     * @param \Food\DishesBundle\Entity\FoodCategoryLocalized $localized
     */
    public function removeLocalized(\Food\DishesBundle\Entity\FoodCategoryLocalized $localized)
    {
        $this->localized->removeElement($localized);
    }

    /**
     * Get localized
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getLocalized()
    {
        return $this->localized;
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
}