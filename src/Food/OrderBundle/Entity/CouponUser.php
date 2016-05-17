<?php

namespace Food\OrderBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="coupon_users")
 * @ORM\Entity
 */
class CouponUser
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
     * @var \DateTime
     *
     * @ORM\Column(name="used_at", type="datetime")
     */
    private $usedAt;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user", referencedColumnName="id")
     */
    private $user;

    /**
     * @var Coupon
     *
     * @ORM\ManyToOne(targetEntity="\Food\OrderBundle\Entity\Coupon", inversedBy="couponUsers")
     * @ORM\JoinColumn(name="coupon", referencedColumnName="id")
     **/
    private $coupon;

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->getId()) {
            return $this->getId().'-'.$this->getCoupon()->getCode().'-'.$this->getUser()->getId();
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
     * Set usedAt
     *
     * @param \DateTime $usedAt
     * @return CouponUser
     */
    public function setUsedAt(\DateTime $usedAt)
    {
        $this->usedAt = $usedAt;
    
        return $this;
    }

    /**
     * Get usedAt
     *
     * @return \DateTime 
     */
    public function getUsedAt()
    {
        return $this->usedAt;
    }

    /**
     * Set user
     *
     * @param \Food\UserBundle\Entity\User $user
     * @return CouponUser
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
     * Set coupon
     *
     * @param \Food\OrderBundle\Entity\Coupon $coupon
     * @return CouponUser
     */
    public function setCoupon(\Food\OrderBundle\Entity\Coupon $coupon = null)
    {
        $this->coupon = $coupon;
    
        return $this;
    }

    /**
     * Get coupon
     *
     * @return \Food\OrderBundle\Entity\Coupon 
     */
    public function getCoupon()
    {
        return $this->coupon;
    }
}
