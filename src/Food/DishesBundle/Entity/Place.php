<?php

namespace Food\DishesBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Food\AppBundle\Entity\Uploadable;
use Gedmo\Translatable\Translatable;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Client
 *
 * @ORM\Table(name="place", indexes={@ORM\Index(name="active_idx", columns={"active"}),@ORM\Index(name="recommended_idx", columns={"recommended"}),@ORM\Index(name="new_idx", columns={"new"})})
 * @ORM\Entity(repositoryClass="Food\DishesBundle\Entity\PlaceRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 * @Gedmo\TranslationEntity(class="Food\DishesBundle\Entity\PlaceLocalized")
 * @Callback(methods={"isFileSizeValid"})
 */
class Place extends Uploadable implements Translatable
{
    const OPT_DELIVERY_AND_PICKUP = 'delivery_and_pickup';
    const OPT_ONLY_DELIVERY = 'delivery';
    const OPT_ONLY_PICKUP = 'pickup';

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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(name="slogan", type="string", length=255, nullable=true)
     */
    private $slogan;

    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description = null;

    /**
     * @var string
     *
     * @ORM\Column(name="chain", type="string", length=10, nullable=true)
     */
    private $chain;

    /**
     * @var bool
     *
     * @ORM\Column(name="navision", type="boolean")
     */
    private $navision = false;

    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(name="alcohol_rules", type="text", nullable=true)
     */
    private $alcoholRules = null;

    /**
     * @var bool
     *
     * @ORM\Column(name="only_alcohol", type="boolean")
     */
    private $onlyAlcohol = false;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", length=255)
     */
    private $logo = "";

    /**
     * @var object
     */
    protected $file;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

    /**
     * @var bool
     *
     * @ORM\Column(name="show_notification", type="boolean", nullable=true)
     */
    private $showNotification = false;

    /**
     * @var string
     * @ORM\Column(name="notification_content", type="text", nullable=true)
     */
    private $notificationContent;

    /**
     * @var bool
     *
     * @ORM\Column(name="discount_prices_enabled", type="boolean", nullable=true)
     */
    private $discountPricesEnabled;

    /**
     * @var bool
     *
     * @ORM\Column(name="recommended", type="boolean")
     */
    private $recommended;

    /**
     * @var bool
     *
     * @ORM\Column(name="top", type="boolean", nullable=true)
     */
    private $top;

    /**
     * @var bool
     *
     * @ORM\Column(name="new", type="boolean")
     */
    private $new;

    /**
     * @ORM\ManyToMany(targetEntity="Kitchen", inversedBy="places")
     * @ORM\JoinTable(name="place_kitchen",
     *      joinColumns={@ORM\JoinColumn(name="place_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="kitchen_id", referencedColumnName="id")}
     *      )
     */
    private $kitchens;

    /**
     * @ORM\OneToMany(targetEntity="Dish", mappedBy="place")
     */
    private $dishes;

    /**
     * @ORM\OneToMany(targetEntity="Food\PlacesBundle\Entity\BestOffer", mappedBy="place")
     */
    private $bestOffers;

    /**
     * @ORM\OneToMany(targetEntity="PlacePoint", mappedBy="place", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var ArrayCollection
     */
    private $points;

    /**
     * @ORM\ManyToMany(targetEntity="Food\AppBundle\Entity\SeoRecord", mappedBy="places")
     */
    private $seorecords = array();

    /**
     * @ORM\OneToMany(targetEntity="PlaceCoverPhoto", mappedBy="place", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var ArrayCollection
     */
    private $photos;

    /**
     * @var int
     *
     * @ORM\Column(name="delivery_price", type="float")
     */
    private $deliveryPrice;

    /**
     * @var int
     *
     * @ORM\Column(name="delivery_price_old", type="integer", nullable=true)
     */
    private $deliveryPriceOld;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_time", type="string")
     */
    private $deliveryTime;

    /**
     * @var string
     *
     * @ORM\Column(name="pickup_time", type="string")
     */
    private $pickupTime;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_time_info", type="string", nullable=true)
     */
    private $deliveryTimeInfo;

    /**
     * @var float
     *
     * @ORM\Column(name="cart_minimum", type="float")
     */
    private $cartMinimum;

    /**
     * @var int
     *
     * @ORM\Column(name="cart_minimum_old", type="integer", nullable=true)
     */
    private $cartMinimumOld;

    /**
     * @var bool
     *
     * @ORM\Column(name="self_delivery", type="boolean")
     */
    private $selfDelivery = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="min_on_self", type="boolean")
     */
    private $minimalOnSelfDel = false;

    /**
     * Does place accept card on delivery?
     * @var bool
     *
     * @ORM\Column(name="card_on_delivery", type="boolean")
     */
    private $cardOnDelivery = false;

    /**
     * This place does not accet online payments
     * @var bool
     *
     * @ORM\Column(name="disabled_online_payment", type="boolean")
     */
    private $disabledOnlinePayment = false;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_options", type="string", length=64)
     */
    private $deliveryOptions;

    /**
     * @var int
     *
     * @ORM\Column(name="priority", type="smallint", options={"default":0}, nullable=true)
     */
    private $priority;

    /**
     * @ORM\OneToMany(targetEntity="FoodCategory", mappedBy="place", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var ArrayCollection
     */
    private $categories;


    /**
     * @ORM\OneToMany(targetEntity="PlaceReviews", mappedBy="place")
     * @ORM\OrderBy({"createdAt" = "DESC"})
     */
    private $reviews;

    /**
     * @var float
     *
     * @ORM\Column(name="average_rating", type="float")
     */
    private $averageRating = 0;
    /**
     * @var int
     *
     * @ORM\Column(name="review_count", type="integer")
     */
    private $reviewCount = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="send_invoice", type="boolean")
     */
    private $sendInvoice = false;

    /**
     * @ORM\OneToMany(targetEntity="\Food\UserBundle\Entity\User", mappedBy="place")
     **/
    private $users;

    /**
     * @var string
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="edited_at", type="datetime", nullable=true)
     */
    private $editedAt;

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
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     **/
    private $createdBy;

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

    protected $resizeMode = \Imagine\Image\ImageInterface::THUMBNAIL_INSET;
    protected $boxSize = array('w' => 130, 'h' => 86);

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * @ORM\OneToMany(targetEntity="PlaceLocalized", mappedBy="object", cascade={"persist", "remove"})
     **/
    private $translations;

    /**
     * Returns place name
     *
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
     * Constructor
     */
    public function __construct()
    {
        $this->uploadDir = 'uploads/places';
        $this->kitchens = new \Doctrine\Common\Collections\ArrayCollection();
        $this->localized = new \Doctrine\Common\Collections\ArrayCollection();
        $this->points = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->photos = new \Doctrine\Common\Collections\ArrayCollection();
        $this->deliveryOptions = self::OPT_DELIVERY_AND_PICKUP;
        $this->priority = 0;
    }

    /**
     * @return array
     */
    public function getBoxSize()
    {
        return $this->boxSize;
    }

    /**
     * @return string
     */
    public function getResizeMode()
    {
        return $this->resizeMode;
    }

    /**
     * @inheritdoc
     * @return string
     */
    public function getUploadDir()
    {
        if (empty($this->uploadDir)) {
            $this->uploadDir = 'uploads/places';
        }
        return $this->uploadDir;
    }

    /**
     * @inheritdoc
     * @return string
     */
    public function getUploadableField()
    {
        if (empty($this->uploadableField)) {
            $this->uploadableField = 'logo';
        }
        return $this->uploadableField;
    }

    /**
     * @param \Doctrine\ORM\EntityManager $em
     * @return string
     */
    public function getOrigName(\Doctrine\ORM\EntityManager $em)
    {
        $query = $em->createQuery("SELECT o.name FROM FoodDishesBundle:Place as o WHERE o.id=:id")
            ->setParameter('id', $this->getId());
        $res = ($query->getSingleResult());
        return $res['name'];
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
     * @return Place
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
     * Set logo
     *
     * @param string $logo
     * @return Place
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    
        return $this;
    }

    /**
     * Get logo
     *
     * @return string 
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return Place
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Place
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
     * @param \DateTime $editedAt
     * @return Place
     */
    public function setEditedAt($editedAt)
    {
        $this->editedAt = $editedAt;
    
        return $this;
    }

    /**
     * Get editedAt
     *
     * @return \DateTime 
     */
    public function getEditedAt()
    {
        return $this->editedAt;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     * @return Place
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    
        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime 
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Add kitchens
     *
     * @param \Food\DishesBundle\Entity\Kitchen $kitchens
     * @return Place
     */
    public function addKitchen(\Food\DishesBundle\Entity\Kitchen $kitchens)
    {
        $this->kitchens[] = $kitchens;
    
        return $this;
    }

    /**
     * Remove kitchens
     *
     * @param \Food\DishesBundle\Entity\Kitchen $kitchens
     */
    public function removeKitchen(\Food\DishesBundle\Entity\Kitchen $kitchens)
    {
        $this->kitchens->removeElement($kitchens);
    }

    /**
     * Get kitchens
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getKitchens()
    {
        return $this->kitchens;
    }

    /**
     * Add points
     *
     * @param \Food\DishesBundle\Entity\PlacePoint $points
     * @return Place
     */
    public function addPoint(\Food\DishesBundle\Entity\PlacePoint $points)
    {
        $this->points[] = $points;
    
        return $this;
    }

    /**
     * Remove points
     *
     * @param \Food\DishesBundle\Entity\PlacePoint $points
     */
    public function removePoint(\Food\DishesBundle\Entity\PlacePoint $points)
    {
        $this->points->removeElement($points);
    }

    /**
     * Get points
     *
     * @return PlacePoint[]
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * Add users
     *
     * @param \Food\UserBundle\Entity\User $users
     * @return Place
     */
    public function addUser(\Food\UserBundle\Entity\User $users)
    {
        $this->users[] = $users;
    
        return $this;
    }

    /**
     * Remove users
     *
     * @param \Food\UserBundle\Entity\User $users
     */
    public function removeUser(\Food\UserBundle\Entity\User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return Place
     */
    public function setPath($path)
    {
        $this->path = $path;
    
        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set file
     *
     * @param string $file
     * @return Place
     */
    public function setFile($file)
    {
        $this->file = $file;
    
        return $this;
    }

    /**
     * Get file
     *
     * @return string 
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set createdBy
     *
     * @param \Food\UserBundle\Entity\User $createdBy
     * @return Place
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
     * @return Place
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
     * @return Place
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
     * Add categories
     *
     * @param \Food\DishesBundle\Entity\FoodCategory $categories
     * @return Place
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
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Add dishes
     *
     * @param \Food\DishesBundle\Entity\Dish $dishes
     * @return Place
     */
    public function addDishe(\Food\DishesBundle\Entity\Dish $dishes)
    {
        $this->dishes[] = $dishes;
    
        return $this;
    }

    /**
     * Remove dishes
     *
     * @param \Food\DishesBundle\Entity\Dish $dishes
     */
    public function removeDishe(\Food\DishesBundle\Entity\Dish $dishes)
    {
        $this->dishes->removeElement($dishes);
    }

    /**
     * Get dishes
     *
     * @return Dish[]
     */
    public function getDishes()
    {
        return $this->dishes;
    }

    /**
     * Add dishes
     *
     * @param \Food\DishesBundle\Entity\Dish $dishes
     * @return Place
     */
    public function addDish(\Food\DishesBundle\Entity\Dish $dishes)
    {
        $this->dishes[] = $dishes;
    
        return $this;
    }

    /**
     * Remove dishes
     *
     * @param \Food\DishesBundle\Entity\Dish $dishes
     */
    public function removeDish(\Food\DishesBundle\Entity\Dish $dishes)
    {
        $this->dishes->removeElement($dishes);
    }

    /**
     * Add categories
     *
     * @param \Food\DishesBundle\Entity\FoodCategory $categories
     * @return Place
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
     * Add reviews
     *
     * @param \Food\DishesBundle\Entity\PlaceReviews $reviews
     * @return Place
     */
    public function addReview(\Food\DishesBundle\Entity\PlaceReviews $reviews)
    {
        $this->reviews[] = $reviews;
    
        return $this;
    }

    /**
     * Remove reviews
     *
     * @param \Food\DishesBundle\Entity\PlaceReviews $reviews
     */
    public function removeReview(\Food\DishesBundle\Entity\PlaceReviews $reviews)
    {
        $this->reviews->removeElement($reviews);
    }

    /**
     * Get reviews
     *
     * @return \Food\DishesBundle\Entity\PlaceReviews[]
     */
    public function getReviews()
    {
        return $this->reviews;
    }


    /**
     * Get average rating :) meh....
     * @return int
     */
    public function getRating()
    {
        $reviews = $this->getReviews();
        $sums = 0;
        $counts = 0;
        foreach ($reviews as $rev) {
            $sums += $rev->getRate();
            $counts++;
        }
        if ($sums == 0) {
            return 0;
        }
        return floor(($sums / $counts) * 10) / 10;
    }

    /**
     * Set slogan
     *
     * @param string $slogan
     * @return Place
     */
    public function setSlogan($slogan)
    {
        $this->slogan = $slogan;
    
        return $this;
    }

    /**
     * Get slogan
     *
     * @return string 
     */
    public function getSlogan()
    {
        return $this->slogan;
    }

    /**
     * Set new
     *
     * @param boolean $new
     * @return Place
     */
    public function setNew($new)
    {
        $this->new = $new;
    
        return $this;
    }

    /**
     * Get new
     *
     * @return boolean 
     */
    public function getNew()
    {
        return $this->new;
    }

    /**
     * Set deliveryPrice
     *
     * @param integer $deliveryPrice
     * @return Place
     */
    public function setDeliveryPrice($deliveryPrice)
    {
        $this->deliveryPrice = $deliveryPrice;
    
        return $this;
    }

    /**
     * Get deliveryPrice
     *
     * @return integer 
     */
    public function getDeliveryPrice()
    {
        return $this->deliveryPrice;
    }

    /**
     * Set deliveryTime
     *
     * @param string $deliveryTime
     * @return Place
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->deliveryTime = $deliveryTime;
    
        return $this;
    }

    /**
     * Get deliveryTime
     *
     * @return string 
     */
    public function getDeliveryTime()
    {
        return $this->deliveryTime;
    }

    /**
     * Set cartMinimum
     *
     * @param integer $cartMinimum
     * @return Place
     */
    public function setCartMinimum($cartMinimum)
    {
        $this->cartMinimum = $cartMinimum;
    
        return $this;
    }

    /**
     * Get cartMinimum
     *
     * @return float
     */
    public function getCartMinimum()
    {
        return $this->cartMinimum;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Place
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
     * Add translations
     *
     * @param \Food\DishesBundle\Entity\PlaceLocalized $translations
     * @return Place
     */
    public function addTranslation(\Food\DishesBundle\Entity\PlaceLocalized $translations)
    {
        if (method_exists($this->translations, 'contains')) {
            if (!$this->translations->contains($translations)) {
                $this->translations[] = $translations;
                $translations->setObject($this);
            }
        }

        return $this;
    }

    /**
     * Remove translations
     *
     * @param \Food\DishesBundle\Entity\PlaceLocalized $translations
     */
    public function removeTranslation(\Food\DishesBundle\Entity\PlaceLocalized $translations)
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
     * @param mixed $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set averageRating
     *
     * @param float $averageRating
     * @return Place
     */
    public function setAverageRating($averageRating)
    {
        $this->averageRating = $averageRating;
    
        return $this;
    }

    /**
     * Get averageRating
     *
     * @return float
     */
    public function getAverageRating()
    {
        return $this->averageRating;
    }

    /**
     * Set recommended
     *
     * @param boolean $recommended
     * @return Place
     */
    public function setRecommended($recommended)
    {
        $this->recommended = $recommended;
    
        return $this;
    }

    /**
     * Get recommended
     *
     * @return boolean 
     */
    public function getRecommended()
    {
        return $this->recommended;
    }

    /**
     * Set selfDelivery
     *
     * @param boolean $selfDelivery
     * @return Place
     */
    public function setSelfDelivery($selfDelivery)
    {
        $this->selfDelivery = $selfDelivery;
    
        return $this;
    }

    /**
     * Get selfDelivery
     *
     * @return boolean 
     */
    public function getSelfDelivery()
    {
        return $this->selfDelivery;
    }

    /**
     * Set deliveryTimeInfo
     *
     * @param string $deliveryTimeInfo
     * @return Place
     */
    public function setDeliveryTimeInfo($deliveryTimeInfo)
    {
        $this->deliveryTimeInfo = $deliveryTimeInfo;
    
        return $this;
    }

    /**
     * Get deliveryTimeInfo
     *
     * @return string 
     */
    public function getDeliveryTimeInfo()
    {
        return $this->deliveryTimeInfo;
    }

    /**
     * Set minimalOnSelfDel
     *
     * @param boolean $minimalOnSelfDel
     * @return Place
     */
    public function setMinimalOnSelfDel($minimalOnSelfDel)
    {
        $this->minimalOnSelfDel = $minimalOnSelfDel;
    
        return $this;
    }

    /**
     * Get minimalOnSelfDel
     *
     * @return boolean 
     */
    public function getMinimalOnSelfDel()
    {
        return $this->minimalOnSelfDel;
    }

    /**
     * Set cardOnDelivery
     *
     * @param boolean $cardOnDelivery
     * @return Place
     */
    public function setCardOnDelivery($cardOnDelivery)
    {
        $this->cardOnDelivery = $cardOnDelivery;
    
        return $this;
    }

    /**
     * Get cardOnDelivery
     *
     * @return boolean 
     */
    public function getCardOnDelivery()
    {
        return $this->cardOnDelivery;
    }

    /**
     * Set alcoholRules
     *
     * @param string $alcoholRules
     * @return Place
     */
    public function setAlcoholRules($alcoholRules)
    {
        $this->alcoholRules = $alcoholRules;
    
        return $this;
    }

    /**
     * Get alcoholRules
     *
     * @return string 
     */
    public function getAlcoholRules()
    {
        return $this->alcoholRules;
    }

    /**
     * Add photos
     *
     * @param \Food\DishesBundle\Entity\PlaceCoverPhoto $photos
     * @return Place
     */
    public function addPhoto(\Food\DishesBundle\Entity\PlaceCoverPhoto $photos)
    {
        $this->photos[] = $photos;
    
        return $this;
    }

    /**
     * Remove photos
     *
     * @param \Food\DishesBundle\Entity\PlaceCoverPhoto $photos
     */
    public function removePhoto(\Food\DishesBundle\Entity\PlaceCoverPhoto $photos)
    {
        $this->photos->removeElement($photos);
    }

    /**
     * Get photos
     *
     * @return PlaceCoverPhoto[]
     */
    public function getPhotos()
    {
        return $this->photos;
    }

    /**
     * Set disabledOnlinePayment
     *
     * @param boolean $disabledOnlinePayment
     * @return Place
     */
    public function setDisabledOnlinePayment($disabledOnlinePayment)
    {
        $this->disabledOnlinePayment = $disabledOnlinePayment;
    
        return $this;
    }

    /**
     * Get disabledOnlinePayment
     *
     * @return boolean 
     */
    public function getDisabledOnlinePayment()
    {
        return $this->disabledOnlinePayment;
    }

    /**
     * Set deliveryOptions
     *
     * @param string $deliveryOptions
     *
     * @throws \InvalidArgumentException
     * @return Place
     */
    public function setDeliveryOptions($deliveryOptions)
    {
        if (!in_array($deliveryOptions, array(self::OPT_DELIVERY_AND_PICKUP, self::OPT_ONLY_DELIVERY, self::OPT_ONLY_PICKUP))) {
            throw new \InvalidArgumentException('Unknown delivery opion: '.$deliveryOptions);
        }
        $this->deliveryOptions = $deliveryOptions;
    
        return $this;
    }

    /**
     * Get deliveryOptions
     *
     * @return string 
     */
    public function getDeliveryOptions()
    {
        return $this->deliveryOptions;
    }

    /**
     * Set chain
     *
     * @param string $chain
     * @return Place
     */
    public function setChain($chain)
    {
        $this->chain = $chain;
    
        return $this;
    }

    /**
     * Get chain
     *
     * @return string 
     */
    public function getChain()
    {
        return $this->chain;
    }

    /**
     * Set navision
     *
     * @param boolean $navision
     * @return Place
     */
    public function setNavision($navision)
    {
        $this->navision = $navision;
    
        return $this;
    }

    /**
     * Get navision
     *
     * @return boolean 
     */
    public function getNavision()
    {
        return $this->navision;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     * @return Place
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    
        return $this;
    }

    /**
     * Get priority
     *
     * @return integer 
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set onlyAlcohol
     *
     * @param boolean $onlyAlcohol
     * @return Place
     */
    public function setOnlyAlcohol($onlyAlcohol)
    {
        $this->onlyAlcohol = $onlyAlcohol;
    
        return $this;
    }

    /**
     * Get onlyAlcohol
     *
     * @return boolean 
     */
    public function getOnlyAlcohol()
    {
        return $this->onlyAlcohol;
    }

    /**
     * Set sendInvoice
     *
     * @param boolean $sendInvoice
     * @return Place
     */
    public function setSendInvoice($sendInvoice)
    {
        $this->sendInvoice = $sendInvoice;
    
        return $this;
    }

    /**
     * Get sendInvoice
     *
     * @return boolean 
     */
    public function getSendInvoice()
    {
        return $this->sendInvoice;
    }

    /**
     * Set discountPricesEnabled
     *
     * @param boolean $discountPricesEnabled
     * @return Place
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

    public function isFileSizeValid(ExecutionContextInterface $context)
    {
        if ($this->getFile() && $this->getFile()->getSize() > round($this->maxFileSize * 1024 * 1024)) {
            $context->addViolationAt('file', 'Paveiksliukas užima daugiau nei ' . $this->maxFileSize . ' MB vietos.');
        }
    }

    /**
     * Set reviewCount
     *
     * @param integer $reviewCount
     * @return Place
     */
    public function setReviewCount($reviewCount)
    {
        $this->reviewCount = $reviewCount;
    
        return $this;
    }

    /**
     * Get reviewCount
     *
     * @return integer 
     */
    public function getReviewCount()
    {
        return $this->reviewCount;
    }

    /**
     * Set top
     *
     * @param boolean $top
     * @return Place
     */
    public function setTop($top)
    {
        $this->top = $top;
    
        return $this;
    }

    /**
     * Get top
     *
     * @return boolean 
     */
    public function getTop()
    {
        return $this->top;
    }

    /**
     * Set deliveryPriceOld
     *
     * @param integer $deliveryPriceOld
     * @return Place
     */
    public function setDeliveryPriceOld($deliveryPriceOld)
    {
        $this->deliveryPriceOld = $deliveryPriceOld;
    
        return $this;
    }

    /**
     * Get deliveryPriceOld
     *
     * @return integer 
     */
    public function getDeliveryPriceOld()
    {
        return $this->deliveryPriceOld;
    }

    /**
     * Set cartMinimumOld
     *
     * @param integer $cartMinimumOld
     * @return Place
     */
    public function setCartMinimumOld($cartMinimumOld)
    {
        $this->cartMinimumOld = $cartMinimumOld;
    
        return $this;
    }

    /**
     * Get cartMinimumOld
     *
     * @return integer 
     */
    public function getCartMinimumOld()
    {
        return $this->cartMinimumOld;
    }

    /**
     * Set showNotification
     *
     * @param boolean $showNotification
     * @return Place
     */
    public function setShowNotification($showNotification)
    {
        $this->showNotification = $showNotification;
    
        return $this;
    }

    /**
     * Get showNotification
     *
     * @return boolean 
     */
    public function getShowNotification()
    {
        return $this->showNotification;
    }

    /**
     * Set notificationContent
     *
     * @param string $notificationContent
     * @return Place
     */
    public function setNotificationContent($notificationContent)
    {
        $this->notificationContent = $notificationContent;
    
        return $this;
    }

    /**
     * Get notificationContent
     *
     * @return string 
     */
    public function getNotificationContent()
    {
        return $this->notificationContent;
    }

    /**
     * Add seorecords
     *
     * @param \Food\AppBundle\Entity\SeoRecord $seorecords
     * @return Place
     */
    public function addSeorecord(\Food\AppBundle\Entity\SeoRecord $seorecords)
    {
        $this->seorecords[] = $seorecords;
    
        return $this;
    }

    /**
     * Remove seorecords
     *
     * @param \Food\AppBundle\Entity\SeoRecord $seorecords
     */
    public function removeSeorecord(\Food\AppBundle\Entity\SeoRecord $seorecords)
    {
        $this->seorecords->removeElement($seorecords);
    }

    /**
     * Get seorecords
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSeorecords()
    {
        return $this->seorecords;
    }

    public function getSEO($field = 'Title')
    {
        $seo_line = "";
        $seo_records = $this->getSeorecords();
        if (count($seo_records) > 0) {
            foreach ($seo_records as $seo_record) {
                $seo_line .= $seo_record->{'get' . $field}() . " ";
            }
            return rtrim($seo_line, " ");
        }
        return false;
    }

    /**
     * Add bestOffers
     *
     * @param \Food\PlacesBundle\Entity\BestOffer $bestOffers
     * @return Place
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
     * Set pickupTime
     *
     * @param string $pickupTime
     * @return Place
     */
    public function setPickupTime($pickupTime)
    {
        $this->pickupTime = $pickupTime;
    
        return $this;
    }

    /**
     * Get pickupTime
     *
     * @return string 
     */
    public function getPickupTime()
    {
        return $this->pickupTime;
    }
}