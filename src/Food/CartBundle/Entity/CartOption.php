<?php

namespace Food\CartBundle\Entity;

use Symfony\Bridge\Doctrine;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="cart_option", uniqueConstraints={@ORM\UniqueConstraint(name="unique_id", columns={"session", "dish_id", "dish_option_id"})})
 * @ORM\Entity
 */
class CartOption
{
    /**
     * @ORM\Column(name="session", type="string", length=255)
     */
    private $session;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Dish")
     * @ORM\JoinColumn(name="dish_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $dish_id;

    /**
     * @ORM\Column(name="quantity", type="integer", length=3)
     */
    private $quantity;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\DishOption")
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

    /**
     * @param mixed $session
     * @return $this
     */
    public function setSession($session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSession()
    {
        return $this->session;
    }
}