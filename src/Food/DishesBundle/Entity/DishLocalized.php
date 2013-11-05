<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Dishes localized
 *
 * @ORM\Table(name="dish_localized")
 * @ORM\Entity
 */
class DishLocalized
{
    /**
     * @var \Food\DishesBundle\Entity\Dish
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Dish", inversedBy="dish")
     * @ORM\JoinColumn(name="dish_id", referencedColumnName="id")
     */
    private $dish;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="lang", type="integer", length=1)
     */
    private $language;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * Set language
     *
     * @param integer $language
     * @return DishLocalized
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    
        return $this;
    }

    /**
     * Get language
     *
     * @return integer 
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return DishLocalized
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
     * Set description
     *
     * @param string $description
     * @return DishLocalized
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
     * Set dish
     *
     * @param \Food\DishesBundle\Entity\Dish $dish
     * @return DishLocalized
     */
    public function setDish(\Food\DishesBundle\Entity\Dish $dish)
    {
        $this->dish = $dish;
    
        return $this;
    }

    /**
     * Get dish
     *
     * @return \Food\DishesBundle\Entity\Dish 
     */
    public function getDish()
    {
        return $this->dish;
    }
}