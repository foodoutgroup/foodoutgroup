<?php

namespace Food\CartBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Table(name="cart")
 * @ORM\Entity
 */
class Cart
{
    /**
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;

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
     * Set quantity
     *
     * @param integer $quantity
     * @return Cart
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
     * @return Cart
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
     * @return Cart
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
}