<?php

namespace Food\PlacesBundle\Entity;

use DateTime;
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
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;

    /**
     * @var \Food\DishesBundle\Entity\Place
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place", inversedBy="placeNotification")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id", nullable=true)
     */
    private $placeCollection;

    /**
     * @ORM\ManyToMany(targetEntity="Food\AppBundle\Entity\City", inversedBy="placeNotificationCollection")
     * @ORM\JoinTable(name="place_notification_city")
     */
    private $cityCollection;

    /**
     * @var datetime
     * @ORM\Column(name="show_till", type="datetime", nullable=true)
     */
    private $showTill;

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
     * Set description
     *
     * @param string $description
     * @return PlaceNotification
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
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

    /**
     * Set showTill
     *
     * @param \DateTime $showTill
     * @return PlaceNotification
     */
    public function setShowTill($showTill)
    {
        $this->showTill = $showTill;

        return $this;
    }

    /**
     * Get showTill
     *
     * @return \DateTime
     */
    public function getShowTill()
    {
        return $this->showTill;
    }
}
