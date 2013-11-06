<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Dish option
 *
 * @ORM\Table(name="dish_option")
 * @ORM\Entity
 */
class DishOption
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
     * @ORM\Column(name="name", type="string", length=45)
     */
    private $name;

    /**
     * @var bool
     *
     * @ORM\Column(name="hidden", type="boolean")
     */
    private $hidden;

    /**
     * @var \Food\DishesBundle\Entity\DishOptionLocalized
     *
     * @ORM\OneToMany(targetEntity="DishOptionLocalized", mappedBy="id")
     **/
    private $localized;

    /**
     * @ORM\ManyToMany(targetEntity="Dish", mappedBy="dishoption")
     */
    private $dishes;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->localized = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add localized
     *
     * @param \Food\DishesBundle\Entity\DishOptionLocalized $localized
     * @return DishOption
     */
    public function addLocalized(\Food\DishesBundle\Entity\DishOptionLocalized $localized)
    {
        $this->localized[] = $localized;
    
        return $this;
    }

    /**
     * Remove localized
     *
     * @param \Food\DishesBundle\Entity\DishOptionLocalized $localized
     */
    public function removeLocalized(\Food\DishesBundle\Entity\DishOptionLocalized $localized)
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
     * TODO
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}