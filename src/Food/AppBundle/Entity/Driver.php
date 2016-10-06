<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Food\AppBundle\Entity\Driver
 *
 * @ORM\Table(name="drivers", indexes={@ORM\Index(name="active_idx", columns={"active"}), @ORM\Index(name="city_idx", columns={"city"})})
 * @ORM\Entity(repositoryClass="Food\AppBundle\Entity\DriverRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class Driver
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="type", type="string", length=64)
     */
    private $type = 'local';

    /**
     * @var String
     *
     * @ORM\Column(name="provider", type="string", length=64, nullable=true)
     */
    private $provider = null;

    /**
     * @var string
     * @ORM\Column(name="extId", type="string", length=255, nullable=true)
     */
    private $extId = null;

    /**
     * @var string
     * @ORM\Column(name="token", type="string", length=255, nullable=true)
     */
    private $token = null;

    /**
     * @var string
     * @ORM\Column(name="phone", type="string", length=16)
     */
    private $phone;

    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=128)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="city", type="string", length=64)
     */
    private $city;

    /**
     * @var bool
     * @ORM\Column(name="active", type="boolean")
     */
    private $active = true;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="edited_at", type="datetime", nullable=true)
     */
    private $editedAt;

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
     * @return string
     */
    public function __toString()
    {
        if ($this->getId()) {
            return $this->getName().'-'.$this->getCity().'-'.$this->getId();
        }

        return '';
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
     * Set type
     *
     * @param string $type
     * @return Driver
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set provider
     *
     * @param string $provider
     * @return Driver
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Get provider
     *
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Set extId
     *
     * @param string $extId
     * @return Driver
     */
    public function setExtId($extId)
    {
        $this->extId = $extId;

        return $this;
    }

    /**
     * Get extId
     *
     * @return string
     */
    public function getExtId()
    {
        return $this->extId;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return Driver
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
     * Set name
     *
     * @param string $name
     * @return Driver
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
     * Set city
     *
     * @param string $city
     * @return Driver
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

    /**
     * Set active
     *
     * @param boolean $active
     * @return Driver
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
     * @param \DateTime|null $createdAt
     * @return Driver
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
     * @return Driver
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
     * @param \DateTime $deletedAt
     * @return Driver
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
     * Set createdBy
     *
     * @param \Food\UserBundle\Entity\User $createdBy
     * @return Driver
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
     * @return Driver
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
     * @return Driver
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
     * @return string
     */
    public function getContact()
    {
        if (!$this->getId()) {
            return '';
        }
        $driverContactData = $this->getName();
        $phone = $this->getPhone();
        $company = $this->getProvider();

        if (!empty($phone)) {
            $driverContactData .= ', '.$phone;
        }

        if (!empty($company)) {
            $driverContactData .= ', '.$company;
        }

        return $driverContactData;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return Driver
     */
    public function setToken($token)
    {
        $this->token = $token;
    
        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }
}