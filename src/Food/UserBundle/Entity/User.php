<?php

namespace Food\UserBundle\Entity;

use Food\DishesBundle\Entity\Place;
use Food\UserBundle\Entity\UserAddress;
use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="UserRepository")
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
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="Food\DishesBundle\Entity\Place", inversedBy="users")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     **/
    private $place;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_id", type="string", length=255, nullable=true)
     */
    private $facebook_id;

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

    /**
     * @var string
     *
     * @ORM\Column(name="api_token", type="string", length=64, nullable=true)
     */
    private $apiToken = null;

    /**
     * @var string
     *
     * @ORM\Column(name="api_token_validity", type="datetime", nullable=true)
     */
    private $apiTokenValidity = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="date", nullable=true)
     */
    private $birthday = null;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_bussines_client", type="boolean", nullable=true)
     */
    private $isBussinesClient = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="no_invoice", type="boolean", nullable=true)
     */
    private $noInvoice = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="no_minimum_cart", type="boolean", nullable=true)
     */
    private $noMinimumCart = false;

    /**
     * @var string
     * @ORM\Column(name="user_group", type="string", length=160, nullable=true)
     */
    private $group;

    /**
     * @var string
     * @ORM\Column(name="company_name", type="string", length=160, nullable=true)
     */
    private $companyName;

    /**
     * @var string
     * @ORM\Column(name="company_code", type="string", length=60, nullable=true)
     */
    private $companyCode;

    /**
     * @var string
     * @ORM\Column(name="vat_code", type="string", length=60, nullable=true)
     */
    private $vatCode;

    /**
     * @var string
     * @ORM\Column(name="company_address", type="text", nullable=true)
     */
    private $company_address;

    /**
     * @var string
     * @ORM\Column(name="checking_account", type="string", length=24, nullable=true)
     */
    private $checkingAccount;

    /**
     * @var integer
     * @ORM\Column(name="workers_count", type="integer", nullable=true)
     */
    private $workersCount;

    /**
     * @var string
     * @ORM\Column(name="director_first_name", type="string", length=60, nullable=true)
     */
    private $directorFirstName;

    /**
     * @var string
     * @ORM\Column(name="director_last_name", type="string", length=60, nullable=true)
     */
    private $directorLastName;

    /**
     * @var float
     * @ORM\Column(name="discount", type="decimal", precision=5, scale=2, nullable=true)
     */
    private $discount;

    /**
     * @var boolean
     * @ORM\Column(name="allow_delay_payment", type="boolean", nullable=true)
     */
    private $allowDelayPayment = false;

    /**
     * @var boolean
     * @ORM\Column(name="required_division", type="boolean", nullable=true)
     */
    private $requiredDivision = false;

    /**
     * @var boolean
     * @ORM\Column(name="regenerate_password", type="boolean", nullable=true)
     */
    private $regeneratePassword = false;

    /**
     * @ORM\OneToMany(targetEntity="UserDivisionCode", mappedBy="user", cascade={"persist", "remove"})
     **/
    private $divisionCodes;

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime('now'));

        parent::__construct();
    }

    /**
     * @return string
     */
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

    public function getNameForOrder()
    {
        $theName = "";
        $name = $this->getFirstname();
        $surname = $this->getLastname();
        if (!empty($name)) {
            $theName = $name;
        }
        if (!empty($surname)) {
            if (!empty($theName)) {
                $theName.=" ";
            }
            $theName.= $surname;
        }
        return $theName;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        if (!$this->getId()) {
            return '';
        }

        $fullUserName = $this->getFirstname();

        $lastname = $this->getLastname();
        if (!empty($lastname)) {
            $fullUserName .= ' '.$lastname;
        }

        return $fullUserName;
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
     * @param Place $places
     */
    public function removePlace(Place $places)
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
     * @param Place $place
     * @return User
     */
    public function setPlace(Place $place = null)
    {
        $this->place = $place;
    
        return $this;
    }

    /**
     * Get place
     *
     * @return Place
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
     * @param UserAddress $address
     * @return User
     */
    public function addAddress(UserAddress $address)
    {
        $this->address[] = $address;
    
        return $this;
    }

    /**
     * Remove address
     *
     * @param UserAddress $address
     */
    public function removeAddress(UserAddress $address)
    {
        $this->address->removeElement($address);
    }

    /**
     * Get address
     *
     * @return UserAddress[]
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

    public function getCurrentDefaultAddress()
    {
        $address = null;
        if (!empty($this->address)) {
            foreach($this->address as $address) {
            }
        }
        return $address;
    }

    /**
     * Add address
     *
     * @param UserAddress $address
     * @return User
     */
    public function addAddres(UserAddress $address)
    {
        $this->address[] = $address;
    
        return $this;
    }

    /**
     * Remove address
     *
     * @param UserAddress $address
     */
    public function removeAddres(UserAddress $address)
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

    /**
     * Set apiToken
     *
     * @param string $apiToken
     * @return User
     */
    public function setApiToken($apiToken)
    {
        $this->apiToken = $apiToken;
    
        return $this;
    }

    /**
     * Get apiToken
     *
     * @return string 
     */
    public function getApiToken()
    {
        return $this->apiToken;
    }

    /**
     * Set apiTokenValidity
     *
     * @param \DateTime $apiTokenValidity
     * @return User
     */
    public function setApiTokenValidity($apiTokenValidity)
    {
        $this->apiTokenValidity = $apiTokenValidity;
    
        return $this;
    }

    /**
     * Get apiTokenValidity
     *
     * @return \DateTime 
     */
    public function getApiTokenValidity()
    {
        return $this->apiTokenValidity;
    }

    /**
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * Set birthday
     *
     * @param \DateTime $birthday
     * @return User
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;
    
        return $this;
    }

    /**
     * Get birthday
     *
     * @return \DateTime 
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param \DateTime $expiresAt
     * @return User
     */
    public function setExpiresAt(\DateTime $expiresAt)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCredentialsExpireAt()
    {
        return $this->credentialsExpireAt;
    }

    /**
     * Set isBussinesClient
     *
     * @param boolean $isBussinesClient
     * @return User
     */
    public function setIsBussinesClient($isBussinesClient)
    {
        if ($isBussinesClient && !$this->getIsBussinesClient()) {
            $this->setCreatedAt(new \DateTime());
        }
        $this->isBussinesClient = $isBussinesClient;
    
        return $this;
    }

    /**
     * Get isBussinesClient
     *
     * @return boolean 
     */
    public function getIsBussinesClient()
    {
        return $this->isBussinesClient;
    }

    /**
     * Set companyName
     *
     * @param string $companyName
     * @return User
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;
    
        return $this;
    }

    /**
     * Get companyName
     *
     * @return string 
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * Set companyCode
     *
     * @param string $companyCode
     * @return User
     */
    public function setCompanyCode($companyCode)
    {
        $this->companyCode = $companyCode;
    
        return $this;
    }

    /**
     * Get companyCode
     *
     * @return string 
     */
    public function getCompanyCode()
    {
        return $this->companyCode;
    }

    /**
     * Set vatCode
     *
     * @param string $vatCode
     * @return User
     */
    public function setVatCode($vatCode)
    {
        $this->vatCode = $vatCode;
    
        return $this;
    }

    /**
     * Get vatCode
     *
     * @return string 
     */
    public function getVatCode()
    {
        return $this->vatCode;
    }

    /**
     * Set company_address
     *
     * @param string $companyAddress
     * @return User
     */
    public function setCompanyAddress($companyAddress)
    {
        $this->company_address = $companyAddress;
    
        return $this;
    }

    /**
     * Get company_address
     *
     * @return string 
     */
    public function getCompanyAddress()
    {
        return $this->company_address;
    }

    /**
     * Add divisionCodes
     *
     * @param \Food\UserBundle\Entity\UserDivisionCode $divisionCodes
     * @return User
     */
    public function addDivisionCode(\Food\UserBundle\Entity\UserDivisionCode $divisionCodes)
    {
        $this->divisionCodes[] = $divisionCodes;
    
        return $this;
    }

    /**
     * Remove divisionCodes
     *
     * @param \Food\UserBundle\Entity\UserDivisionCode $divisionCodes
     */
    public function removeDivisionCode(\Food\UserBundle\Entity\UserDivisionCode $divisionCodes)
    {
        $this->divisionCodes->removeElement($divisionCodes);
    }

    /**
     * Get divisionCodes
     *
     * @return \Doctrine\Common\Collections\Collection|UserDivisionCode[]
     */
    public function getDivisionCodes()
    {
        return $this->divisionCodes;
    }

    /**
     * Set facebook_id
     *
     * @param string $facebookId
     * @return User
     */
    public function setFacebookId($facebookId)
    {
        $this->facebook_id = $facebookId;
    
        return $this;
    }

    /**
     * Get facebook_id
     *
     * @return string 
     */
    public function getFacebookId()
    {
        return $this->facebook_id;
    }

    /**
     * Set noInvoice
     *
     * @param boolean $noInvoice
     * @return User
     */
    public function setNoInvoice($noInvoice)
    {
        $this->noInvoice = $noInvoice;

        return $this;
    }

    /**
     * Get noInvoice
     *
     * @return boolean
     */
    public function getNoInvoice()
    {
        return $this->noInvoice;
    }

    /**
     * Set noMinimumCart
     *
     * @param boolean $noMinimumCart
     * @return User
     */
    public function setNoMinimumCart($noMinimumCart)
    {
        $this->noMinimumCart = $noMinimumCart;

        return $this;
    }

    /**
     * Get noMinimumCart
     *
     * @return boolean
     */
    public function getNoMinimumCart()
    {
        return $this->noMinimumCart;
    }

    /**
     * Set checkingAccount
     *
     * @param string $checkingAccount
     * @return User
     */
    public function setCheckingAccount($checkingAccount)
    {
        $this->checkingAccount = $checkingAccount;
    
        return $this;
    }

    /**
     * Get checkingAccount
     *
     * @return string 
     */
    public function getCheckingAccount()
    {
        return $this->checkingAccount;
    }

    /**
     * Set workersCount
     *
     * @param integer $workersCount
     * @return User
     */
    public function setWorkersCount($workersCount)
    {
        $this->workersCount = $workersCount;
    
        return $this;
    }

    /**
     * Get workersCount
     *
     * @return integer 
     */
    public function getWorkersCount()
    {
        return $this->workersCount;
    }

    /**
     * Set directorFirstName
     *
     * @param string $directorFirstName
     * @return User
     */
    public function setDirectorFirstName($directorFirstName)
    {
        $this->directorFirstName = $directorFirstName;
    
        return $this;
    }

    /**
     * Get directorFirstName
     *
     * @return string 
     */
    public function getDirectorFirstName()
    {
        return $this->directorFirstName;
    }

    /**
     * Set directorLastName
     *
     * @param string $directorLastName
     * @return User
     */
    public function setDirectorLastName($directorLastName)
    {
        $this->directorLastName = $directorLastName;
    
        return $this;
    }

    /**
     * Get directorLastName
     *
     * @return string 
     */
    public function getDirectorLastName()
    {
        return $this->directorLastName;
    }

    /**
     * Set discount
     *
     * @param string $discount
     * @return User
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
    
        return $this;
    }

    /**
     * Get discount
     *
     * @return string 
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set allowDelayPayment
     *
     * @param boolean $allowDelayPayment
     * @return User
     */
    public function setAllowDelayPayment($allowDelayPayment)
    {
        $this->allowDelayPayment = $allowDelayPayment;
    
        return $this;
    }

    /**
     * Get allowDelayPayment
     *
     * @return boolean 
     */
    public function getAllowDelayPayment()
    {
        return $this->allowDelayPayment;
    }

    /**
     * Set requiredDivision
     *
     * @param boolean $requiredDivision
     * @return User
     */
    public function setRequiredDivision($requiredDivision)
    {
        $this->requiredDivision = $requiredDivision;
    
        return $this;
    }

    /**
     * Get requiredDivision
     *
     * @return boolean 
     */
    public function getRequiredDivision()
    {
        return $this->requiredDivision;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return User
     */
    public function setCreatedAt(\DateTime $createdAt)
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
     * Set group
     *
     * @param string $group
     * @return User
     */
    public function setGroup($group)
    {
        $this->group = $group;
    
        return $this;
    }

    /**
     * Get group
     *
     * @return string 
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set regeneratePassword
     *
     * @param boolean $regeneratePassword
     * @return User
     */
    public function setRegeneratePassword($regeneratePassword)
    {
        $this->regeneratePassword = $regeneratePassword;
    
        return $this;
    }

    /**
     * Get regeneratePassword
     *
     * @return boolean 
     */
    public function getRegeneratePassword()
    {
        return $this->regeneratePassword;
    }
}