<?php

namespace Food\AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Food\AppBundle\Validator\Constraints as AppAssert;


/**
 * City
 *
 * @ORM\Table(name="city")
 * @ORM\Entity(repositoryClass="Food\AppBundle\Entity\CityRepository")
 * @Gedmo\TranslationEntity(class="Food\AppBundle\Entity\CityLocalized")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class City implements \JsonSerializable
{


    const SLUG_TYPE = 'city';

    /**
     * @ORM\ManyToMany(targetEntity="Food\PlacesBundle\Entity\BestOffer", mappedBy="offerCity")
     */

    private $bestOffers;

    /**
     * @ORM\ManyToMany(targetEntity="Food\PlacesBundle\Entity\PlaceNotification", mappedBy="cityCollection")
     */

    private $placeNotificationCollection;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="Food\AppBundle\Entity\CityLocalized", mappedBy="object", cascade={"persist", "remove"})
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
     * @Gedmo\Translatable
     * @ORM\Column(name="meta_title", type="string", length=255, nullable=true)
     */
    private $meta_title;

    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(name="meta_description", type="string", length=255, nullable=true)
     */
    private $meta_description;

    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(name="slug", type="string", length=255, nullable=true, unique=true)
     */
    private $slug;

    /**
     * @var boolean
     *
     * @ORM\Column(name="zavalas_on", type="boolean", nullable=true)
     */
    private $zavalasOn;


    /**
     * @var string
     *
     * @ORM\Column(name="zavalas_time", type="string", nullable=true)
     */
    private $zavalasTime;

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="integer", nullable=true)
     */
    private $position;

    /**
     * @var String
     *
     * @ORM\Column(name="code", type="string", nullable=true)
     */
    private $code;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
    private $active;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * @var string|null
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="edited_by", referencedColumnName="id")
     */
    private $editedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="edited_at", type="datetime", nullable=true)
     */
    private $editedAt;

    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }
    /**
     * @var boolean
     *
     * @ORM\Column(name="pedestrian", type="boolean", nullable=true)
     */
    private $pedestrian;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pop_up", type="boolean", nullable=true)
     */
    private $popUp;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pop_up_time_from", type="time", nullable=true)
     */
    private $popUpTimeFrom;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pop_up_time_to", type="time", nullable=true)
     */
    private $popUpTimeTo;

    /**
     * @var boolean
     *
     * @ORM\Column(name="badge", type="boolean", nullable=true)
     */
    private $badge;

    /**
     * @var boolean
     *
     * @ORM\Column(name="show_in_dispatcher", type="boolean", nullable=true ,options={"default": false})
     */
    private $showInDispatcher;

    /**
     * @var string
     *
     * @ORM\Column(name="dispatcher_lat", type="string", nullable=true)
     */
    private $dispatcherLat;

    /**
     * @var string
     *
     * @ORM\Column(name="dispatcher_lng", type="string", nullable=true)
     */
    private $dispatcherLng;

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
     * @return City
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
     * Set zavalasOn
     *
     * @param boolean $zavalasOn
     * @return City
     */
    public function setZavalasOn($zavalasOn)
    {
        $this->zavalasOn = $zavalasOn;

        return $this;
    }

    /**
     * Get zavalasOn
     *
     * @return boolean
     */
    public function isZavalasOn()
    {
        return $this->zavalasOn;
    }

    /**
     * Set zavalasTime
     *
     * @param string $zavalasTime
     * @return City
     */
    public function setZavalasTime($zavalasTime)
    {
        $this->zavalasTime = $zavalasTime;

        return $this;
    }

    /**
     * Get zavalasTime
     *
     * @return string
     */
    public function getZavalasTime()
    {
        return $this->zavalasTime;
    }

    /**
     * Return city title
     *
     * @return string
     */
    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }

        return $this->getTitle();
    }

    public function jsonSerialize()
    {
        return json_encode(array(
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'zavalasOn' => $this->isZavalasOn(),
            'zavalasTime' => $this->getZavalasTime(),
            'pedestrian' => $this->getPedestrian()
        ));
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }


    /**
     * @return boolean
     */
    public function isActive()
    {
        return (boolean)$this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
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
     * @param CityLocalized $t
     */
    public function addTranslation(CityLocalized $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    /**
     * Remove translations
     *
     * @param CityLocalized $translations
     */
    public function removeTranslation(CityLocalized $translations)
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
     * @return string
     */
    public function getMetaTitle()
    {
        return $this->meta_title;
    }

    /**
     * @param string $meta_title
     */
    public function setMetaTitle($meta_title)
    {
        $this->meta_title = $meta_title;
    }

    /**
     * @return string
     */
    public function getMetaDescription()
    {
        return $this->meta_description;
    }

    /**
     * @param string $meta_description
     */
    public function setMetaDescription($meta_description)
    {
        $this->meta_description = $meta_description;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param integer $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;

    }


    /**
     * Get zavalasOn
     *
     * @return boolean
     */
    public function getZavalasOn()
    {
        return $this->zavalasOn;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Add bestOffers
     *
     * @param \Food\PlacesBundle\Entity\BestOffer $bestOffers
     * @return City
     */
    public function addBestOffer(\Food\PlacesBundle\Entity\BestOffer $bestOffers)
    {
        $this->bestOffers[] = $bestOffers;

        return $this;
    }

    /**
     * Remove bestOffers
     *
     * @param \Food\PlacesBundle\Entity\BestOffer $bestOffers
     */
    public function removeBestOffer(\Food\PlacesBundle\Entity\BestOffer $bestOffers)
    {
        $this->bestOffers->removeElement($bestOffers);
    }

    /**
     * Get bestOffers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBestOffers()
    {
        return $this->bestOffers;
    }

    /**
     * Add bestOffers
     *
     * @param \Food\PlacesBundle\Entity\PlaceNotification $placeNotificationCollection
     * @return City
     */
    public function addPlaceNotificationCollection(\Food\PlacesBundle\Entity\PlaceNotification $placeNotificationCollection)
    {
        $this->placeNotificationCollection[] = $placeNotificationCollection;

        return $this;
    }

    /**
     * Remove bestOffers
     *
     * @param \Food\PlacesBundle\Entity\PlaceNotification $placeNotificationCollection
     */
    public function removePlaceNotificationCollection(\Food\PlacesBundle\Entity\PlaceNotification $placeNotificationCollection)
    {
        $this->placeNotificationCollection->removeElement($placeNotificationCollection);
    }

    /**
     * Get placeController
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPlaceNotificationCollection()
    {
        return $this->placeNotificationCollection;
    }

    /**
     * @return null|string
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param null|string $deletedAt
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getEditedAt()
    {
        return $this->editedAt;
    }

    /**
     * @param \DateTime|null $editedAt
     */
    public function setEditedAt($editedAt)
    {
        $this->editedAt = $editedAt;
    }

    /**
     * @return \Food\UserBundle\Entity\User
     */
    public function getEditedBy()
    {
        return $this->editedBy;
    }

    /**
     * @param \Food\UserBundle\Entity\User $editedBy
     */
    public function setEditedBy($editedBy)
    {
        $this->editedBy = $editedBy;
    }

    /**
     * Set pedestrian
     *
     * @param boolean $pedestrian
     * @return City
     */
    public function setPedestrian($pedestrian)
    {
        $this->pedestrian = $pedestrian;

        return $this;
    }

    /**
     * Get pedestrian
     *
     * @return boolean
     */
    public function getPedestrian()
    {
        return $this->pedestrian;
    }

    /**
     * Set popUp
     *
     * @param boolean $popUp
     * @return City
     */
    public function setPopUp($popUp)
    {
        $this->popUp = $popUp;

        return $this;
    }

    /**
     * Get popUp
     *
     * @return boolean
     */
    public function getPopUp()
    {
        return $this->popUp;
    }

    /**
     * Set popUpTimeFrom
     *
     * @param \DateTime $popUpTimeFrom
     * @return City
     */
    public function setPopUpTimeFrom($popUpTimeFrom)
    {
        $this->popUpTimeFrom = $popUpTimeFrom;

        return $this;
    }

    /**
     * Get popUpTimeFrom
     *
     * @return \DateTime
     */
    public function getPopUpTimeFrom()
    {
        return $this->popUpTimeFrom;
    }

    /**
     * Set popUpTimeTo
     *
     * @param \DateTime $popUpTimeTo
     * @return City
     */
    public function setPopUpTimeTo($popUpTimeTo)
    {
        $this->popUpTimeTo = $popUpTimeTo;

        return $this;
    }

    /**
     * Get popUpTimeTo
     *
     * @return \DateTime
     */
    public function getPopUpTimeTo()
    {
        return $this->popUpTimeTo;
    }

    /**
     * Set badge
     *
     * @param boolean $badge
     * @return City
     */
    public function setBadge($badge)
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * Get badge
     *
     * @return boolean
     */
    public function getBadge()
    {
        return $this->badge;
    }

    /**
     * Set showInDispatcher
     *
     * @param boolean $showInDispatcher
     * @return City
     */
    public function setShowInDispatcher($showInDispatcher)
    {
        $this->showInDispatcher = $showInDispatcher;

        return $this;
    }

    /**
     * Get showInDispatcher
     *
     * @return boolean 
     */
    public function getShowInDispatcher()
    {
        return $this->showInDispatcher;
    }

    /**
     * Set dispatcherLat
     *
     * @param string $dispatcherLat
     * @return City
     */
    public function setDispatcherLat($dispatcherLat)
    {
        $this->dispatcherLat = $dispatcherLat;

        return $this;
    }

    /**
     * Get dispatcherLat
     *
     * @return string 
     */
    public function getDispatcherLat()
    {
        return $this->dispatcherLat;
    }

    /**
     * Set dispatcherLng
     *
     * @param string $dispatcherLng
     * @return City
     */
    public function setDispatcherLng($dispatcherLng)
    {
        $this->dispatcherLng = $dispatcherLng;

        return $this;
    }

    /**
     * Get dispatcherLng
     *
     * @return string 
     */
    public function getDispatcherLng()
    {
        return $this->dispatcherLng;
    }
}
