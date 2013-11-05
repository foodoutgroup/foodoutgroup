<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Food categories localized
 *
 * @ORM\Table(name="food_categories_localized")
 * @ORM\Entity
 */
class FoodCategoryLocalized
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45)
     */
    private $name;

    /**
     * @var \Food\DishesBundle\Entity\FoodCategory
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="FoodCategory", inversedBy="foodcategory")
     * @ORM\JoinColumn(name="food_category_id", referencedColumnName="id")
     */
    private $category;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="lang", type="integer", length=1)
     */
    private $language;


    /**
     * Set name
     *
     * @param string $name
     * @return FoodCategoryLocalized
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
     * Set language
     *
     * @param integer $language
     * @return FoodCategoryLocalized
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
     * Set category
     *
     * @param \Food\DishesBundle\Entity\FoodCategory $category
     * @return FoodCategoryLocalized
     */
    public function setCategory(\Food\DishesBundle\Entity\FoodCategory $category)
    {
        $this->category = $category;
    
        return $this;
    }

    /**
     * Get category
     *
     * @return \Food\DishesBundle\Entity\FoodCategory 
     */
    public function getCategory()
    {
        return $this->category;
    }
}