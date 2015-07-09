<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DishDate
 *
 * @ORM\Table(name="dish_date")
 * @ORM\Entity
 */
class DishDate
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
     * @var \DateTime
     *
     * @ORM\Column(name="start", type="date")
     */
    private $start;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end", type="date")
     */
    private $end;

    /**
     * @ORM\ManyToOne(targetEntity="Dish", inversedBy="dates")
     * @ORM\JoinColumn(name="dish", referencedColumnName="id")
     *
     * @var Dish
     */
    private $dish;

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
     * Set startdate
     *
     * @param \DateTime $start
     * @return DishDate
     */
    public function setStart($start)
    {
        $this->start = $start;
    
        return $this;
    }

    /**
     * Get start
     *
     * @return \DateTime 
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set end
     *
     * @param \DateTime $end
     * @return DishDate
     */
    public function setEnd($end)
    {
        $this->end = $end;
    
        return $this;
    }

    /**
     * Get end
     *
     * @return \DateTime 
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set dish
     *
     * @param \Food\DishesBundle\Entity\Dish $dish
     * @return DishDate
     */
    public function setDish(\Food\DishesBundle\Entity\Dish $dish = null)
    {
        $this->dish = $dish;
    
        return $this;
    }

    /**
     * Get dish
     *
     * @return \Food\DishesBundle\Entity\Dish 
     */
    public function getDish()
    {
        return $this->dish;
    }
}