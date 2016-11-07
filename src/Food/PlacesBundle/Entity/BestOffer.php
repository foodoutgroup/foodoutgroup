<?php

namespace Food\PlacesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Food\AppBundle\Entity\Uploadable;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\ExecutionContextInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * BestOffer
 *
 * @ORM\Table(name="best_offer")
 * @ORM\Entity(repositoryClass="\Food\PlacesBundle\Entity\BestOfferRepository")
 * @Callback(methods={"isFileSizeValid"})
 * @Gedmo\TranslationEntity(class="Food\PlacesBundle\Entity\BestOfferLocalized")

 */
class BestOffer extends Uploadable
{
    // megabytes
    protected $maxFileSize = 1.9;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="BestOfferLocalized", mappedBy="object", cascade={"persist", "remove"})
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
     * @ORM\Column(name="city", type="string", length=128, nullable=true)
     */
    private $city;

    /**
     * @var \Food\DishesBundle\Entity\Place
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place", inversedBy="bestOffers")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     */
    private $place;

    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(name="link", type="text")
     */
    private $link;

    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(name="text", type="text")
     */
    private $text;

    /**
     * @var integer
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

    /**
     * @var integer
     *
     * @ORM\Column(name="use_url", type="boolean", nullable=true)
     */
    private $useUrl = false;

    /**
     * @var string
     * @ORM\Column(name="image", type="string", length=255)
     */
    private $image = '';

    protected $uploadDir;
    private $file;

    protected $resizeMode = \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;
    protected $multipleThumbs = true;
    protected $boxSize = array(
        'type1' => array('w' => 180, 'h' => 177),
        'type2' => array('w' => 640, 'h' => 480),
    );

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
     * @return BestOffer
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
     * Set link
     *
     * @param string $link
     * @return BestOffer
     */
    public function setLink($link)
    {
        $this->link = $link;
    
        return $this;
    }

    /**
     * Get link
     *
     * @return string 
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set text
     *
     * @param string $text
     * @return BestOffer
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
     * Set active
     *
     * @param integer $active
     * @return BestOffer
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
     * Set image
     *
     * @param string $image
     * @return BestOffer
     */
    public function setImage($image)
    {
        $this->image = $image;
    
        return $this;
    }

    /**
     * Get image
     *
     * @return string 
     */
    public function getImage()
    {
        return $this->image;
    }

    public function getUploadableField()
    {
        return 'image';
    }

    /**
     * @return string
     */
    public function getUploadDir()
    {
        if (empty($this->uploadDir)) {
            $this->uploadDir = 'uploads/best_offers';
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

    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }
        return $this->getTitle();
    }

    /**
     * Set city
     *
     * @param string $city
     * @return BestOffer
     */
    public function setCity($city)
    {
        $this->city = $city;
    
        return $this;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }

    public function isFileSizeValid(ExecutionContextInterface $context)
    {
        if ($this->getFile() && $this->getFile()->getSize() > round($this->maxFileSize * 1024 * 1024)) {
            $context->addViolationAt('file', 'Paveiksliukas užima daugiau nei ' . $this->maxFileSize . ' MB vietos.');
        }
    }

    /**
     * Set place
     *
     * @param \Food\DishesBundle\Entity\Place $place
     * @return BestOffer
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
     * Set useUrl
     *
     * @param boolean $useUrl
     * @return BestOffer
     */
    public function setUseUrl($useUrl)
    {
        $this->useUrl = $useUrl;
    
        return $this;
    }

    /**
     * Get useUrl
     *
     * @return boolean 
     */
    public function getUseUrl()
    {
        return $this->useUrl;
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
     * @param \Food\PlacesBundle\Entity\BestOfferLocalized $t
     */
    public function addTranslation(\Food\PlacesBundle\Entity\BestOfferLocalized $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    /**
     * Remove translations
     *
     * @param \Food\PlacesBundle\Entity\BestOfferLocalized $translations
     */
    public function removeTranslation(\Food\PlacesBundle\Entity\BestOfferLocalized $translations)
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

}