<?php

namespace Food\CartBundle\Entity;

use Symfony\Bridge\Doctrine;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="cart_option")
 * @ORM\Entity
 */
class CartOption
{
    /**
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User", inversedBy="user")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Dish", inversedBy="dish_id")
     * @ORM\JoinColumn(name="dish_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $dish_id;

    /**
     * @ORM\Column(name="quantity", type="integer", length=3)
     */
    private $quantity;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\DishOption", inversedBy="dish_option_id")
     * @ORM\JoinColumn(name="dish_option_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $dish_option_id;

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return CartOption
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
     * Set user
     *
     * @param \Food\UserBundle\Entity\User $user
     * @return CartOption
     */
    public function setUser(\Food\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \Food\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set dish_id
     *
     * @param \Food\DishesBundle\Entity\Dish $dishId
     * @return CartOption
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
     * Set dish_option_id
     *
     * @param \Food\DishesBundle\Entity\DishOption $dishOptionId
     * @return CartOption
     */
    public function setDishOptionId(\Food\DishesBundle\Entity\DishOption $dishOptionId)
    {
        $this->dish_option_id = $dishOptionId;
    
        return $this;
    }

    /**
     * Get dish_option_id
     *
     * @return \Food\DishesBundle\Entity\DishOption 
     */
    public function getDishOptionId()
    {
        return $this->dish_option_id;
    }
}