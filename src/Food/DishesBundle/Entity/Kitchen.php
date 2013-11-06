<?php

namespace Food\DishesBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\ORM\EntityManager;

/**
 * Kitchen
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Kitchen
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
     * @ORM\Column(name="logo", type="string", length=100)
     */
    private $logo;

    /**
     * @var int
     *
     * @ORM\Column(name="visible", type="integer", length=1)
     */
    private $visible;

    /**
     * @ORM\OneToMany(targetEntity="KitchenLocalized", mappedBy="id")
     **/
    private $localized;

    /**
     * @ORM\ManyToMany(targetEntity="Place", mappedBy="kitchens")
     */
    private $places = array();

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
     * @return Kitchen
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
     * Constructor
     */
    public function __construct()
    {
        $this->localized = new \Doctrine\Common\Collections\ArrayCollection();
        $this->places = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add localized
     *
     * @param \Food\DishesBundle\Entity\KitchenLocalized $localized
     * @return Kitchen
     */
    public function addLocalized(\Food\DishesBundle\Entity\KitchenLocalized $localized)
    {
        $this->localized[] = $localized;
    
        return $this;
    }

    /**
     * Remove localized
     *
     * @param \Food\DishesBundle\Entity\KitchenLocalized $localized
     */
    public function removeLocalized(\Food\DishesBundle\Entity\KitchenLocalized $localized)
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
     * @param string $logo
     * @return Kitchen
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }


    /**
     * @param int $visible
     * @return Kitchen
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * @return int
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     *
     */
    public function getCount()
    {

        //return 123;
        //var_dump(empty($this->places) ? 'emp': );
        return sizeof($this->places);
        //return sizeof($this->getPlaces());
    }


    /**
     * Add places
     *
     * @param \Food\DishesBundle\Entity\Place $places
     * @return Kitchen
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
     * Get places
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPlaces()
    {
        return $this->places;
    }

    public function __toString()
    {
        return $this->getName();
    }
}