<?php

namespace Food\DishesBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Food\AppBundle\Entity\Uploadable;

/**
 * Client
 *
 * @ORM\Table()
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class Place extends Uploadable
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", length=255)
     * aaaaaa@aaaAssert\NotBlank
     */
    private $logo = "";

    /**
     * @var object
     */
    public $file;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

    /**
     * @ORM\ManyToMany(targetEntity="Kitchen", inversedBy="places")
     */
    private $kitchens;

    /**
     * @ORM\OneToMany(targetEntity="PlaceLocalized", mappedBy="id")
     **/
    private $localized;


    /**
     * @ORM\OneToMany(targetEntity="PlacePoint", mappedBy="place", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var ArrayCollection
     */
    private $points;


    /**
     * @ORM\OneToMany(targetEntity="\Food\UserBundle\Entity\User", mappedBy="id")
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
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User", inversedBy="user")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     **/
    private $createdBy;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User", inversedBy="user")
     * @ORM\JoinColumn(name="edited_by", referencedColumnName="id")
     */
    private $editedBy;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User", inversedBy="user")
     * @ORM\JoinColumn(name="deleted_by", referencedColumnName="id")
     */
    private $deletedBy;

    /**
     * @var string
     */
    public $uploadableField = 'logo';

    /**
     * TODO
     *
     * @return string
     */
    public function __toString()
    {
        // TODO return localized
        return $this->getName();
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->kitchens = new \Doctrine\Common\Collections\ArrayCollection();
        $this->localized = new \Doctrine\Common\Collections\ArrayCollection();
        $this->points = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add localized
     *
     * @param \Food\DishesBundle\Entity\PlaceLocalized $localized
     * @return Place
     */
    public function addLocalized(\Food\DishesBundle\Entity\PlaceLocalized $localized)
    {
        $this->localized[] = $localized;
    
        return $this;
    }

    /**
     * Remove localized
     *
     * @param \Food\DishesBundle\Entity\PlaceLocalized $localized
     */
    public function removeLocalized(\Food\DishesBundle\Entity\PlaceLocalized $localized)
    {
        $this->localized->removeElement($localized);
    }

    /**
     * Get localized
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getLocalized()
    {
        return $this->localized;
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
     * @return \Doctrine\Common\Collections\Collection 
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
}