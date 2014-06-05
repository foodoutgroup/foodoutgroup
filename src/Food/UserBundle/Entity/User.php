<?php

namespace Food\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Food\DishesBundle\Entity\Place", inversedBy="users")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     **/
    private $place;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=255, nullable=true)
     */
    private $firstname = null;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=255, nullable=true)
     */
    private $lastname = null;

    /**
     * @ORM\OneToMany(targetEntity="UserAddress", mappedBy="user", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     **/
    private $address;

    /**
     * @ORM\Column(name="fully_registered", type="smallint", nullable=true)
     */
    private $fully_registered = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     */
    private $phone = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function getContact()
    {
        if (!$this->getId()) {
            return '';
        }
        $userContactData = $this->getFirstname();
        $surname = $this->getLastname();
        $email = $this->getEmail();
        $phone = $this->getPhone();

        if (!empty($surname)) {
            $userContactData .= ' '.$surname;
        }
        if (!empty($email)) {
            $userContactData .= ', '.$email;
        }
        if (!empty($phone)) {
            $userContactData .= ', '.$phone;
        }

        return $userContactData;
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
     * Remove places
     *
     * @param \Food\DishesBundle\Entity\Place $places
     */
    public function removePlace(\Food\DishesBundle\Entity\Place $places)
    {
        $this->places->removeElement($places);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => $this->getId(),
            'groupNames' => $this->getGroupNames(),
            'roles' => $this->getRoles(),
        );
    }

    /**
     * Set place
     *
     * @param \Food\DishesBundle\Entity\Place $place
     * @return User
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
     * Set firstname
     *
     * @param string $firstname
     * @return User
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    
        return $this;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return User
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    
        return $this;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Add address
     *
     * @param \Food\UserBundle\Entity\UserAddress $address
     * @return User
     */
    public function addAddress(\Food\UserBundle\Entity\UserAddress $address)
    {
        $this->address[] = $address;
    
        return $this;
    }

    /**
     * Remove address
     *
     * @param \Food\UserBundle\Entity\UserAddress $address
     */
    public function removeAddress(\Food\UserBundle\Entity\UserAddress $address)
    {
        $this->address->removeElement($address);
    }

    /**
     * Get address
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Get address
     *
     * @return UserAddress
     */
    public function getDefaultAddress()
    {
        return $this->address[0];
    }

    /**
     * Add address
     *
     * @param \Food\UserBundle\Entity\UserAddress $address
     * @return User
     */
    public function addAddres(\Food\UserBundle\Entity\UserAddress $address)
    {
        $this->address[] = $address;
    
        return $this;
    }

    /**
     * Remove address
     *
     * @param \Food\UserBundle\Entity\UserAddress $address
     */
    public function removeAddres(\Food\UserBundle\Entity\UserAddress $address)
    {
        $this->address->removeElement($address);
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return User
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    
        return $this;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Because f**k FOSUserBundle.
     */
    public function setEmail($email)
    {
        $email = is_null($email) ? '' : $email;
        parent::setEmail($email);
        $this->setUsername($email);
    }

    /**
     * Set fully_registered
     *
     * @param integer $fullyRegistered
     * @return User
     */
    public function setFullyRegistered($fullyRegistered)
    {
        $this->fully_registered = $fullyRegistered;
    
        return $this;
    }

    /**
     * Get fully_registered
     *
     * @return integer 
     */
    public function getFullyRegistered()
    {
        return $this->fully_registered;
    }
}