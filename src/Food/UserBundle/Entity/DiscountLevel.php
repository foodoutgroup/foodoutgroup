<?php

namespace Food\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Food\UserBundle\Entity\DiscountLevel
 *
 * @ORM\Entity
 * @ORM\Table(name="discount_level")
 */
class DiscountLevel
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var float
     * @ORM\Column(name="range_start", type="decimal", precision=9, scale=2, nullable=true)
     */
    private $rangeStart;

    /**
     * @var float
     * @ORM\Column(name="range_end", type="decimal", precision=9, scale=2, nullable=true)
     */
    private $rangeEnd;

    /**
     * @var float
     * @ORM\Column(name="discount", type="decimal", precision=5, scale=2, nullable=true)
     */
    private $discount;

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
     * Set rangeStart
     *
     * @param string $rangeStart
     * @return DiscountLevel
     */
    public function setRangeStart($rangeStart)
    {
        $this->rangeStart = $rangeStart;
    
        return $this;
    }

    /**
     * Get rangeStart
     *
     * @return string 
     */
    public function getRangeStart()
    {
        return $this->rangeStart;
    }

    /**
     * Set rangeEnd
     *
     * @param string $rangeEnd
     * @return DiscountLevel
     */
    public function setRangeEnd($rangeEnd)
    {
        $this->rangeEnd = $rangeEnd;
    
        return $this;
    }

    /**
     * Get rangeEnd
     *
     * @return string 
     */
    public function getRangeEnd()
    {
        return $this->rangeEnd;
    }

    /**
     * Set discount
     *
     * @param string $discount
     * @return DiscountLevel
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
}