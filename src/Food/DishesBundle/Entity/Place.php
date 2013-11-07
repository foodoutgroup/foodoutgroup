<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Client
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Place
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
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", length=255)
     */
    private $logo = "";

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

    /**
     * @ORM\ManyToMany(targetEntity="Kitchen", inversedBy="places")
     */
    private $kitchens;

    /**
     * @ORM\OneToMany(targetEntity="PlaceLocalized", mappedBy="id")
     **/
    private $localized;


    /**
     * @ORM\OneToMany(targetEntity="PlacePoint", mappedBy="place", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var ArrayCollection
     */
    private $points;


    /**
     * @ORM\OneToMany(targetEntity="\Food\UserBundle\Entity\User", mappedBy="id")
     **/
    private $users;

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
     * Set name
     *
     * @param string $name
     * @return Client
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set logo
     *
     * @param string $logo
     * @return Client
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    
        return $this;
    }

    /**
     * Get logo
     *
     * @return string 
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set active
     *
     * @param integer $active
     * @return Client
     */
    public function setActive($active)
    {
        $this->active = $active;
    
        return $this;
    }

    /**
     * Get active
     *
     * @return integer 
     */
    public function getActive()
    {
        return $this->active;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->kitchens = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add kitchens
     *
     * @param \Food\DishesBundle\Entity\Kitchen $kitchens
     * @return Client
     */
    public function addKitchen(\Food\DishesBundle\Entity\Kitchen $kitchens)
    {
        $this->kitchens[] = $kitchens;
    
        return $this;
    }

    /**
     * Remove kitchens
     *
     * @param \Food\DishesBundle\Entity\Kitchen $kitchens
     */
    public function removeKitchen(\Food\DishesBundle\Entity\Kitchen $kitchens)
    {
        $this->kitchens->removeElement($kitchens);
    }

    /**
     * Get kitchens
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getKitchens()
    {
        return $this->kitchens;
    }

    /**
     * Add localized
     *
     * @param \Food\DishesBundle\Entity\ClientLocalized $localized
     * @return Client
     */
    public function addLocalized(\Food\DishesBundle\Entity\PlaceLocalized $localized)
    {
        $this->localized[] = $localized;
    
        return $this;
    }

    /**
     * Remove localized
     *
     * @param \Food\DishesBundle\Entity\ClientLocalized $localized
     */
    public function removeLocalized(\Food\DishesBundle\Entity\PlaceLocalized $localized)
    {
        $this->localized->removeElement($localized);
    }

    /**
     * Get localized
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getLocalized()
    {
        return $this->localized;
    }

    /**
     * Add users
     *
     * @param \Food\UserBundle\Entity\User $users
     * @return Place
     */
    public function addUser(\Food\UserBundle\Entity\User $users)
    {
        $this->users[] = $users;
    
        return $this;
    }

    /**
     * Remove users
     *
     * @param \Food\UserBundle\Entity\User $users
     */
    public function removeUser(\Food\UserBundle\Entity\User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add points
     *
     * @param \Food\DishesBundle\Entity\PlacePoint $points
     * @return Place
     */
    public function addPoint(\Food\DishesBundle\Entity\PlacePoint $points)
    {
        $this->points[] = $points;
    
        return $this;
    }

    /**
     * Remove points
     *
     * @param \Food\DishesBundle\Entity\PlacePoint $points
     */
    public function removePoint(\Food\DishesBundle\Entity\PlacePoint $points)
    {
        $this->points->removeElement($points);
    }

    /**
     * Get points
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPoints()
    {
        return $this->points;
    }
}