<?php

namespace Food\CartBundle\Entity;

use Doctrine\ORM\EntityManager;
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
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Dish")
     * @ORM\JoinColumn(name="dish_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $dish_id;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\DishSize")
     * @ORM\JoinColumn(name="dish_size_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $dish_size_id;

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

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function setEm($em)
    {
        $this->em = $em;
    }

    /**
     * DFQ cia sugalvojau - reik permastyt...
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * @param $quantity
     * @return $this
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


    /**
     * @return array|CartOption[]
     */
    public function getOptions()
    {
        return $this->getEm()->getRepository('FoodCartBundle:CartOption')
            ->findBy(
                array(
                    'dish_id' => $this->getDishId(),
                    'session' => $this->getSession()
                )
            );
    }


    /**
     * Set dish_size_id
     *
     * @param \Food\DishesBundle\Entity\DishSize $dishSizeId
     * @return Cart
     */
    public function setDishSizeId(\Food\DishesBundle\Entity\DishSize $dishSizeId)
    {
        $this->dish_size_id = $dishSizeId;
    
        return $this;
    }

    /**
     * Get dish_size_id
     *
     * @return \Food\DishesBundle\Entity\DishSize 
     */
    public function getDishSizeId()
    {
        return $this->dish_size_id;
    }
}
