<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Food\DishesBundle\Entity\Dish;
use Food\DishesBundle\Entity\DishUnit;

/**
 * @ORM\Table(name="order_details")
 * @ORM\Entity
 */
class OrderDetails
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
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="details")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     */
    private $order_id;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Dish")
     * @ORM\JoinColumn(name="dish_id", referencedColumnName="id")
     */
    private $dish_id;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\DishUnit")
     * @ORM\JoinColumn(name="dish_unit_id", referencedColumnName="id")
     */
    private $dish_unit_id;

    /**
     * @ORM\Column(name="dish_name", type="string", length=255)
     */
    private $dish_name;

    /**
     * @var string
     *
     * @ORM\Column(name="name_to_nav", type="string", length=32, nullable=true)
     */
    private $nameToNav;

    /**
     * @ORM\Column(name="dish_unit_name", type="string", length=255)
     */
    private $dish_unit_name;

    /**
     * @ORM\Column(name="dish_size_code", type="string", length=255, nullable=true)
     */
    private $dish_size_code;

    /**
     * @ORM\Column(name="dish_size_mm_code", type="string", length=255, nullable=true)
     */
    private $dish_size_mm_code;

    /**
     * @ORM\Column(name="quantity", type="integer", length=3)
     */
    private $quantity;

    /**
     * @ORM\Column(name="price", type="decimal", precision=8, scale=2)
     */
    private $price;

    /**
     * @ORM\Column(name="orig_price", type="decimal", precision=8, scale=2)
     */
    private $origPrice;

    /**
     * @ORM\Column(name="price_before_discount", type="decimal", precision=8, scale=2, nullable=true)
     */
    private $priceBeforeDiscount;

    /**
     * @var bool
     * @ORM\Column(name="percent_discount", type="integer", nullable=true)
     */
    private $percentDiscount = 0;

    /**
     * @var OrderDetailsOptions[]
     *
     * @ORM\OneToMany(targetEntity="OrderDetailsOptions", mappedBy="order_detail")
     */
    private $options;

    /**
     * @var string
     * @ORM\Column(name="is_free", type="boolean", nullable=true)
     */
    private $isFree = null;

    /**
     * Set dish_name
     *
     * @param string $dishName
     * @return OrderDetails
     */
    public function setDishName($dishName)
    {
        $this->dish_name = $dishName;

        return $this;
    }

    /**
     * Get dish_name
     *
     * @return string
     */
    public function getDishName()
    {
        return $this->dish_name;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return OrderDetails
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set price
     *
     * @param float $price
     * @return OrderDetails
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set order_id
     *
     * @param \Food\OrderBundle\Entity\Order $orderId
     * @return OrderDetails
     */
    public function setOrderId(\Food\OrderBundle\Entity\Order $orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get order_id
     *
     * @return \Food\OrderBundle\Entity\Order
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set dish_id
     *
     * @param Dish $dishId
     * @return OrderDetails
     */
    public function setDishId($dishId)
    {
        $this->dish_id = $dishId;

        return $this;
    }

    /**
     * Get dish_id
     *
     * @return Dish
     */
    public function getDishId()
    {
        return $this->dish_id;
    }

    /**
     * Set dish_unit_id
     *
     * @param DishUnit $dishUnitId
     * @return OrderDetails
     */
    public function setDishUnitId(DishUnit $dishUnitId)
    {
        $this->dish_unit_id = $dishUnitId;

        return $this;
    }

    /**
     * Get dish_unit_id
     *
     * @return DishUnit
     */
    public function getDishUnitId()
    {
        return $this->dish_unit_id;
    }

    /**
     * Set dish_unit_name
     *
     * @param string $dishUnitName
     * @return OrderDetails
     */
    public function setDishUnitName($dishUnitName)
    {
        $this->dish_unit_name = $dishUnitName;

        return $this;
    }

    /**
     * Get dish_unit_name
     *
     * @return string
     */
    public function getDishUnitName()
    {
        return $this->dish_unit_name;
    }

    /**
     * Set dish_size_code
     *
     * @param string $dishSizeCode
     * @return OrderDetails
     */
    public function setDishSizeCode($dishSizeCode)
    {
        $this->dish_size_code = $dishSizeCode;

        return $this;
    }

    /**
     * Get dish_size_code
     *
     * @return string
     */
    public function getDishSizeCode()
    {
        return $this->dish_size_code;
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
     * Constructor
     */
    public function __construct()
    {
        $this->options = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add options
     *
     * @param \Food\OrderBundle\Entity\OrderDetailsOptions $options
     * @return OrderDetails
     */
    public function addOption(\Food\OrderBundle\Entity\OrderDetailsOptions $options)
    {
        $this->options[] = $options;

        return $this;
    }

    /**
     * Remove options
     *
     * @param \Food\OrderBundle\Entity\OrderDetailsOptions $options
     */
    public function removeOption(\Food\OrderBundle\Entity\OrderDetailsOptions $options)
    {
        $this->options->removeElement($options);
    }

    /**
     * Get options
     *
     * @return \Food\OrderBundle\Entity\OrderDetailsOptions[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set origPrice
     *
     * @param string $origPrice
     * @return OrderDetails
     */
    public function setOrigPrice($origPrice)
    {
        $this->origPrice = $origPrice;

        return $this;
    }

    /**
     * Get origPrice
     *
     * @return float
     */
    public function getOrigPrice()
    {
        return $this->origPrice;
    }

    /**
     * Set percentDiscount
     *
     * @param integer $percentDiscount
     * @return OrderDetails
     */
    public function setPercentDiscount($percentDiscount)
    {
        $this->percentDiscount = $percentDiscount;

        return $this;
    }

    /**
     * Get percentDiscount
     *
     * @return integer
     */
    public function getPercentDiscount()
    {
        return $this->percentDiscount;
    }

    /**
     * Set isFree
     *
     * @param boolean $isFree
     * @return OrderDetails
     */
    public function setIsFree($isFree)
    {
        $this->isFree = $isFree;

        return $this;
    }

    /**
     * Get isFree
     *
     * @return boolean
     */
    public function getIsFree()
    {
        return $this->isFree;
    }

    /**
     * @return mixed
     */
    public function getPriceBeforeDiscount()
    {
        return $this->priceBeforeDiscount;
    }

    /**
     * @param mixed $priceBeforeDiscount
     */
    public function setPriceBeforeDiscount($priceBeforeDiscount)
    {
        $this->priceBeforeDiscount = $priceBeforeDiscount;
        return $this;
    }



    /**
     * Set nameToNav
     *
     * @param string $nameToNav
     * @return OrderDetails
     */
    public function setNameToNav($nameToNav)
    {
        $this->nameToNav = $nameToNav;

        return $this;
    }

    /**
     * Get nameToNav
     *
     * @return string
     */
    public function getNameToNav()
    {
        return $this->nameToNav;
    }

    /**
     * Set dish_size_mm_code
     *
     * @param string $dishSizeMmCode
     * @return OrderDetails
     */
    public function setDishSizeMmCode($dishSizeMmCode)
    {
        $this->dish_size_mm_code = $dishSizeMmCode;
    
        return $this;
    }

    /**
     * Get dish_size_mm_code
     *
     * @return string 
     */
    public function getDishSizeMmCode()
    {
        return $this->dish_size_mm_code;
    }
}