<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orders_to_restaurant")
 * @ORM\Entity(repositoryClass="Food\OrderBundle\Entity\OrderToRestaurantRepository")
 */
class OrderToRestaurant
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
     * @ORM\ManyToOne(targetEntity="\Food\OrderBundle\Entity\Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     **/
    private $order;

    /**
     * @var string
     * @ORM\Column(name="state", type="string", length=32)
     */
    private $state;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_added", type="datetime")
     */
    private $dateAdded;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_sent", type="datetime", nullable=true)
     */
    private $dateSent;

    /**
     * @var integer
     * @ORM\Column(name="try_count", type="integer", nullable=true)
     */
    private $tryCount;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_failed", type="datetime", nullable=true)
     */
    private $dateFailed;

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
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return OrderToRestaurant
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;
    
        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime 
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }


    /**
     * Set dateSent
     *
     * @param \DateTime $dateSent
     * @return OrderToRestaurant
     */
    public function setDateSent($dateSent)
    {
        $this->dateSent = $dateSent;

        return $this;
    }

    /**
     * Get dateSent
     *
     * @return \DateTime
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * @return int
     */
    public function getTryCount()
    {
        return $this->tryCount;
    }

    /**
     * @param int $tryCount
     */
    public function setTryCount($tryCount)
    {
        $this->tryCount = $tryCount;
    }


    /**
     * Set dateFailed
     *
     * @param \DateTime $dateFailed
     * @return OrderToRestaurant
     */
    public function setDateFailed($dateFailed)
    {
        $this->dateFailed = $dateFailed;

        return $this;
    }

    /**
     * Get dateSent
     *
     * @return \DateTime
     */
    public function getDateFailed()
    {
        return $this->dateFailed;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Set order
     *
     * @param \Food\OrderBundle\Entity\Order $order
     * @return OrderToRestaurant
     */
    public function setOrder(\Food\OrderBundle\Entity\Order $order = null)
    {
        $this->order = $order;
    
        return $this;
    }

    /**
     * Get order
     *
     * @return \Food\OrderBundle\Entity\Order 
     */
    public function getOrder()
    {
        return $this->order;
    }
}