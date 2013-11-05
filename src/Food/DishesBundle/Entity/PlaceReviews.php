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
     * @var object TODO - not really.. :)
     *
     * @ORM\ManyToOne(targetEntity="Place", inversedBy="place")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var object TODO - not really.. :)
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
     * @var integer
     *
     * @ORM\Column(name="deleted", type="integer")
     */
    private $deleted;


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
     * Set deleted
     *
     * @param integer $deleted
     * @return PlaceReviews
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    
        return $this;
    }

    /**
     * Get deleted
     *
     * @return integer 
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set client
     *
     * @param \Food\DishesBundle\Entity\Place $client
     * @return PlaceReviews
     */
    public function setClient(\Food\DishesBundle\Entity\Place $client = null)
    {
        $this->client = $client;
    
        return $this;
    }

    /**
     * Get client
     *
     * @return \Food\DishesBundle\Entity\Place 
     */
    public function getClient()
    {
        return $this->client;
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