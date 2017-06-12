<?php

namespace Food\PlacesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Food\AppBundle\Entity\Uploadable;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\ExecutionContextInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * PlaceNotification
 *
 * @ORM\Table(name="place_notification")
 * @ORM\Entity(repositoryClass="\Food\PlacesBundle\Entity\PlaceNotificationRepository")
 * @Callback(methods={"isFileSizeValid"})
 * @Gedmo\TranslationEntity(class="Food\PlacesBundle\Entity\PlaceNotificationLocalized")

 */
class PlaceNotification extends Uploadable
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
     * @ORM\OneToMany(targetEntity="PlaceNotificationLocalized", mappedBy="object", cascade={"persist", "remove"})
     **/
    private $translations;


    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;


    /**
     * @var string
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;


    /**
     * @var \Food\DishesBundle\Entity\Place
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place", inversedBy="placeNotification")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     */
    private $placeCollection;

    /**
     * @ORM\ManyToMany(targetEntity="Food\AppBundle\Entity\City", inversedBy="placeNotificationCollection")
     * @ORM\JoinTable(name="place_notification_city")
     */
    private $cityCollection;

    /**
     * @var integer
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;


    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set title
     *
     * @param string $title
     * @return PlaceNotification
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return PlaceNotification
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
     * Set active
     *
     * @param integer $active
     * @return PlaceNotification
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

    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }
        return $this->getTitle();
    }

    /**
     * Set place
     *
     * @param \Food\DishesBundle\Entity\Place $place
     * @return PlaceNotification
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
     * @param $locale
     */
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Add translations
     *
     * @param \Food\PlacesBundle\Entity\PlaceNotificationLocalized $t
     */
    public function addTranslation(\Food\PlacesBundle\Entity\PlaceNotificationLocalized $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    /**
     * Remove translations
     *
     * @param \Food\PlacesBundle\Entity\PlaceNotificationLocalized $translations
     */
    public function removeTranslation(\Food\PlacesBundle\Entity\PlaceNotificationLocalized $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }


    /**
     * Set placeCollection
     *
     * @param \Food\DishesBundle\Entity\Place $placeCollection
     * @return PlaceNotification
     */
    public function setPlaceCollection(\Food\DishesBundle\Entity\Place $placeCollection = null)
    {
        $this->placeCollection = $placeCollection;

        return $this;
    }

    /**
     * Get placeCollection
     *
     * @return \Food\DishesBundle\Entity\Place 
     */
    public function getPlaceCollection()
    {
        return $this->placeCollection;
    }

    /**
     * Add cityCollection
     *
     * @param \Food\AppBundle\Entity\City $cityCollection
     * @return PlaceNotification
     */
    public function addCityCollection(\Food\AppBundle\Entity\City $cityCollection)
    {
        $this->cityCollection[] = $cityCollection;

        return $this;
    }

    /**
     * Remove cityCollection
     *
     * @param \Food\AppBundle\Entity\City $cityCollection
     */
    public function removeCityCollection(\Food\AppBundle\Entity\City $cityCollection)
    {
        $this->cityCollection->removeElement($cityCollection);
    }

    /**
     * Get cityCollection
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCityCollection()
    {
        return $this->cityCollection;
    }
}
