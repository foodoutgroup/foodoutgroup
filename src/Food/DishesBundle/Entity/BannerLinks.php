<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Food\AppBundle\Entity\Uploadable;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * BannerLinks
 *
 * @ORM\Table(name="banner_links")
 * @ORM\Entity(repositoryClass="Food\DishesBundle\Entity\BannerLinksRepository")
 * @Callback(methods={"isFileSizeValid"})
 * @Gedmo\TranslationEntity(class="Food\DishesBundle\Entity\BannerLinksLocalized")
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
     * @var text
     * @Gedmo\Translatable
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
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    protected $resizeMode = null;
    protected $boxSize = null;
    // megabytes
    protected $maxFileSize = 1.9;

    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }

        return $this->getId().'-'.$this->getPlaceFrom()->getName().'-'.$this->getPlaceTo()->getName().'-'.$this->getPhoto();
    }

    /**
     * @var object
     */
    protected $file;

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

    /**
     * @return object
     */
    public function getFile()
    {
        return $this->file;
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
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add translations
     *
     * @param \Food\DishesBundle\Entity\BannerLinksLocalized $translations
     * @return BannerLinks
     */
    public function addTranslation(\Food\DishesBundle\Entity\BannerLinksLocalized $translations)
    {
        $this->translations[] = $translations;

        return $this;
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

    public function isFileSizeValid(ExecutionContextInterface $context)
    {
        if ($this->getFile() && $this->getFile()->getSize() > round($this->maxFileSize * 1024 * 1024)) {
            $context->addViolationAt('file', 'Paveiksliukas užima daugiau nei ' . $this->maxFileSize . ' MB vietos.');
        }
    }
}
