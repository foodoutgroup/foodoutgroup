<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Place
 *
 * @ORM\Table(name="place_reviews")
 * @ORM\Entity
 */
class PlaceReviews
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
     * @var \Food\DishesBundle\Entity\Place
     *
     * @ORM\ManyToOne(targetEntity="Place", inversedBy="place")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     */
    private $place;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User", inversedBy="user")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="review", type="text")
     */
    private $review;

    /**
     * @var string|null
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;


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
     * Set date
     *
     * @param \DateTime $date
     * @return PlaceReviews
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set review
     *
     * @param string $review
     * @return PlaceReviews
     */
    public function setReview($review)
    {
        $this->review = $review;
    
        return $this;
    }

    /**
     * Get review
     *
     * @return string 
     */
    public function getReview()
    {
        return $this->review;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     * @return PlaceReviews
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    
        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime 
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Set place
     *
     * @param \Food\DishesBundle\Entity\Place $place
     * @return PlaceReviews
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
     * Set user
     *
     * @param \Food\UserBundle\Entity\User $user
     * @return PlaceReviews
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
}