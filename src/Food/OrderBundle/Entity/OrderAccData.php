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
    private $order_id;

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
    private $delivery_date;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_time", type="string", length=255)
     */
    private $delivery_time;

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
    private $restaurant_address;

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
    private $delivery_type;

    /**
     * @var string
     *
     * @ORM\Column(name="client_name", type="string", length=255)
     */
    private $client_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_delivered", type="smallint")
     */
    private $is_delivered;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_address", type="string", length=255)
     */
    private $delivery_address;

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
    private $payment_type;

    /**
     * @var string
     *
     * @ORM\Column(name="food_amount", type="decimal", precision=8, scale=4)
     */
    private $food_amount;

    /**
     * @var string
     *
     * @ORM\Column(name="food_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $food_amount_eur;

    /**
     * @var string
     *
     * @ORM\Column(name="food_vat", type="decimal", precision=8, scale=4)
     */
    private $food_vat;

    /**
     * @var string
     *
     * @ORM\Column(name="drinks_amount", type="decimal", precision=8, scale=4)
     */
    private $drinks_amount;

    /**
     * @var string
     *
     * @ORM\Column(name="drinks_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $drinks_amount_eur;

    /**
     * @var string
     *
     * @ORM\Column(name="drinks_vat", type="decimal", precision=8, scale=4)
     */
    private $drinks_vat;

    /**
     * @var string
     *
     * @ORM\Column(name="alcohol_amount", type="decimal", precision=8, scale=4)
     */
    private $alcohol_amount;

    /**
     * @var string
     *
     * @ORM\Column(name="alcohol_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $alcohol_amount_eur;

    /**
     * @var string
     *
     * @ORM\Column(name="alcohol_vat", type="decimal", precision=8, scale=4)
     */
    private $alcohol_vat;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_amount", type="decimal", precision=8, scale=4)
     */
    private $delivery_amount;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $delivery_amount_eur;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_vat", type="decimal", precision=8, scale=4)
     */
    private $delivery_vat;

    /**
     * @var string
     *
     * @ORM\Column(name="gift_card_amount", type="decimal", precision=8, scale=4)
     */
    private $gift_card_amount;

    /**
     * @var string
     *
     * @ORM\Column(name="gift_card_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $gift_card_amount_eur;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_type", type="string", length=255)
     */
    private $discount_type;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_amount", type="decimal", precision=8, scale=4)
     */
    private $discount_amount;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $discount_amount_eur;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_percent", type="decimal", precision=8, scale=4)
     */
    private $discount_percent;

    /**
     * @var string
     *
     * @ORM\Column(name="total_amount", type="decimal", precision=8, scale=4)
     */
    private $total_amount;

    /**
     * @var string
     *
     * @ORM\Column(name="total_amount_eur", type="decimal", precision=8, scale=4)
     */
    private $total_amount_eur;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_synced", type="smallint")
     */
    private $is_synced;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sync_timestamp", type="datetime", nullable=true)
     */
    private $sync_timestamp;

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
     * Set order_id
     *
     * @param integer $orderId
     * @return OrderAccData
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;
    
        return $this;
    }

    /**
     * Get order_id
     *
     * @return integer 
     */
    public function getOrderId()
    {
        return $this->order_id;
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
     * Set delivery_date
     *
     * @param string $deliveryDate
     * @return OrderAccData
     */
    public function setDeliveryDate($deliveryDate)
    {
        $this->delivery_date = $deliveryDate;
    
        return $this;
    }

    /**
     * Get delivery_date
     *
     * @return string 
     */
    public function getDeliveryDate()
    {
        return $this->delivery_date;
    }

    /**
     * Set delivery_time
     *
     * @param string $deliveryTime
     * @return OrderAccData
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->delivery_time = $deliveryTime;
    
        return $this;
    }

    /**
     * Get delivery_time
     *
     * @return string 
     */
    public function getDeliveryTime()
    {
        return $this->delivery_time;
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
     * Set restaurant_address
     *
     * @param string $restaurantAddress
     * @return OrderAccData
     */
    public function setRestaurantAddress($restaurantAddress)
    {
        $this->restaurant_address = $restaurantAddress;
    
        return $this;
    }

    /**
     * Get restaurant_address
     *
     * @return string 
     */
    public function getRestaurantAddress()
    {
        return $this->restaurant_address;
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
     * Set delivery_type
     *
     * @param string $deliveryType
     * @return OrderAccData
     */
    public function setDeliveryType($deliveryType)
    {
        $this->delivery_type = $deliveryType;
    
        return $this;
    }

    /**
     * Get delivery_type
     *
     * @return string 
     */
    public function getDeliveryType()
    {
        return $this->delivery_type;
    }

    /**
     * Set client_name
     *
     * @param string $clientName
     * @return OrderAccData
     */
    public function setClientName($clientName)
    {
        $this->client_name = $clientName;
    
        return $this;
    }

    /**
     * Get client_name
     *
     * @return string 
     */
    public function getClientName()
    {
        return $this->client_name;
    }

    /**
     * Set is_delivered
     *
     * @param integer $isDelivered
     * @return OrderAccData
     */
    public function setIsDelivered($isDelivered)
    {
        $this->is_delivered = $isDelivered;
    
        return $this;
    }

    /**
     * Get is_delivered
     *
     * @return integer 
     */
    public function getIsDelivered()
    {
        return $this->is_delivered;
    }

    /**
     * Set delivery_address
     *
     * @param string $deliveryAddress
     * @return OrderAccData
     */
    public function setDeliveryAddress($deliveryAddress)
    {
        $this->delivery_address = $deliveryAddress;
    
        return $this;
    }

    /**
     * Get delivery_address
     *
     * @return string 
     */
    public function getDeliveryAddress()
    {
        return $this->delivery_address;
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
     * Set payment_type
     *
     * @param string $paymentType
     * @return OrderAccData
     */
    public function setPaymentType($paymentType)
    {
        $this->payment_type = $paymentType;
    
        return $this;
    }

    /**
     * Get payment_type
     *
     * @return string 
     */
    public function getPaymentType()
    {
        return $this->payment_type;
    }

    /**
     * Set food_amount
     *
     * @param string $foodAmount
     * @return OrderAccData
     */
    public function setFoodAmount($foodAmount)
    {
        $this->food_amount = $foodAmount;
    
        return $this;
    }

    /**
     * Get food_amount
     *
     * @return string 
     */
    public function getFoodAmount()
    {
        return $this->food_amount;
    }

    /**
     * Set food_amount_eur
     *
     * @param string $foodAmountEur
     * @return OrderAccData
     */
    public function setFoodAmountEur($foodAmountEur)
    {
        $this->food_amount_eur = $foodAmountEur;
    
        return $this;
    }

    /**
     * Get food_amount_eur
     *
     * @return string 
     */
    public function getFoodAmountEur()
    {
        return $this->food_amount_eur;
    }

    /**
     * Set food_vat
     *
     * @param string $foodVat
     * @return OrderAccData
     */
    public function setFoodVat($foodVat)
    {
        $this->food_vat = $foodVat;
    
        return $this;
    }

    /**
     * Get food_vat
     *
     * @return string 
     */
    public function getFoodVat()
    {
        return $this->food_vat;
    }

    /**
     * Set drinks_amount
     *
     * @param string $drinksAmount
     * @return OrderAccData
     */
    public function setDrinksAmount($drinksAmount)
    {
        $this->drinks_amount = $drinksAmount;
    
        return $this;
    }

    /**
     * Get drinks_amount
     *
     * @return string 
     */
    public function getDrinksAmount()
    {
        return $this->drinks_amount;
    }

    /**
     * Set drinks_amount_eur
     *
     * @param string $drinksAmountEur
     * @return OrderAccData
     */
    public function setDrinksAmountEur($drinksAmountEur)
    {
        $this->drinks_amount_eur = $drinksAmountEur;
    
        return $this;
    }

    /**
     * Get drinks_amount_eur
     *
     * @return string 
     */
    public function getDrinksAmountEur()
    {
        return $this->drinks_amount_eur;
    }

    /**
     * Set drinks_vat
     *
     * @param string $drinksVat
     * @return OrderAccData
     */
    public function setDrinksVat($drinksVat)
    {
        $this->drinks_vat = $drinksVat;
    
        return $this;
    }

    /**
     * Get drinks_vat
     *
     * @return string 
     */
    public function getDrinksVat()
    {
        return $this->drinks_vat;
    }

    /**
     * Set alcohol_amount
     *
     * @param string $alcoholAmount
     * @return OrderAccData
     */
    public function setAlcoholAmount($alcoholAmount)
    {
        $this->alcohol_amount = $alcoholAmount;
    
        return $this;
    }

    /**
     * Get alcohol_amount
     *
     * @return string 
     */
    public function getAlcoholAmount()
    {
        return $this->alcohol_amount;
    }

    /**
     * Set alcohol_amount_eur
     *
     * @param string $alcoholAmountEur
     * @return OrderAccData
     */
    public function setAlcoholAmountEur($alcoholAmountEur)
    {
        $this->alcohol_amount_eur = $alcoholAmountEur;
    
        return $this;
    }

    /**
     * Get alcohol_amount_eur
     *
     * @return string 
     */
    public function getAlcoholAmountEur()
    {
        return $this->alcohol_amount_eur;
    }

    /**
     * Set alcohol_vat
     *
     * @param string $alcoholVat
     * @return OrderAccData
     */
    public function setAlcoholVat($alcoholVat)
    {
        $this->alcohol_vat = $alcoholVat;
    
        return $this;
    }

    /**
     * Get alcohol_vat
     *
     * @return string 
     */
    public function getAlcoholVat()
    {
        return $this->alcohol_vat;
    }

    /**
     * Set delivery_amount
     *
     * @param string $deliveryAmount
     * @return OrderAccData
     */
    public function setDeliveryAmount($deliveryAmount)
    {
        $this->delivery_amount = $deliveryAmount;
    
        return $this;
    }

    /**
     * Get delivery_amount
     *
     * @return string 
     */
    public function getDeliveryAmount()
    {
        return $this->delivery_amount;
    }

    /**
     * Set delivery_amount_eur
     *
     * @param string $deliveryAmountEur
     * @return OrderAccData
     */
    public function setDeliveryAmountEur($deliveryAmountEur)
    {
        $this->delivery_amount_eur = $deliveryAmountEur;
    
        return $this;
    }

    /**
     * Get delivery_amount_eur
     *
     * @return string 
     */
    public function getDeliveryAmountEur()
    {
        return $this->delivery_amount_eur;
    }

    /**
     * Set delivery_vat
     *
     * @param string $deliveryVat
     * @return OrderAccData
     */
    public function setDeliveryVat($deliveryVat)
    {
        $this->delivery_vat = $deliveryVat;
    
        return $this;
    }

    /**
     * Get delivery_vat
     *
     * @return string 
     */
    public function getDeliveryVat()
    {
        return $this->delivery_vat;
    }

    /**
     * Set gift_card_amount
     *
     * @param string $giftCardAmount
     * @return OrderAccData
     */
    public function setGiftCardAmount($giftCardAmount)
    {
        $this->gift_card_amount = $giftCardAmount;
    
        return $this;
    }

    /**
     * Get gift_card_amount
     *
     * @return string 
     */
    public function getGiftCardAmount()
    {
        return $this->gift_card_amount;
    }

    /**
     * Set gift_card_amount_eur
     *
     * @param string $giftCardAmountEur
     * @return OrderAccData
     */
    public function setGiftCardAmountEur($giftCardAmountEur)
    {
        $this->gift_card_amount_eur = $giftCardAmountEur;
    
        return $this;
    }

    /**
     * Get gift_card_amount_eur
     *
     * @return string 
     */
    public function getGiftCardAmountEur()
    {
        return $this->gift_card_amount_eur;
    }

    /**
     * Set discount_type
     *
     * @param string $discountType
     * @return OrderAccData
     */
    public function setDiscountType($discountType)
    {
        $this->discount_type = $discountType;
    
        return $this;
    }

    /**
     * Get discount_type
     *
     * @return string 
     */
    public function getDiscountType()
    {
        return $this->discount_type;
    }

    /**
     * Set discount_amount
     *
     * @param string $discountAmount
     * @return OrderAccData
     */
    public function setDiscountAmount($discountAmount)
    {
        $this->discount_amount = $discountAmount;
    
        return $this;
    }

    /**
     * Get discount_amount
     *
     * @return string 
     */
    public function getDiscountAmount()
    {
        return $this->discount_amount;
    }

    /**
     * Set discount_amount_eur
     *
     * @param string $discountAmountEur
     * @return OrderAccData
     */
    public function setDiscountAmountEur($discountAmountEur)
    {
        $this->discount_amount_eur = $discountAmountEur;
    
        return $this;
    }

    /**
     * Get discount_amount_eur
     *
     * @return string 
     */
    public function getDiscountAmountEur()
    {
        return $this->discount_amount_eur;
    }

    /**
     * Set discount_percent
     *
     * @param string $discountPercent
     * @return OrderAccData
     */
    public function setDiscountPercent($discountPercent)
    {
        $this->discount_percent = $discountPercent;
    
        return $this;
    }

    /**
     * Get discount_percent
     *
     * @return string 
     */
    public function getDiscountPercent()
    {
        return $this->discount_percent;
    }

    /**
     * Set total_amount
     *
     * @param string $totalAmount
     * @return OrderAccData
     */
    public function setTotalAmount($totalAmount)
    {
        $this->total_amount = $totalAmount;
    
        return $this;
    }

    /**
     * Get total_amount
     *
     * @return string 
     */
    public function getTotalAmount()
    {
        return $this->total_amount;
    }

    /**
     * Set total_amount_eur
     *
     * @param string $totalAmountEur
     * @return OrderAccData
     */
    public function setTotalAmountEur($totalAmountEur)
    {
        $this->total_amount_eur = $totalAmountEur;
    
        return $this;
    }

    /**
     * Get total_amount_eur
     *
     * @return string 
     */
    public function getTotalAmountEur()
    {
        return $this->total_amount_eur;
    }

    /**
     * Set is_synced
     *
     * @param integer $isSynced
     * @return OrderAccData
     */
    public function setIsSynced($isSynced)
    {
        $this->is_synced = $isSynced;
    
        return $this;
    }

    /**
     * Get is_synced
     *
     * @return integer 
     */
    public function getIsSynced()
    {
        return $this->is_synced;
    }

    /**
     * Set sync_timestamp
     *
     * @param \DateTime $syncTimestamp
     * @return OrderAccData
     */
    public function setSyncTimestamp($syncTimestamp)
    {
        $this->sync_timestamp = $syncTimestamp;
    
        return $this;
    }

    /**
     * Get sync_timestamp
     *
     * @return \DateTime 
     */
    public function getSyncTimestamp()
    {
        return $this->sync_timestamp;
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