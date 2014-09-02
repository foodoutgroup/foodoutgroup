<?php

namespace Food\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Shopping basket magic for da smthing like Mobile :D
 *
 * @ORM\Table(name="shopping_basket_relation")
 * @ORM\Entity
 */
class ShoppingBasketRelation
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="session", type="string", length=255)
     */
    private $session;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     */
    private $place_id;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set session
     *
     * @param string $session
     * @return ShoppingBasketRelation
     */
    public function setSession($session)
    {
        $this->session = $session;
    
        return $this;
    }

    /**
     * Get session
     *
     * @return string 
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set place_id
     *
     * @param \Food\DishesBundle\Entity\Place $placeId
     * @return ShoppingBasketRelation
     */
    public function setPlaceId(\Food\DishesBundle\Entity\Place $placeId = null)
    {
        $this->place_id = $placeId;
    
        return $this;
    }

    /**
     * Get place_id
     *
     * @return \Food\DishesBundle\Entity\Place 
     */
    public function getPlaceId()
    {
        return $this->place_id;
    }
}