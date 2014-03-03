<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Food\AppBundle\Entity\Subscribers
 *
 * @ORM\Table(name="subscribers")
 * @ORM\Entity
 */
class Subscribers
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
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var string $locale
     *
     * @ORM\Column(name="locale", type="string", length=4)
     */
    private $locale;

    /**
     * @var \DateTime $dateAdded
     *
     * @ORM\Column(name="date_added", type="datetime")
     */
    private $dateAdded;

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->getId()) {
            return ''.$this->getEmail();
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
     * Set email
     *
     * @param string $email
     * @return Subscribers
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return Subscribers
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    
        return $this;
    }

    /**
     * Get locale
     *
     * @return string 
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return Subscribers
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;
    
        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime 
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }
}
