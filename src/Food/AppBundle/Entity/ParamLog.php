<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="params_log")
 * @ORM\Entity
 */
class ParamLog
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
     * @ORM\ManyToOne(targetEntity="Param", inversedBy="paramLog")
     * @ORM\JoinColumn(name="param_id", referencedColumnName="id")
     */
    private $param;

    /**
     * @var \DateTime
     * @ORM\Column(name="event_date", type="datetime")
     */
    private $event_date;

    /**
     * @var string
     * @ORM\Column(name="old_value", type="string", length=50)
     */
    private $oldValue;

    /**
     * @var string
     * @ORM\Column(name="new_value", type="string", length=50)
     */
    private $newValue;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;

    public function __construct()
    {
        $this->event_date = new \DateTime("now");
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
     * Set event_date
     *
     * @param \DateTime $eventDate
     * @return ParamLog
     */
    public function setEventDate($eventDate)
    {
        $this->event_date = $eventDate;
    
        return $this;
    }

    /**
     * Get event_date
     *
     * @return \DateTime 
     */
    public function getEventDate()
    {
        return $this->event_date;
    }

    /**
     * Set oldValue
     *
     * @param string $oldValue
     * @return ParamLog
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
     * @return ParamLog
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
     * Set param
     *
     * @param \Food\AppBundle\Entity\Param $param
     * @return ParamLog
     */
    public function setParam(\Food\AppBundle\Entity\Param $param = null)
    {
        $this->param = $param;
    
        return $this;
    }

    /**
     * Get param
     *
     * @return \Food\AppBundle\Entity\Param 
     */
    public function getParam()
    {
        return $this->param;
    }

    /**
     * Set user
     *
     * @param \Food\UserBundle\Entity\User $user
     * @return ParamLog
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