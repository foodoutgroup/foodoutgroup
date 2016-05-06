<?php

namespace Food\ReportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Rfm
 *
 * @ORM\Table(name="report_rfm")
 * @ORM\Entity
 */
class Rfm
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
     * @var integer
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $email;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $phone;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $firstname;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $lastname;

    /**
     * @var boolean
     * @ORM\Column(name="is_business_client", type="boolean")
     */
    private $isBusinessClient;

    /**
     * @var string
     * @ORM\Column(name="company_name", type="string")
     */
    private $companyName;

    /**
     * @var datetime
     * @ORM\Column(name="first_order_date", type="datetime")
     */
    private $firstOrderDate;

    /**
     * @var datetime
     * @ORM\Column(name="last_order_date", type="datetime")
     */
    private $lastOrderDate;

    /**
     * @var integer
     * @ORM\Column(name="recency_score", type="integer")
     */
    private $recencyScore;

    /**
     * @var integer
     * @ORM\Column(name="frequency_score", type="integer")
     */
    private $frequencyScore;

    /**
     * @var integer
     * @ORM\Column(name="monetary_score", type="integer")
     */
    private $monetaryScore;

    /**
     * @var integer
     * @ORM\Column(name="total_rfm_score", type="integer")
     */
    private $totalRfmScore;


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
     * Set userId
     *
     * @param integer $userId
     * @return Rfm
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    
        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Rfm
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
     * Set phone
     *
     * @param string $phone
     * @return Rfm
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
     * Set firstname
     *
     * @param string $firstname
     * @return Rfm
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
     * @return Rfm
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
     * Set firstOrderDate
     *
     * @param \DateTime $firstOrderDate
     * @return Rfm
     */
    public function setFirstOrderDate($firstOrderDate)
    {
        $this->firstOrderDate = $firstOrderDate;
    
        return $this;
    }

    /**
     * Get firstOrderDate
     *
     * @return \DateTime 
     */
    public function getFirstOrderDate()
    {
        return $this->firstOrderDate;
    }

    /**
     * Set lastOrderDate
     *
     * @param \DateTime $lastOrderDate
     * @return Rfm
     */
    public function setLastOrderDate($lastOrderDate)
    {
        $this->lastOrderDate = $lastOrderDate;
    
        return $this;
    }

    /**
     * Get lastOrderDate
     *
     * @return \DateTime 
     */
    public function getLastOrderDate()
    {
        return $this->lastOrderDate;
    }

    /**
     * Set recencyScore
     *
     * @param integer $recencyScore
     * @return Rfm
     */
    public function setRecencyScore($recencyScore)
    {
        $this->recencyScore = $recencyScore;
    
        return $this;
    }

    /**
     * Get recencyScore
     *
     * @return integer 
     */
    public function getRecencyScore()
    {
        return $this->recencyScore;
    }

    /**
     * Set frequencyScore
     *
     * @param integer $frequencyScore
     * @return Rfm
     */
    public function setFrequencyScore($frequencyScore)
    {
        $this->frequencyScore = $frequencyScore;
    
        return $this;
    }

    /**
     * Get frequencyScore
     *
     * @return integer 
     */
    public function getFrequencyScore()
    {
        return $this->frequencyScore;
    }

    /**
     * Set monetaryScore
     *
     * @param integer $monetaryScore
     * @return Rfm
     */
    public function setMonetaryScore($monetaryScore)
    {
        $this->monetaryScore = $monetaryScore;
    
        return $this;
    }

    /**
     * Get monetaryScore
     *
     * @return integer 
     */
    public function getMonetaryScore()
    {
        return $this->monetaryScore;
    }

    /**
     * Set totalRfmScore
     *
     * @param integer $totalRfmScore
     * @return Rfm
     */
    public function setTotalRfmScore($totalRfmScore)
    {
        $this->totalRfmScore = $totalRfmScore;
    
        return $this;
    }

    /**
     * Get totalRfmScore
     *
     * @return integer 
     */
    public function getTotalRfmScore()
    {
        return $this->totalRfmScore;
    }

    /**
     * Set isBusinessClient
     *
     * @param boolean $isBusinessClient
     * @return Rfm
     */
    public function setIsBusinessClient($isBusinessClient)
    {
        $this->isBusinessClient = $isBusinessClient;
    
        return $this;
    }

    /**
     * Get isBusinessClient
     *
     * @return boolean 
     */
    public function getIsBusinessClient()
    {
        return $this->isBusinessClient;
    }

    /**
     * Set companyName
     *
     * @param string $companyName
     * @return Rfm
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
}