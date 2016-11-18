<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Food\AppBundle\Entity\CityLog
 *
 * @ORM\Table(name="cities_log")
 * @ORM\Entity
 */
class CityLog
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
     * @ORM\ManyToOne(targetEntity="City", inversedBy="cityLog")
     * @ORM\JoinColumn(name="city_id", referencedColumnName="id")
     */
    private $city;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="event_date", type="datetime")
     */
    private $eventDate;

    /**
     * @var string
     *
     * @ORM\Column(name="old_value", type="text")
     */
    private $oldValue;

    /**
     * @var string
     *
     * @ORM\Column(name="new_value", type="text")
     */
    private $newValue;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;


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
     * Set city
     *
     * @param string $city
     * @return CityLog
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
     * Set eventDate
     *
     * @param \DateTime $eventDate
     * @return CityLog
     */
    public function setEventDate($eventDate)
    {
        $this->eventDate = $eventDate;
    
        return $this;
    }

    /**
     * Get eventDate
     *
     * @return \DateTime 
     */
    public function getEventDate()
    {
        return $this->eventDate;
    }

    /**
     * Set oldValue
     *
     * @param string $oldValue
     * @return CityLog
     */
    public function setOldValue($oldValue)
    {
        $this->oldValue = $oldValue;
    
        return $this;
    }

    /**
     * Get oldValue
     *
     * @return string 
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * Set newValue
     *
     * @param string $newValue
     * @return CityLog
     */
    public function setNewValue($newValue)
    {
        $this->newValue = $newValue;
    
        return $this;
    }

    /**
     * Get newValue
     *
     * @return string 
     */
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * Set user
     *
     * @param \Food\UserBundle\Entity\User $user
     * @return CityLog
     */
    public function setUser(\Food\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Food\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
