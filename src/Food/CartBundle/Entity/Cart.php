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
     */
    private $sesion_id;

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
     * Set sesion_id
     *
     * @param string $sesionId
     * @return Cart
     */
    public function setSesionId($sesionId)
    {
        $this->sesion_id = $sesionId;
    
        return $this;
    }

    /**
     * Get sesion_id
     *
     * @return string 
     */
    public function getSesionId()
    {
        return $this->sesion_id;
    }
}