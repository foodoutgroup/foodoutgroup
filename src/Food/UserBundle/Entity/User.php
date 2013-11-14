<?php

namespace Food\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Food\DishesBundle\Entity\Place", inversedBy="places")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     **/
    private $places = array();

    public function __construct()
    {
        parent::__construct();
        //$this->places =  new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set places
     *
     * @param \Food\DishesBundle\Entity\Place $places
     * @return User
     */
    public function setPlaces(\Food\DishesBundle\Entity\Place $places = null)
    {
        $this->places = $places;
    
        return $this;
    }

    /**
     * Get places
     *
     * @return \Food\DishesBundle\Entity\Place 
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * Add places
     *
     * @param \Food\DishesBundle\Entity\Place $places
     * @return User
     */
    public function addPlace(\Food\DishesBundle\Entity\Place $places)
    {
        $this->places[] = $places;
    
        return $this;
    }

    /**
     * Remove places
     *
     * @param \Food\DishesBundle\Entity\Place $places
     */
    public function removePlace(\Food\DishesBundle\Entity\Place $places)
    {
        $this->places->removeElement($places);
    }

    /**
     *  TODO laikinas crap debuginimui. Po to pasidarykime normalu castinima
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => $this->getId(),
            'groupNames' => $this->getGroupNames(),
            'roles' => $this->getRoles(),
        );
    }
}