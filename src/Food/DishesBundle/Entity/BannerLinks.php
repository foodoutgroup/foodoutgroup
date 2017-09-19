<?php

namespace Food\DishesBundle\Entity;

use Food\AppBundle\Entity\Uploadable;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Dish option
 *
 * @ORM\Table(name="banner_links", indexes={@ORM\Index(name="deleted_at_idx", columns={"deleted_at"})})
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Food\DishesBundle\Entity\BannerLinksRepository")
 * @Callback(methods={"isFileSizeValid"})
 * @Gedmo\TranslationEntity(class="Food\DishesBundle\Entity\BannerLinksLocalized")
 */
class BannerLinks extends Uploadable implements Translatable
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
     * @ORM\Column(name="text", type="text", length=255,nullable=true)
     */
    private $text;

    /**
     * @var string
     *
     * @ORM\Column(name="photo", type="string", length=255)
     */
    private $photo = "";

    /**
     * @var \Food\DishesBundle\Entity\BannerLinksLocalized
     *
     * @ORM\OneToMany(targetEntity="BannerLinksLocalized", mappedBy="object", cascade={"persist", "remove"})
     **/
    private $translations;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

    /**
     * @var \Food\DishesBundle\Entity\Place
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place")
     * @ORM\JoinColumn(name="place_from", referencedColumnName="id", nullable=true)
     */
    private $placeFrom;


    /**
     * @var \Food\DishesBundle\Entity\Place
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place")
     * @ORM\JoinColumn(name="place_to", referencedColumnName="id", nullable=true)
     */
    private $placeTo;

    /**
     * @var string
     *
     * @ORM\Column(name="color", type="text", length=255, nullable=true)
     */
    private $color;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;



    protected $resizeMode = null;
    protected $boxSize = null;
    // megabytes
    protected $maxFileSize = 1.9;

    protected $file;


    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }

        return $this->getId().'-'.$this->getPlaceFrom()->getName().'-'.$this->getPlaceTo()->getName().'-'.$this->getPhoto();
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
            $this->uploadDir = 'uploads/banners';
        }
        return $this->uploadDir;
    }

    /**
     * @param object $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }


    public function isFileSizeValid(ExecutionContextInterface $context)
    {
        if ($this->getFile() && $this->getFile()->getSize() > round($this->maxFileSize * 1024 * 1024)) {
            $context->addViolationAt('file', 'Paveiksliukas užima daugiau nei ' . $this->maxFileSize . ' MB vietos.');
        }
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
     * @return object
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param $locale
     */
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();

    }

    /**
     * Set id
     *
     * @param integer $id
     * @return BannerLinks
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Add translations
     *
     * @param \Food\DishesBundle\Entity\BannerLinksLocalized $translations
     * @return BannerLinks
     */
    public function addTranslation(\Food\DishesBundle\Entity\BannerLinksLocalized $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }
    /**
     * Remove translations
     *
     * @param \Food\DishesBundle\Entity\BannerLinksLocalized $translations
     */
    public function removeTranslation(\Food\DishesBundle\Entity\BannerLinksLocalized $translations)
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
     * Set description
     *
     * @param string $description
     * @return BannerLinks
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }



    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * @return Place
     */
    public function getPlaceFrom()
    {
        return $this->placeFrom;
    }

    /**
     * @param Place $placeFrom
     */
    public function setPlaceFrom($placeFrom)
    {
        $this->placeFrom = $placeFrom;
    }

    /**
     * @return Place
     */
    public function getPlaceTo()
    {
        return $this->placeTo;
    }

    /**
     * @param Place $placeTo
     */
    public function setPlaceTo($placeTo)
    {
        $this->placeTo = $placeTo;
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