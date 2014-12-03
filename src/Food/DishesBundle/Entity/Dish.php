<?php

namespace Food\DishesBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;
use Food\AppBundle\Entity\Uploadable;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Dish
 *
 * @ORM\Table(name="dish")
 * @ORM\Entity(repositoryClass="Food\DishesBundle\Entity\DishRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 * @Gedmo\TranslationEntity(class="Food\DishesBundle\Entity\DishLocalized")
 * @Callback(methods={"isFileSizeValid"})
 */
class Dish extends Uploadable implements Translatable
{
    // 1.9M or 1.9 megabyte
    protected $maxFileSize = 1992294;

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
     * @Gedmo\Translatable
     * @ORM\Column(name="name", type="string", length=80)
     */
    private $name;

    /**
     * @var string
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var \Food\DishesBundle\Entity\Place
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place", inversedBy="dishes")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     */
    private $place;

    /**
     * @var DishSize[]
     *
     * @ORM\OneToMany(targetEntity="DishSize", mappedBy="dish", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $sizes;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     **/
    private $createdBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="edited_at", type="datetime", nullable=true)
     */
    private $editedAt;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="edited_by", referencedColumnName="id")
     */
    private $editedBy;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="deleted_by", referencedColumnName="id")
     */
    private $deletedBy;

    /**
     * @var \Food\DishesBundle\Entity\DishLocalized
     *
     * @ORM\OneToMany(targetEntity="DishLocalized", mappedBy="object", cascade={"persist", "remove"})
     **/
    private $translations;

    /**
     * @ORM\ManyToMany(targetEntity="FoodCategory", inversedBy="dishes")
     * @ORM\JoinTable(name="food_category_dish_map")
     */
    private $categories;

    /**
     * @var string
     *
     * @ORM\Column(name="time_from", type="string", length=5, nullable=true)
     */
    private $timeFrom;

    /**
     * @var string
     *
     * @ORM\Column(name="time_to", type="string", length=5, nullable=true)
     */
    private $timeTo;

    /**
     * @ORM\ManyToMany(targetEntity="DishOption", inversedBy="dishes")
     * @ORM\JoinTable(name="dish_option_map")
     * @ORM\OrderBy({"groupName" = "DESC", "singleSelect" = "DESC"})
     */
    private $options;


    /**
     * @var bool
     *
     * @ORM\Column(name="discount_prices_enabled", type="boolean", nullable=true)
     */
    private $discountPricesEnabled = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="recomended", type="boolean")
     */
    private $recomended = false;

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
    private $active = true;


    /**
     * @var object
     */
    protected $file;

    protected $resizeMode = \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;
    protected $multipleThumbs = true;
    protected $boxSize = array(
        'type1' => array('w' => 260, 'h' => 179),
        'type2' => array('w' => 118, 'h' => 97),
        'type3' => array('w' => 550, 'h' => 400), // @todo - check ar sitie gerai ir atitinka realybe :)
        'type4' => array('w' => 1300, 'h' => 500), // @todo - check ar sitie gerai ir atitinka realybe :)
    );

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * Constructor
     */
    public function __construct()
    {
        // This is just the beginning
        $this->setCreatedAt(new \DateTime('now'));

        $this->categories = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Returns the name
     * @return string
     */
    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }
        return $this->getName();
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
     * Set name
     *
     * @param string $name
     * @return Dish
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Dish
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set editedAt
     *
     * @param \DateTime|null $editedAt
     * @return Dish
     */
    public function setEditedAt($editedAt)
    {
        $this->editedAt = $editedAt;
    
        return $this;
    }

    /**
     * Get editedAt
     *
     * @return \DateTime|null
     */
    public function getEditedAt()
    {
        return $this->editedAt;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime|null $deletedAt
     * @return Dish
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    
        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime|null
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Set place
     *
     * @param \Food\DishesBundle\Entity\Place $place
     * @return Dish
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
     * Add categories
     *
     * @param \Food\DishesBundle\Entity\FoodCategory $categories
     * @return Dish
     */
    public function addCategorie(\Food\DishesBundle\Entity\FoodCategory $categories)
    {
        $this->categories[] = $categories;
    
        return $this;
    }

    /**
     * Remove categories
     *
     * @param \Food\DishesBundle\Entity\FoodCategory $categories
     */
    public function removeCategorie(\Food\DishesBundle\Entity\FoodCategory $categories)
    {
        $this->categories->removeElement($categories);
    }

    /**
     * Get categories
     *
     * @return \Food\DishesBundle\Entity\FoodCategory[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Add options
     *
     * @param \Food\DishesBundle\Entity\DishOption $options
     * @return Dish
     */
    public function addOption(\Food\DishesBundle\Entity\DishOption $options)
    {
        $this->options[] = $options;
    
        return $this;
    }

    /**
     * Remove options
     *
     * @param \Food\DishesBundle\Entity\DishOption $options
     */
    public function removeOption(\Food\DishesBundle\Entity\DishOption $options)
    {
        $this->options->removeElement($options);
    }

    /**
     * Get options
     *
     * @return \Food\DishesBundle\Entity\DishOption[]
     */
    public function getOptions()
    {
        return $this->options;
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
     * @param \Food\DishesBundle\Entity\DishLocalized $t
     * @return Dish
     */
    public function addTranslation(\Food\DishesBundle\Entity\DishLocalized $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    /**
     * Remove translations
     *
     * @param \Food\DishesBundle\Entity\DishLocalized $translations
     */
    public function removeTranslation(\Food\DishesBundle\Entity\DishLocalized $translations)
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
     * Set unit
     *
     * @param \Food\DishesBundle\Entity\DishUnit $unit
     * @return Dish
     */
    public function setUnit(\Food\DishesBundle\Entity\DishUnit $unit = null)
    {
        $this->unit = $unit;
    
        return $this;
    }

    /**
     * Get unit
     *
     * @return \Food\DishesBundle\Entity\DishUnit 
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set createdBy
     *
     * @param \Food\UserBundle\Entity\User $createdBy
     * @return Dish
     */
    public function setCreatedBy(\Food\UserBundle\Entity\User $createdBy = null)
    {
        $this->createdBy = $createdBy;
    
        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Food\UserBundle\Entity\User 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set editedBy
     *
     * @param \Food\UserBundle\Entity\User $editedBy
     * @return Dish
     */
    public function setEditedBy(\Food\UserBundle\Entity\User $editedBy = null)
    {
        $this->editedBy = $editedBy;
    
        return $this;
    }

    /**
     * Get editedBy
     *
     * @return \Food\UserBundle\Entity\User 
     */
    public function getEditedBy()
    {
        return $this->editedBy;
    }

    /**
     * Set deletedBy
     *
     * @param \Food\UserBundle\Entity\User $deletedBy
     * @return Dish
     */
    public function setDeletedBy(\Food\UserBundle\Entity\User $deletedBy = null)
    {
        $this->deletedBy = $deletedBy;
    
        return $this;
    }

    /**
     * Get deletedBy
     *
     * @return \Food\UserBundle\Entity\User 
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * Set recomended
     *
     * @param boolean $recomended
     * @return Dish
     */
    public function setRecomended($recomended)
    {
        $this->recomended = $recomended;
    
        return $this;
    }

    /**
     * Get recomended
     *
     * @return boolean 
     */
    public function getRecomended()
    {
        return $this->recomended;
    }

    /**
     * Add sizes
     *
     * @param \Food\DishesBundle\Entity\DishSize $sizes
     * @return Dish
     */
    public function addSize(\Food\DishesBundle\Entity\DishSize $sizes)
    {
        $this->sizes[] = $sizes;
    
        return $this;
    }

    /**
     * Remove sizes
     *
     * @param \Food\DishesBundle\Entity\DishSize $sizes
     */
    public function removeSize(\Food\DishesBundle\Entity\DishSize $sizes)
    {
        $this->sizes->removeElement($sizes);
    }

    /**
     * Get sizes
     *
     * @return DishSize[]
     */
    public function getSizes()
    {
        return $this->sizes;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Dish
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
     * Add categories
     *
     * @param \Food\DishesBundle\Entity\FoodCategory $categories
     * @return Dish
     */
    public function addCategory(\Food\DishesBundle\Entity\FoodCategory $categories)
    {
        $this->categories[] = $categories;
    
        return $this;
    }

    /**
     * Remove categories
     *
     * @param \Food\DishesBundle\Entity\FoodCategory $categories
     */
    public function removeCategory(\Food\DishesBundle\Entity\FoodCategory $categories)
    {
        $this->categories->removeElement($categories);
    }

    /**
     * Set photo
     *
     * @param string $photo
     * @return Dish
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
            $this->uploadDir = 'uploads/dishes';
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
     * @return Dish
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
     * @return bool
     */
    public function isAlcohol()
    {
        $categories = $this->getCategories();

        foreach ($categories as $category) {
            if ($category->getAlcohol()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set timeFrom
     *
     * @param string $timeFrom
     * @return Dish
     */
    public function setTimeFrom($timeFrom)
    {
        $this->timeFrom = $timeFrom;
    
        return $this;
    }

    /**
     * Get timeFrom
     *
     * @return string 
     */
    public function getTimeFrom()
    {
        return $this->timeFrom;
    }

    /**
     * Set timeTo
     *
     * @param string $timeTo
     * @return Dish
     */
    public function setTimeTo($timeTo)
    {
        $this->timeTo = $timeTo;
    
        return $this;
    }

    /**
     * Get timeTo
     *
     * @return string 
     */
    public function getTimeTo()
    {
        return $this->timeTo;
    }

    /**
     * Set discountPricesEnabled
     *
     * @param boolean $discountPricesEnabled
     * @return Dish
     */
    public function setDiscountPricesEnabled($discountPricesEnabled)
    {
        $this->discountPricesEnabled = $discountPricesEnabled;
    
        return $this;
    }

    /**
     * Get discountPricesEnabled
     *
     * @return boolean 
     */
    public function getDiscountPricesEnabled()
    {
        return $this->discountPricesEnabled;
    }


    public function getShowDiscount()
    {
        if ($this->getDiscountPricesEnabled() && $this->getPlace()->getDiscountPricesEnabled()) {
            return true;
        }
        return false;
    }

    public function isFileSizeValid(ExecutionContextInterface $context)
    {
        if ($this->getFile() && $this->getFile()->getSize() > $this->maxFileSize) {
            $context->addViolationAt('file', 'Paveiksliukas užima per daug disko vietos, pabandyk įkelti mažesnį paveiksliuką.');
        }
    }
}
