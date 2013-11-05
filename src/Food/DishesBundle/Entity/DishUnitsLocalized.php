<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Dish units localized
 *
 * @ORM\Table(name="dish_units_localized")
 * @ORM\Entity
 */
class DishUnitsLocalized
{
    /**
     * @var \Food\DishesBundle\Entity\DishUnit
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="DishUnit", inversedBy="dishunit")
     * @ORM\JoinColumn(name="dish_unit_id", referencedColumnName="id")
     */
    private $dishUnit;

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
     * Set language
     *
     * @param integer $language
     * @return DishUnitsLocalized
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
     * @return DishUnitsLocalized
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
     * Set dishUnit
     *
     * @param \Food\DishesBundle\Entity\DishUnit $dishUnit
     * @return DishUnitsLocalized
     */
    public function setDishUnit(\Food\DishesBundle\Entity\DishUnit $dishUnit)
    {
        $this->dishUnit = $dishUnit;
    
        return $this;
    }

    /**
     * Get dishUnit
     *
     * @return \Food\DishesBundle\Entity\DishUnit 
     */
    public function getDishUnit()
    {
        return $this->dishUnit;
    }
}