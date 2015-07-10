<?php

namespace Food\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user_division_code")
 * @ORM\Entity
 */
class UserDivisionCode
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
     * @ORM\Column(name="code", type="string", length=128)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="division", type="string", length=256)
     */
    private $division;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="divisionCodes")
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

    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }

        return $this->getCode();
    }


    /**
     * Set code
     *
     * @param string $code
     * @return UserDivisionCode
     */
    public function setCode($code)
    {
        $this->code = $code;
    
        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set user
     *
     * @param \Food\UserBundle\Entity\User $user
     * @return UserDivisionCode
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

    /**
     * Set division
     *
     * @param string $division
     * @return UserDivisionCode
     */
    public function setDivision($division)
    {
        $this->division = $division;
    
        return $this;
    }

    /**
     * Get division
     *
     * @return string 
     */
    public function getDivision()
    {
        return $this->division;
    }
}