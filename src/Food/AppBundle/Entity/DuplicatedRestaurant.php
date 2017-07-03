<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DuplicatedRestaurant
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Food\AppBundle\Entity\DuplicatedRestaurantRepository")
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
     * @var integer
     *
     * @ORM\Column(name="created_by", type="integer")
     */
    private $createdBy;


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
}
