<?php

namespace Food\ReportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrdersByRestaurantFile
 *
 * @ORM\Table("orders_by_restaurant_file")
 * @ORM\Entity
 */
class OrdersByRestaurantFile
{

    const TYPE_FOR_RESTAURANT = 1;
    const TYPE_FOR_ADMINISTRATION = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var datetime
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     **/
    private $createdBy;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255)
     */
    private $filename;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;

    /**
     * @ORM\ManyToMany(targetEntity="Food\DishesBundle\Entity\Place")
     * @ORM\JoinColumn(onDelete="CASCADE")
     *
     * @var ArrayCollection
     */
    private $restaurants;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_from", type="date")
     */
    private $dateFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_to", type="date")
     */
    private $dateTo;


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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return OrdersByRestaurantFile
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdBy
     *
     * @param \Food\UserBundle\Entity\User $createdBy
     * @return OrdersByRestaurantFile
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    
        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Food\UserBundle\Entity\User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return OrdersByRestaurantFile
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    
        return $this;
    }

    /**
     * Get filename
     *
     * @return string 
     */
    public function getFilename()
    {
        return $this->filename;
    }


    /**
     * Set type
     *
     * @param string $type
     * @return OrdersByRestaurantFile
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Add categories
     *
     * @param \Food\DishesBundle\Entity\Place $restaurant
     *
     * @return Place
     */
    public function addRestaurant(\Food\DishesBundle\Entity\Place $restaurant)
    {
        $this->restaurants[] = $restaurant;

        return $this;
    }

    /**
     * Remove categories
     *
     * @param \Food\DishesBundle\Entity\Place $restaurant
     */
    public function removeRestaurant(\Food\DishesBundle\Entity\Place $restaurant)
    {
        $this->restaurants->removeElement($restaurant);
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRestaurants()
    {
        return $this->restaurants;
    }

    /**
     * Set dateFrom
     *
     * @param \DateTime $dateFrom
     * @return OrdersByRestaurantFile
     */
    public function setDateFrom($dateFrom)
    {
        $this->dateFrom = $dateFrom;
    
        return $this;
    }

    /**
     * Get dateFrom
     *
     * @return \DateTime 
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * Set dateTo
     *
     * @param \DateTime $dateTo
     * @return OrdersByRestaurantFile
     */
    public function setDateTo($dateTo)
    {
        $this->dateTo = $dateTo;
    
        return $this;
    }

    /**
     * Get dateTo
     *
     * @return \DateTime 
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    public static function getOrdersByRestaurantFileTypes() {
        return array(
            self::TYPE_FOR_RESTAURANT,
            self::TYPE_FOR_ADMINISTRATION
        );
    }
}
