<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderAccData
 *
 * @ORM\Table(name="order_acc_data", indexes={@ORM\Index(name="order_id_idx", columns={"order_id"})})
 * @ORM\Entity
 */
class OrderAccData
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
     *
     * @ORM\Column(name="order_id", type="integer")
     */
    private $orderId;

    /**
     * @var string
     *
     * @ORM\Column(name="date", type="string", length=255)
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="time", type="string", length=255)
     */
    private $time;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_date", type="string", length=255)
     */
    private $deliveryDate;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_time", type="string", length=255)
     */
    private $deliveryTime;

    /**
     * @var string
     *
     * @ORM\Column(name="staff", type="string", length=255)
     */
    private $staff;

    /**
     * @var string
     *
     * @ORM\Column(name="chain", type="string", length=255)
     */
    private $chain;

    /**
     * @var string
     *
     * @ORM\Column(name="restaurant", type="string", length=255)
     */
    private $restaurant;

    /**
     * @var string
     *
     * @ORM\Column(name="restaurant_address", type="string", length=255)
     */
    private $restaurantAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="driver", type="string", length=255)
     */
    private $driver;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_type", type="string", length=255)
     */
    private $deliveryType;

    /**
     * @var string
     *
     * @ORM\Column(name="client_name", type="string", length=255)
     */
    private $clientName;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_delivered", type="smallint")
     */
    private $isDelivered;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_address", type="string", length=255)
     */
    private $deliveryAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=255)
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_type", type="string", length=255)
     */
    private $paymentType;

    /**
     * @var string
     *
     * @ORM\Column(name="food_amount", type="decimal", precision=8, scale=4)
     */
    private $foodAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="food_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $foodAmountEur;

    /**
     * @var string
     *
     * @ORM\Column(name="food_vat", type="decimal", precision=8, scale=4)
     */
    private $foodVat;

    /**
     * @var string
     *
     * @ORM\Column(name="drinks_amount", type="decimal", precision=8, scale=4)
     */
    private $drinksAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="drinks_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $drinksAmountEur;

    /**
     * @var string
     *
     * @ORM\Column(name="drinks_vat", type="decimal", precision=8, scale=4)
     */
    private $drinksVat;

    /**
     * @var string
     *
     * @ORM\Column(name="alcohol_amount", type="decimal", precision=8, scale=4)
     */
    private $alcoholAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="alcohol_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $alcoholAmountEur;

    /**
     * @var string
     *
     * @ORM\Column(name="alcohol_vat", type="decimal", precision=8, scale=4)
     */
    private $alcoholVat;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_amount", type="decimal", precision=8, scale=4)
     */
    private $deliveryAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $deliveryAmountEur;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_vat", type="decimal", precision=8, scale=4)
     */
    private $deliveryVat;

    /**
     * @var string
     *
     * @ORM\Column(name="gift_card_amount", type="decimal", precision=8, scale=4)
     */
    private $giftCardAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="gift_card_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $giftCardAmountEur;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_type", type="string", length=255)
     */
    private $discountType;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_amount", type="decimal", precision=8, scale=4)
     */
    private $discountAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $discountAmountEur;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_percent", type="decimal", precision=8, scale=4)
     */
    private $discountPercent;

    /**
     * @var string
     *
     * @ORM\Column(name="total_amount", type="decimal", precision=8, scale=4)
     */
    private $totalAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="total_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $totalAmountEur;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_synced", type="smallint")
     */
    private $isSynced;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sync_timestamp", type="datetime")
     */
    private $syncTimestamp;

    /**
     * @ORM\Version @ORM\Column(type="integer")
     */
    private $version;

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
     * Set orderId
     *
     * @param integer $orderId
     * @return OrderAccData
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    
        return $this;
    }

    /**
     * Get orderId
     *
     * @return integer 
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set date
     *
     * @param string $date
     * @return OrderAccData
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return string 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set time
     *
     * @param string $time
     * @return OrderAccData
     */
    public function setTime($time)
    {
        $this->time = $time;
    
        return $this;
    }

    /**
     * Get time
     *
     * @return string 
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set deliveryDate
     *
     * @param string $deliveryDate
     * @return OrderAccData
     */
    public function setDeliveryDate($deliveryDate)
    {
        $this->deliveryDate = $deliveryDate;
    
        return $this;
    }

    /**
     * Get deliveryDate
     *
     * @return string 
     */
    public function getDeliveryDate()
    {
        return $this->deliveryDate;
    }

    /**
     * Set deliveryTime
     *
     * @param string $deliveryTime
     * @return OrderAccData
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->deliveryTime = $deliveryTime;
    
        return $this;
    }

    /**
     * Get deliveryTime
     *
     * @return string 
     */
    public function getDeliveryTime()
    {
        return $this->deliveryTime;
    }

    /**
     * Set staff
     *
     * @param string $staff
     * @return OrderAccData
     */
    public function setStaff($staff)
    {
        $this->staff = $staff;
    
        return $this;
    }

    /**
     * Get staff
     *
     * @return string 
     */
    public function getStaff()
    {
        return $this->staff;
    }

    /**
     * Set chain
     *
     * @param string $chain
     * @return OrderAccData
     */
    public function setChain($chain)
    {
        $this->chain = $chain;
    
        return $this;
    }

    /**
     * Get chain
     *
     * @return string 
     */
    public function getChain()
    {
        return $this->chain;
    }

    /**
     * Set restaurant
     *
     * @param string $restaurant
     * @return OrderAccData
     */
    public function setRestaurant($restaurant)
    {
        $this->restaurant = $restaurant;
    
        return $this;
    }

    /**
     * Get restaurant
     *
     * @return string 
     */
    public function getRestaurant()
    {
        return $this->restaurant;
    }

    /**
     * Set restaurantAddress
     *
     * @param string $restaurantAddress
     * @return OrderAccData
     */
    public function setRestaurantAddress($restaurantAddress)
    {
        $this->restaurantAddress = $restaurantAddress;
    
        return $this;
    }

    /**
     * Get restaurantAddress
     *
     * @return string 
     */
    public function getRestaurantAddress()
    {
        return $this->restaurantAddress;
    }

    /**
     * Set driver
     *
     * @param string $driver
     * @return OrderAccData
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
    
        return $this;
    }

    /**
     * Get driver
     *
     * @return string 
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Set deliveryType
     *
     * @param string $deliveryType
     * @return OrderAccData
     */
    public function setDeliveryType($deliveryType)
    {
        $this->deliveryType = $deliveryType;
    
        return $this;
    }

    /**
     * Get deliveryType
     *
     * @return string 
     */
    public function getDeliveryType()
    {
        return $this->deliveryType;
    }

    /**
     * Set clientName
     *
     * @param string $clientName
     * @return OrderAccData
     */
    public function setClientName($clientName)
    {
        $this->clientName = $clientName;
    
        return $this;
    }

    /**
     * Get clientName
     *
     * @return string 
     */
    public function getClientName()
    {
        return $this->clientName;
    }

    /**
     * Set isDelivered
     *
     * @param integer $isDelivered
     * @return OrderAccData
     */
    public function setIsDelivered($isDelivered)
    {
        $this->isDelivered = $isDelivered;
    
        return $this;
    }

    /**
     * Get isDelivered
     *
     * @return integer 
     */
    public function getIsDelivered()
    {
        return $this->isDelivered;
    }

    /**
     * Set deliveryAddress
     *
     * @param string $deliveryAddress
     * @return OrderAccData
     */
    public function setDeliveryAddress($deliveryAddress)
    {
        $this->deliveryAddress = $deliveryAddress;
    
        return $this;
    }

    /**
     * Get deliveryAddress
     *
     * @return string 
     */
    public function getDeliveryAddress()
    {
        return $this->deliveryAddress;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return OrderAccData
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
     * Set country
     *
     * @param string $country
     * @return OrderAccData
     */
    public function setCountry($country)
    {
        $this->country = $country;
    
        return $this;
    }

    /**
     * Get country
     *
     * @return string 
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set paymentType
     *
     * @param string $paymentType
     * @return OrderAccData
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;
    
        return $this;
    }

    /**
     * Get paymentType
     *
     * @return string 
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * Set foodAmount
     *
     * @param string $foodAmount
     * @return OrderAccData
     */
    public function setFoodAmount($foodAmount)
    {
        $this->foodAmount = $foodAmount;
    
        return $this;
    }

    /**
     * Get foodAmount
     *
     * @return string 
     */
    public function getFoodAmount()
    {
        return $this->foodAmount;
    }

    /**
     * Set foodAmountEur
     *
     * @param string $foodAmountEur
     * @return OrderAccData
     */
    public function setFoodAmountEur($foodAmountEur)
    {
        $this->foodAmountEur = $foodAmountEur;
    
        return $this;
    }

    /**
     * Get foodAmountEur
     *
     * @return string 
     */
    public function getFoodAmountEur()
    {
        return $this->foodAmountEur;
    }

    /**
     * Set foodVat
     *
     * @param string $foodVat
     * @return OrderAccData
     */
    public function setFoodVat($foodVat)
    {
        $this->foodVat = $foodVat;
    
        return $this;
    }

    /**
     * Get foodVat
     *
     * @return string 
     */
    public function getFoodVat()
    {
        return $this->foodVat;
    }

    /**
     * Set drinksAmount
     *
     * @param string $drinksAmount
     * @return OrderAccData
     */
    public function setDrinksAmount($drinksAmount)
    {
        $this->drinksAmount = $drinksAmount;
    
        return $this;
    }

    /**
     * Get drinksAmount
     *
     * @return string 
     */
    public function getDrinksAmount()
    {
        return $this->drinksAmount;
    }

    /**
     * Set drinksAmountEur
     *
     * @param string $drinksAmountEur
     * @return OrderAccData
     */
    public function setDrinksAmountEur($drinksAmountEur)
    {
        $this->drinksAmountEur = $drinksAmountEur;
    
        return $this;
    }

    /**
     * Get drinksAmountEur
     *
     * @return string 
     */
    public function getDrinksAmountEur()
    {
        return $this->drinksAmountEur;
    }

    /**
     * Set drinksVat
     *
     * @param string $drinksVat
     * @return OrderAccData
     */
    public function setDrinksVat($drinksVat)
    {
        $this->drinksVat = $drinksVat;
    
        return $this;
    }

    /**
     * Get drinksVat
     *
     * @return string 
     */
    public function getDrinksVat()
    {
        return $this->drinksVat;
    }

    /**
     * Set alcoholAmount
     *
     * @param string $alcoholAmount
     * @return OrderAccData
     */
    public function setAlcoholAmount($alcoholAmount)
    {
        $this->alcoholAmount = $alcoholAmount;
    
        return $this;
    }

    /**
     * Get alcoholAmount
     *
     * @return string 
     */
    public function getAlcoholAmount()
    {
        return $this->alcoholAmount;
    }

    /**
     * Set alcoholAmountEur
     *
     * @param string $alcoholAmountEur
     * @return OrderAccData
     */
    public function setAlcoholAmountEur($alcoholAmountEur)
    {
        $this->alcoholAmountEur = $alcoholAmountEur;
    
        return $this;
    }

    /**
     * Get alcoholAmountEur
     *
     * @return string 
     */
    public function getAlcoholAmountEur()
    {
        return $this->alcoholAmountEur;
    }

    /**
     * Set alcoholVat
     *
     * @param string $alcoholVat
     * @return OrderAccData
     */
    public function setAlcoholVat($alcoholVat)
    {
        $this->alcoholVat = $alcoholVat;
    
        return $this;
    }

    /**
     * Get alcoholVat
     *
     * @return string 
     */
    public function getAlcoholVat()
    {
        return $this->alcoholVat;
    }

    /**
     * Set deliveryAmount
     *
     * @param string $deliveryAmount
     * @return OrderAccData
     */
    public function setDeliveryAmount($deliveryAmount)
    {
        $this->deliveryAmount = $deliveryAmount;
    
        return $this;
    }

    /**
     * Get deliveryAmount
     *
     * @return string 
     */
    public function getDeliveryAmount()
    {
        return $this->deliveryAmount;
    }

    /**
     * Set deliveryAmountEur
     *
     * @param string $deliveryAmountEur
     * @return OrderAccData
     */
    public function setDeliveryAmountEur($deliveryAmountEur)
    {
        $this->deliveryAmountEur = $deliveryAmountEur;
    
        return $this;
    }

    /**
     * Get deliveryAmountEur
     *
     * @return string 
     */
    public function getDeliveryAmountEur()
    {
        return $this->deliveryAmountEur;
    }

    /**
     * Set deliveryVat
     *
     * @param string $deliveryVat
     * @return OrderAccData
     */
    public function setDeliveryVat($deliveryVat)
    {
        $this->deliveryVat = $deliveryVat;
    
        return $this;
    }

    /**
     * Get deliveryVat
     *
     * @return string 
     */
    public function getDeliveryVat()
    {
        return $this->deliveryVat;
    }

    /**
     * Set giftCardAmount
     *
     * @param string $giftCardAmount
     * @return OrderAccData
     */
    public function setGiftCardAmount($giftCardAmount)
    {
        $this->giftCardAmount = $giftCardAmount;
    
        return $this;
    }

    /**
     * Get giftCardAmount
     *
     * @return string 
     */
    public function getGiftCardAmount()
    {
        return $this->giftCardAmount;
    }

    /**
     * Set giftCardAmountEur
     *
     * @param string $giftCardAmountEur
     * @return OrderAccData
     */
    public function setGiftCardAmountEur($giftCardAmountEur)
    {
        $this->giftCardAmountEur = $giftCardAmountEur;
    
        return $this;
    }

    /**
     * Get giftCardAmountEur
     *
     * @return string 
     */
    public function getGiftCardAmountEur()
    {
        return $this->giftCardAmountEur;
    }

    /**
     * Set discountType
     *
     * @param string $discountType
     * @return OrderAccData
     */
    public function setDiscountType($discountType)
    {
        $this->discountType = $discountType;
    
        return $this;
    }

    /**
     * Get discountType
     *
     * @return string 
     */
    public function getDiscountType()
    {
        return $this->discountType;
    }

    /**
     * Set discountAmount
     *
     * @param string $discountAmount
     * @return OrderAccData
     */
    public function setDiscountAmount($discountAmount)
    {
        $this->discountAmount = $discountAmount;
    
        return $this;
    }

    /**
     * Get discountAmount
     *
     * @return string 
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * Set discountAmountEur
     *
     * @param string $discountAmountEur
     * @return OrderAccData
     */
    public function setDiscountAmountEur($discountAmountEur)
    {
        $this->discountAmountEur = $discountAmountEur;
    
        return $this;
    }

    /**
     * Get discountAmountEur
     *
     * @return string 
     */
    public function getDiscountAmountEur()
    {
        return $this->discountAmountEur;
    }

    /**
     * Set discountPercent
     *
     * @param string $discountPercent
     * @return OrderAccData
     */
    public function setDiscountPercent($discountPercent)
    {
        $this->discountPercent = $discountPercent;
    
        return $this;
    }

    /**
     * Get discountPercent
     *
     * @return string 
     */
    public function getDiscountPercent()
    {
        return $this->discountPercent;
    }

    /**
     * Set totalAmount
     *
     * @param string $totalAmount
     * @return OrderAccData
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
    
        return $this;
    }

    /**
     * Get totalAmount
     *
     * @return string 
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Set totalAmountEur
     *
     * @param string $totalAmountEur
     * @return OrderAccData
     */
    public function setTotalAmountEur($totalAmountEur)
    {
        $this->totalAmountEur = $totalAmountEur;
    
        return $this;
    }

    /**
     * Get totalAmountEur
     *
     * @return string 
     */
    public function getTotalAmountEur()
    {
        return $this->totalAmountEur;
    }

    /**
     * Set isSynced
     *
     * @param integer $isSynced
     * @return OrderAccData
     */
    public function setIsSynced($isSynced)
    {
        $this->isSynced = $isSynced;
    
        return $this;
    }

    /**
     * Get isSynced
     *
     * @return integer 
     */
    public function getIsSynced()
    {
        return $this->isSynced;
    }

    /**
     * Set syncTimestamp
     *
     * @param \DateTime $syncTimestamp
     * @return OrderAccData
     */
    public function setSyncTimestamp($syncTimestamp)
    {
        $this->syncTimestamp = $syncTimestamp;
    
        return $this;
    }

    /**
     * Get syncTimestamp
     *
     * @return \DateTime 
     */
    public function getSyncTimestamp()
    {
        return $this->syncTimestamp;
    }

    /**
     * Set version
     *
     * @param integer $version
     * @return OrderAccData
     */
    public function setVersion($version)
    {
        $this->version = $version;
    
        return $this;
    }

    /**
     * Get version
     *
     * @return integer 
     */
    public function getVersion()
    {
        return $this->version;
    }
}