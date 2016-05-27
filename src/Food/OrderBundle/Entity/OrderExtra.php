<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderAccData
 *
 * @ORM\Table(name="order_extra")
 * @ORM\Entity
 */
class OrderExtra
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
     * @ORM\OneToOne(targetEntity="Order", inversedBy="orderExtra")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     **/
    private $order;

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
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     */
    private $phone = null;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=75, nullable=true)
     */
    private $email = null;

    /**
     * @var string
     *
     * @ORM\Column(name="cancel_reason", type="string", length=255, nullable=true)
     */
    private $cancelReason = null;

    /**
     * @var string
     *
     * @ORM\Column(name="cancel_reason_comment", type="text", nullable=true)
     */
    private $cancelReasonComment = null;

    /**
     * @var string
     *
     * @ORM\Column(name="change_reason", type="text", nullable=true)
     */
    private $changeReason = null;

    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }

        return $this->getId().'-#'.$this->getOrder()->getId();
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
     * Set order
     *
     * @param \Food\OrderBundle\Entity\Order $order
     * @return OrderExtra
     */
    public function setOrder(\Food\OrderBundle\Entity\Order $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return \Food\OrderBundle\Entity\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return OrderExtra
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
     * @return OrderExtra
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
     * Set phone
     *
     * @param string $phone
     * @return OrderExtra
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
     * Set email
     *
     * @param string $email
     * @return OrderExtra
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

    /**
     * Set cancelReason
     *
     * @param string $cancelReason
     * @return OrderExtra
     */
    public function setCancelReason($cancelReason)
    {
        $this->cancelReason = $cancelReason;
    
        return $this;
    }

    /**
     * Get cancelReason
     *
     * @return string 
     */
    public function getCancelReason()
    {
        return $this->cancelReason;
    }

    /**
     * Set cancelReasonComment
     *
     * @param string $cancelReasonComment
     * @return OrderExtra
     */
    public function setCancelReasonComment($cancelReasonComment)
    {
        $this->cancelReasonComment = $cancelReasonComment;
    
        return $this;
    }

    /**
     * Get cancelReasonComment
     *
     * @return string 
     */
    public function getCancelReasonComment()
    {
        return $this->cancelReasonComment;
    }

    /**
     * Set changeReason
     *
     * @param string $changeReason
     * @return OrderExtra
     */
    public function setChangeReason($changeReason)
    {
        $this->changeReason = $changeReason;
    
        return $this;
    }

    /**
     * Get changeReason
     *
     * @return string 
     */
    public function getChangeReason()
    {
        return $this->changeReason;
    }
}