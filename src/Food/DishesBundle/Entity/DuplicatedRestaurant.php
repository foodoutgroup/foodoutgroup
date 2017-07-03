<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DuplicatedRestaurant
 *
 * @ORM\Table(name="duplicated_restaurant")
 * @ORM\Entity(repositoryClass="Food\DishesBundle\Entity\DuplicatedRestaurantRepository")
 */
class DuplicatedRestaurant
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
     * @var integer
     *
     * @ORM\Column(name="place_id", type="integer")
     */
    private $placeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="new_place_id", type="integer")
     */
    private $newPlaceId;

    /**
     * @var \DateTime
     *
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
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     *
     * @var \Food\DishesBundle\Entity\Place
     */
    private $place;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place")
     * @ORM\JoinColumn(name="new_place_id", referencedColumnName="id")
     *
     * @var \Food\DishesBundle\Entity\Place
     */
    private $newPlace;

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
     * Set placeId
     *
     * @param integer $placeId
     * @return DuplicatedRestaurant
     */
    public function setPlaceId($placeId)
    {
        $this->placeId = $placeId;

        return $this;
    }

    /**
     * Get placeId
     *
     * @return integer 
     */
    public function getPlaceId()
    {
        return $this->placeId;
    }

    /**
     * Set newPlaceId
     *
     * @param integer $newPlaceId
     * @return DuplicatedRestaurant
     */
    public function setNewPlaceId($newPlaceId)
    {
        $this->newPlaceId = $newPlaceId;

        return $this;
    }

    /**
     * Get newPlaceId
     *
     * @return integer 
     */
    public function getNewPlaceId()
    {
        return $this->newPlaceId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return DuplicatedRestaurant
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
     * @param integer $createdBy
     * @return DuplicatedRestaurant
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return integer 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set place
     *
     * @param \Food\DishesBundle\Entity\Place $place
     * @return DuplicatedRestaurant
     */
    public function setPlace(\Food\DishesBundle\Entity\Place $place = null)
    {
        $this->place = $place;

        return $this;
    }

    /**
     * Get place
     *
     * @return \Food\DishesBundle\Entity\Place
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Set newPlace
     *
     * @param \Food\DishesBundle\Entity\Place $newPlace
     * @return DuplicatedRestaurant
     */
    public function setNewPlace(\Food\DishesBundle\Entity\Place $newPlace = null)
    {
        $this->newPlace = $newPlace;

        return $this;
    }

    /**
     * Get newPlace
     *
     * @return \Food\DishesBundle\Entity\Place 
     */
    public function getNewPlace()
    {
        return $this->newPlace;
    }

    /**
     * Set nplace
     *
     * @param \Food\DishesBundle\Entity\Place $nplace
     * @return DuplicatedRestaurant
     */
    public function setNplace(\Food\DishesBundle\Entity\Place $nplace = null)
    {
        $this->nplace = $nplace;

        return $this;
    }

    /**
     * Get nplace
     *
     * @return \Food\DishesBundle\Entity\Place 
     */
    public function getNplace()
    {
        return $this->nplace;
    }
}
