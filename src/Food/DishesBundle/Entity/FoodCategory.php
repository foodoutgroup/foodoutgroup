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
     * @var string
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="active", type="integer", length=1)
     */
    private $active;

    /**
     * @var string|null
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

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
     * Set date
     *
     * @param \DateTime $date
     * @return FoodCategory
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set active
     *
     * @param integer $active
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
     * @return integer 
     */
    public function getActive()
    {
        return $this->active;
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