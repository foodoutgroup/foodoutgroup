<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="order_details_options")
 * @ORM\Entity
 */
class OrderDetailsOptions
{

    /**
     * @ORM\ManyToOne(targetEntity="Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $order_id;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Dish")
     * @ORM\JoinColumn(name="dish_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $dish_id;

    /**
     * @ORM\Column(name="dish_unit_id", type="integer")
     */
    private $dish_unit_id;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\DishOption")
     * @ORM\JoinColumn(name="dish_option_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $dish_option_id;

    /**
     * @ORM\Column(name="dish_name", type="string", length=255)
     */
    private $dish_option_name;

    /**
     * @ORM\Column(name="dish_option_code", type="string", length=255)
     */
    private $dish_option_code;

    /**
     * @ORM\Column(name="quantity", type="integer", length=3)
     */
    private $quantity;

    /**
     * @ORM\Column(name="price", type="decimal", scale=2)
     */
    private $price;


    /**
     * Set dish_option_name
     *
     * @param string $dishOptionName
     * @return OrderDetailsOptions
     */
    public function setDishOptionName($dishOptionName)
    {
        $this->dish_option_name = $dishOptionName;
    
        return $this;
    }

    /**
     * Get dish_option_name
     *
     * @return string 
     */
    public function getDishOptionName()
    {
        return $this->dish_option_name;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return OrderDetailsOptions
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    
        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer 
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set price
     *
     * @param float $price
     * @return OrderDetailsOptions
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
     * Set order_id
     *
     * @param \Food\OrderBundle\Entity\Order $orderId
     * @return OrderDetailsOptions
     */
    public function setOrderId(\Food\OrderBundle\Entity\Order $orderId)
    {
        $this->order_id = $orderId;
    
        return $this;
    }

    /**
     * Get order_id
     *
     * @return \Food\OrderBundle\Entity\Order 
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set dish_option_id
     *
     * @param integer $dishOptionId
     * @return OrderDetailsOptions
     */
    public function setDishOptionId($dishOptionId)
    {
        $this->dish_option_id = $dishOptionId;
    
        return $this;
    }

    /**
     * Get dish_option_id
     *
     * @return integer 
     */
    public function getDishOptionId()
    {
        return $this->dish_option_id;
    }

    /**
     * Set dish_id
     *
     * @param \Food\DishesBundle\Entity\Dish $dishId
     * @return OrderDetailsOptions
     */
    public function setDishId(\Food\DishesBundle\Entity\Dish $dishId)
    {
        $this->dish_id = $dishId;
    
        return $this;
    }

    /**
     * Get dish_id
     *
     * @return \Food\DishesBundle\Entity\Dish 
     */
    public function getDishId()
    {
        return $this->dish_id;
    }

    /**
     * Set dish_unit_id
     *
     * @param integer $dishUnitId
     * @return OrderDetailsOptions
     */
    public function setDishUnitId($dishUnitId)
    {
        $this->dish_unit_id = $dishUnitId;
    
        return $this;
    }

    /**
     * Get dish_unit_id
     *
     * @return integer 
     */
    public function getDishUnitId()
    {
        return $this->dish_unit_id;
    }

    /**
     * Set dish_option_code
     *
     * @param string $dishOptionCode
     * @return OrderDetailsOptions
     */
    public function setDishOptionCode($dishOptionCode)
    {
        $this->dish_option_code = $dishOptionCode;
    
        return $this;
    }

    /**
     * Get dish_option_code
     *
     * @return string 
     */
    public function getDishOptionCode()
    {
        return $this->dish_option_code;
    }
}
