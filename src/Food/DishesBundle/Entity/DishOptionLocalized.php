<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Dish option
 *
 * @ORM\Table(name="dish_option_localized")
 * @ORM\Entity
 */
class DishOptionLocalized
{
    /**
     * @var \Food\DishesBundle\Entity\DishOption
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="DishOption", inversedBy="dishoption")
     * @ORM\JoinColumn(name="dish_option_id", referencedColumnName="id")
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
     * @return DishOptionLocalized
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
     * @return DishOptionLocalized
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
     * @return DishOptionLocalized
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
     * @param \Food\DishesBundle\Entity\DishOption $dish
     * @return DishOptionLocalized
     */
    public function setDish(\Food\DishesBundle\Entity\DishOption $dish)
    {
        $this->dish = $dish;
    
        return $this;
    }

    /**
     * Get dish
     *
     * @return \Food\DishesBundle\Entity\DishOption 
     */
    public function getDish()
    {
        return $this->dish;
    }
}