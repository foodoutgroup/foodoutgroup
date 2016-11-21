<?php

namespace Food\AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * City
 *
 * @ORM\Table(name="cities")
 * @ORM\Entity(repositoryClass="Food\AppBundle\Entity\CityRepository")
 * @Gedmo\TranslationEntity(class="Food\AppBundle\Entity\CityLocalized")

 */
class City implements \JsonSerializable
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
     * @ORM\Column(name="zavalas_time", type="string", length=50, nullable=true)
     */
    private $zavalasTime;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
    private $active;

    /**
     * @ORM\OneToMany(targetEntity="CityLocalized", mappedBy="object", cascade={"persist", "remove"})
     **/
    private $translations;

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
            'zavalasOn'=> $this->isZavalasOn(),
            'zavalasTime'=> $this->getZavalasTime(),
        ));
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return (boolean) $this->active;
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



}
