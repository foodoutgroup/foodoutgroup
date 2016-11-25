<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="dish_option_size_price")
 * @ORM\Entity
 */
class DishOptionSizePrice {

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \Food\DishesBundle\Entity\DishOption
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\DishOption")
     */
    private $dish_option;

    /**
     * @var \Food\DishesBundle\Entity\DishUnit
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\DishUnit")
     */
    private $unit;

    /**
     * @var double
     *
     * @ORM\Column(name="price", type="decimal", scale=2)
     */
    private $price;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return DishOption
     */
    public function getDishOption()
    {
        return $this->dish_option;
    }

    /**
     * @param DishOption $dish_option
     */
    public function setDishOption($dish_option)
    {
        $this->dish_option = $dish_option;
    }

    /**
     * @return DishUnit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param DishUnit $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }
}