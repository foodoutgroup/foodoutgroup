<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Food\AppBundle\Entity\Uploadable;

/**
 * BannerLinks
 *
 * @ORM\Table(name="banner_links")
 * @ORM\Entity(repositoryClass="Food\DishesBundle\Entity\BannerLinksRepository")
 */
class BannerLinks extends Uploadable
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
     * @ORM\ManyToOne(targetEntity="Place")
     * @ORM\JoinColumn(name="place_from", referencedColumnName="id")
     *
     * @var Place
     */
    private $placeFrom;

    /**
     * @ORM\ManyToOne(targetEntity="Place")
     * @ORM\JoinColumn(name="place_to", referencedColumnName="id")
     *
     * @var Place
     */
    private $placeTo;

    /**
     * @var string
     *
     * @ORM\Column(name="element", type="string", length=255)
     */
    private $element;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="string", length=255)
     */
    private $text;

    /**
     * @var string
     *
     * @ORM\Column(name="photo", type="string", length=255)
     */
    private $photo = "";

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

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
     * Set urlFrom
     *
     * @param string $urlFrom
     * @return BannerLinks
     */
    public function setUrlFrom($urlFrom)
    {
        $this->urlFrom = $urlFrom;

        return $this;
    }

    /**
     * Get urlFrom
     *
     * @return string 
     */
    public function getUrlFrom()
    {
        return $this->urlFrom;
    }

    /**
     * Set urlTo
     *
     * @param string $urlTo
     * @return BannerLinks
     */
    public function setUrlTo($urlTo)
    {
        $this->urlTo = $urlTo;

        return $this;
    }

    /**
     * Get urlTo
     *
     * @return string 
     */
    public function getUrlTo()
    {
        return $this->urlTo;
    }

    /**
     * Set element
     *
     * @param string $element
     * @return BannerLinks
     */
    public function setElement($element)
    {
        $this->element = $element;

        return $this;
    }

    /**
     * Get element
     *
     * @return string 
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * Set text
     *
     * @param string $text
     * @return BannerLinks
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string 
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set photo
     *
     * @param string $photo
     * @return BannerLinks
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo
     *
     * @return string 
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Set placeFrom
     *
     * @param \Food\DishesBundle\Entity\Place $placeFrom
     * @return BannerLinks
     */
    public function setPlaceFrom(\Food\DishesBundle\Entity\Place $placeFrom = null)
    {
        $this->placeFrom = $placeFrom;

        return $this;
    }

    /**
     * Get placeFrom
     *
     * @return \Food\DishesBundle\Entity\Place 
     */
    public function getPlaceFrom()
    {
        return $this->placeFrom;
    }

    /**
     * Set placeTo
     *
     * @param \Food\DishesBundle\Entity\Place $placeTo
     * @return BannerLinks
     */
    public function setPlaceTo(\Food\DishesBundle\Entity\Place $placeTo = null)
    {
        $this->placeTo = $placeTo;

        return $this;
    }

    /**
     * Get placeTo
     *
     * @return \Food\DishesBundle\Entity\Place 
     */
    public function getPlaceTo()
    {
        return $this->placeTo;
    }

    /**
     * @return string
     */
    public function getUploadableField()
    {
        return 'photo';
    }

    /**
     * @return string
     */
    public function getUploadDir()
    {
        if (empty($this->uploadDir)) {
            $this->uploadDir = 'uploads/covers';
        }
        return $this->uploadDir;
    }


    /**
     * Set active
     *
     * @param boolean $active
     * @return BannerLinks
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
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
}
