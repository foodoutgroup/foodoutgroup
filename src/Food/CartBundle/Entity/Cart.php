<?php

namespace Food\CartBundle\Entity;

use Symfony\Bridge\Doctrine;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Table(name="cart", uniqueConstraints={@ORM\UniqueConstraint(name="unique_id", columns={"session", "dish_id"})})
 * @ORM\Entity
 */
class Cart
{

    /**
     * @ORM\Column(name="session", type="string", length=255)
     * @ORM\Id
     */
    private $session;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Dish", inversedBy="dish_id")
     * @ORM\JoinColumn(name="dish_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $dish_id;

    /**
     * @var
     */
    private $options;

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