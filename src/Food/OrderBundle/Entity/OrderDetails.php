<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Table(name="order_details")
 * @ORM\Entity
 */
class OrderDetails
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
     * @ORM\Column(name="dish_name", type="string", length=255)
     */
    private $dish_name;

    /**
     * @ORM\Column(name="quantity", type="integer", length=3)
     */
    private $quantity;

    /**
     * @ORM\Column(name="price", type="decimal", scale=2)
     */
    private $price;


    /**
     * Set dish_name
     *
     * @param string $dishName
     * @return OrderDetails
     */
    public function setDishName($dishName)
    {
        $this->dish_name = $dishName;
    
        return $this;
    }

    /**
     * Get dish_name
     *
     * @return string 
     */
    public function getDishName()
    {
        return $this->dish_name;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return OrderDetails
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
     * @return OrderDetails
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
     * @return OrderDetails
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
     * Set dish_id
     *
     * @param integer $dishId
     * @return OrderDetails
     */
    public function setDishId($dishId)
    {
        $this->dish_id = $dishId;
    
        return $this;
    }

    /**
     * Get dish_id
     *
     * @return integer 
     */
    public function getDishId()
    {
        return $this->dish_id;
    }
}