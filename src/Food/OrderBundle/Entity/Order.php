<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Food\AppBundle\Entity\Driver;

/**
 * @ORM\Table(name="orders", indexes={@ORM\Index(name="nav_delivery_order_idx", columns={"nav_delivery_order"}),@ORM\Index(name="order_status_idx", columns={"order_status"}),@ORM\Index(name="status_date_idx", columns={"order_status", "order_date"}),@ORM\Index(name="payment_status_idx", columns={"payment_status"}),@ORM\Index(name="place_point_city_idx", columns={"place_point_city"}),@ORM\Index(name="delivery_type_idx", columns={"delivery_type"}),@ORM\Index(name="has_user_completed_orders_idx", columns={"place_id","user_id","dispatcher_id","order_status"})})
 * @ORM\Entity(repositoryClass="Food\OrderBundle\Entity\OrderRepository")
 */
class Order
{

    const SOURCE_NAV = "nav";
    const SOURCE_APIV1 = "apiv1";
    const SOURCE_APIV2 = "apiv2";
    const SOURCE_FOODOUT = "foodout";

    public static $sourceCollection = [
        Order::SOURCE_FOODOUT,
        Order::SOURCE_NAV,
        Order::SOURCE_APIV1,
        Order::SOURCE_APIV2,
    ];

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\Place")
     * @ORM\JoinColumn(name="place_id", referencedColumnName="id")
     */
    private $place;

    /**
     * @var string
     * @ORM\Column(name="place_name", type="string", length=100, nullable=true)
     */
    private $place_name;


    /**
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\PlacePoint")
     * @ORM\JoinColumn(name="point_id", referencedColumnName="id")
     */
    private $place_point;

    /**
     * @var string
     * @ORM\Column(name="place_point_city", type="string", length=100, nullable=true)
     */
    private $place_point_city;

    /**
     * @var string
     * @ORM\Column(name="place_point_address", type="string", length=100, nullable=true)
     */
    private $place_point_address;

    /**
     * @var bool
     * @ORM\Column(name="place_point_self_delivery", type="boolean")
     */
    private $place_point_self_delivery = false;

    /**
     * @ORM\Column(name="order_date", type="datetime")
     */
    private $order_date;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User", inversedBy="order")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="dispatcher_id", referencedColumnName="id")
     **/
    private $dispatcher_id;

    /**
     * @var string
     * @ORM\Column(name="delivery_type", type="string", length=50, nullable=true)
     */
    private $deliveryType;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\UserAddress")
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     **/
    private $address_id;

    private $shitfoks;

    private $originAddress;

    private $house;

    private $flat;

    /**
     * @ORM\Column(name="order_status", type="string", length=50, nullable=false)
     **/
    private $order_status = 'new';

    /**
     * @var string
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment = null;

    /**
     * @var string
     * @ORM\Column(name="place_comment", type="text", nullable=true)
     */
    private $place_comment;

    /**
     * @var integer
     * @ORM\Column(name="vat", type="integer")
     */
    private $vat;

    /**
     * @var float
     * @ORM\Column(name="total", type="decimal", precision=8, scale=2, nullable=true)
     */
    private $total;

    /**
     * @var decimal
     * @ORM\Column(name="adminFee", type="decimal", precision=8, scale=2, nullable=true)
     */
    private $adminFee;

    /**
     * @var float
     * @ORM\Column(name="total_before_discount", type="decimal", precision=8, scale=2, nullable=true)
     */
    private $totalBeforeDiscount;

    /**
     * @var float
     * @ORM\Column(name="delivery_price", type="float", nullable=true)
     */
    private $deliveryPrice;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\OrderBundle\Entity\Coupon")
     * @ORM\JoinColumn(name="coupon", referencedColumnName="id")
     **/
    private $coupon;

    /**
     * @var string
     *
     * @ORM\Column(name="coupon_code", type="string", length=255, nullable=true)
     */
    private $couponCode;

    /**
     * @var int
     *
     * @ORM\Column(name="discount_size", type="integer",  nullable=true)
     */
    private $discountSize;

    /**
     * @var float
     * @ORM\Column(name="discount_sum", type="decimal", precision=8, scale=2, nullable=true)
     */
    private $discountSum;

    /**
     * @var integer
     * @ORM\Column(name="order_hash", type="string", length=100)
     */
    private $order_hash;

    /**
     * @var string
     * @ORM\Column(name="payment_method", type="string", length=100, nullable=true)
     */
    private $paymentMethod = null;

    /**
     * @var string
     * @ORM\Column(name="payment_method_code", type="string", length=100, nullable=true)
     */
    private $paymentMethodCode = null;

    /**
     * @var string
     * @ORM\Column(name="payment_status", type="string")
     */
    private $paymentStatus = 'new';

    /**
     * @ORM\Column(name="submitted_for_payment", type="datetime", nullable=true)
     */
    private $submittedForPayment = null;

    /**
     * @ORM\Column(name="last_updated", type="datetime", nullable=true)
     */
    private $lastUpdated = null;

    /**
     * @var string
     * @ORM\Column(name="last_payment_error", type="string", nullable=true)
     */
    private $lastPaymentError = null;

    /**
     * @var OrderDetails[]
     *
     * @ORM\OneToMany(targetEntity="OrderDetails", mappedBy="order_id")
     */
    private $details;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\AppBundle\Entity\Driver")
     * @ORM\JoinColumn(name="driver_id", referencedColumnName="id")
     **/
    private $driver;

    /**
     * @var boolean $driverAutoAssigned
     * @ORM\Column(name="driver_auto_assigned", type="boolean", options={"default"=false})
     **/
    private $driverAutoAssigned = false;

    /**
     * @var mixed
     */
    private $driverSafe = array();

    /**
     * @var string $navDriverCode
     *
     * @ORM\Column(name="nav_driver_code", type="string", length=10, nullable=true)
     */
    private $navDriverCode;

    /**
     * @var string $locale
     *
     * @ORM\Column(name="locale", type="string", length=4)
     */
    private $locale;

    /**
     * @var \Food\OrderBundle\Entity\OrderStatusLog $orderStatusLog
     * @ORM\OneToMany(targetEntity="\Food\OrderBundle\Entity\OrderStatusLog", mappedBy="order")
     **/
    private $orderStatusLog;

    /**
     * @var \Food\OrderBundle\Entity\OrderMailLog $orderMailLog
     * @ORM\OneToMany(targetEntity="\Food\OrderBundle\Entity\OrderMailLog", mappedBy="order")
     **/
    private $orderMailLog;

    /**
     * @var \Food\SmsBundle\Entity\Message
     * @ORM\OneToMany(targetEntity="\Food\SmsBundle\Entity\Message", mappedBy="order")
     **/
    private $smsMessages;

    /**
     * @var \Food\OrderBundle\Entity\PaymentLog $paymentLog
     * @ORM\OneToMany(targetEntity="\Food\OrderBundle\Entity\PaymentLog", mappedBy="order")
     **/
    private $paymentLog;

    /**
     * @var \Food\OrderBundle\Entity\OrderLog $orderLog
     * @ORM\OneToMany(targetEntity="\Food\OrderBundle\Entity\OrderLog", mappedBy="order")
     **/
    private $orderLog;

    /**
     * @var \Food\OrderBundle\Entity\OrderDeliveryLog $orderDeliveryLog
     * @ORM\OneToMany(targetEntity="\Food\OrderBundle\Entity\OrderDeliveryLog", mappedBy="order")
     **/
    private $orderDeliveryLog;

    /**
     * @var \DateTime
     * @ORM\Column(name="accept_time", type="datetime", nullable=true)
     */
    private $acceptTime;

    /**
     * @var
     * @ORM\Column(name="delivery_time", type="datetime", nullable=true)
     */
    private $deliveryTime;
    /**
     * @var
     * @ORM\Column(name="completed_time", type="datetime", nullable=true)
     */
    private $completedTime;

    /**
     * @var bool
     * @ORM\Column(name="is_delay", type="boolean", nullable=true)
     */
    private $delayed;

    /**
     * @var int
     * @ORM\Column(name="delay_duration", type="integer", nullable=true)
     */
    private $delayDuration;

    /**
     * @var string $locale
     *
     * @ORM\Column(name="delay_reason", type="string", length=255,  nullable=true)
     */
    private $delayReason;

    /**
     * @var string $userIp
     *
     * @ORM\Column(name="user_ip", type="string", length=32,  nullable=true)
     */
    private $userIp;

    /**
     * @var bool
     * @ORM\Column(name="company", type="boolean", nullable=true)
     */
    private $company = false;

    /**
     * @var string
     * @ORM\Column(name="company_name", type="string", length=160, nullable=true)
     */
    private $companyName;

    /**
     * @var string
     * @ORM\Column(name="company_code", type="string", length=60, nullable=true)
     */
    private $companyCode;

    /**
     * @var string
     * @ORM\Column(name="vat_code", type="string", length=60, nullable=true)
     */
    private $vatCode;

    /**
     * @var string
     * @ORM\Column(name="company_address", type="text", nullable=true)
     */
    private $company_address;

    /**
     * @var bool
     * @ORM\Column(name="is_corporate_client", type="boolean", nullable=true)
     */
    private $isCorporateClient = false;

    /**
     * @var string
     * @ORM\Column(name="division_code", type="string", length=60, nullable=true)
     */
    private $divisionCode;

    /**
     * @var bool
     * @ORM\Column(name="reminded", type="datetime", nullable=true)
     */
    private $reminded = null;

    /**
     * @var string
     * @ORM\Column(name="sf_series", type="string", length=4, nullable=true)
     */
    private $sfSeries = null;

    /**
     * @var int
     * @ORM\Column(name="sf_number", type="integer", nullable=true)
     */
    private $sfNumber = null;

    /**
     * @ORM\Version @ORM\Column(type="integer")
     */
    private $version;

    /**
     * @var bool
     * @ORM\Column(name="mobile", type="boolean", nullable=true)
     */
    private $mobile = false;

    /**
     * @var bool
     * @ORM\Column(name="preorder", type="boolean", nullable=true)
     */
    private $preorder = false;

    /**
     * @var bool
     * @ORM\Column(name="nav_price_update", type="boolean", nullable=true)
     */
    private $navPriceUpdated = false;

    /**
     * @var bool
     * @ORM\Column(name="nav_process_order", type="boolean", nullable=true)
     */
    private $navPorcessedOrder = false;

    /**
     * @var int
     * @ORM\Column(name="nav_delivery_order", type="string", length=20, nullable=true)
     */
    private $navDeliveryOrder;

    /**
     * @var bool
     * @ORM\Column(name="order_from_nav", type="boolean", nullable=true)
     */
    private $orderFromNav = false;

    /**
     * @var bool
     * @ORM\Column(name="late_order_informed", type="boolean", nullable=true)
     */
    private $lateOrderInformed = false;

    /**
     * @var bool
     * @ORM\Column(name="client_contacted", type="boolean", nullable=true)
     */
    private $clientContacted = false;

    /**
     * @var bool
     * @ORM\Column(name="problem_solved", type="boolean", nullable=true)
     */
    private $problemSolved = false;

    /**
     * @var \Food\OrderBundle\Entity\OrderExtra
     * @ORM\OneToOne(targetEntity="\Food\OrderBundle\Entity\OrderExtra", mappedBy="order", cascade={"persist"})
     **/
    private $orderExtra;

    /**
     * @var bool
     * @ORM\Column(name="order_picked", type="boolean", nullable=true)
     */
    private $order_picked = false;

    /**
     * @var bool
     * @ORM\Column(name="newsletter_subscribe", type="boolean", options={"default"=true})
     */
    private $newsletterSubscribe = false;

    /**
     * @var integer
     * @ORM\Column(name="assign_late", type="integer", nullable=true)
     */
    private $assignLate;

    /**
     * @var bool
     * @ORM\Column(name="place_informed", type="boolean")
     */
    private $place_informed = false;

    /**
     * @var bool
     * @ORM\Column(name="during_zavalas", type="boolean", nullable=true)
     */
    private $duringZavalas = false;

    /**
     * @var \Food\OrderBundle\Entity\OrderFieldChangelog $orderFieldChangelog
     * @ORM\OneToMany(targetEntity="\Food\OrderBundle\Entity\OrderFieldChangelog", mappedBy="order")
     **/
    private $orderFieldChangelog;

    /**
     * @var \Food\AppBundle\Entity\CallLog $orderFieldChangelog
     * @ORM\OneToMany(targetEntity="\Food\AppBundle\Entity\CallLog", mappedBy="order_id")
     **/
    private $orderCallLog;

    /**
     * @var int
     * @ORM\Column(name="food_prepare_time", type="integer", nullable=true)
     */
    private $foodPrepareTime;

    /**
     * @var \DateTime
     * @ORM\Column(name="food_prepare_date", type="datetime", nullable=true)
     */
    private $foodPrepareDate;

    /**
     * @var \Food\AppBundle\Entity\City
     *
     * @ORM\ManyToOne(targetEntity="\Food\AppBundle\Entity\City")
     * @ORM\JoinColumn(name="city_id", referencedColumnName="id")
     **/
    private $cityId;

    /**
     * @var string
     * @ORM\Column(name="source", type="string", length=20, nullable=true)
     */
    private $source = Order::SOURCE_FOODOUT;

    /**
     * @var string
     * @ORM\Column(name="signal_token", type="string", length=255, nullable=true)
     */
    private $signalToken;

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->getId()) {
            return $this->getId() . '-' . $this->getPlaceName() . '-' . $this->getAddressId();
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
     * Set order_date
     *
     * @param \DateTime $orderDate
     * @return Order
     */
    public function setOrderDate($orderDate)
    {
        $this->order_date = $orderDate;

        return $this;
    }

    /**
     * Get order_date
     *
     * @return \DateTime
     */
    public function getOrderDate()
    {
        return $this->order_date;
    }

    /**
     * Set user
     *
     * @param \Food\UserBundle\Entity\User $user
     * @return Order
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
     * Set address_id
     *
     * @param \Food\UserBundle\Entity\UserAddress $addressId
     * @return Order
     */
    public function setAddressId(\Food\UserBundle\Entity\UserAddress $addressId = null)
    {
        $this->address_id = $addressId;

        return $this;
    }

    /**
     * Get address_id
     *
     * @return \Food\UserBundle\Entity\UserAddress
     */
    public function getAddressId()
    {
        return $this->address_id;
    }

    /**
     * Set order_status
     *
     * @param string $orderStatus
     * @return Order
     */
    public function setOrderStatus($orderStatus = null)
    {
        $this->order_status = $orderStatus;

        return $this;
    }

    /**
     * Get order_status
     *
     * @return string
     */
    public function getOrderStatus()
    {
        return $this->order_status;
    }

    /**
     * Set vat
     *
     * @param integer $vat
     * @return Order
     */
    public function setVat($vat)
    {
        $this->vat = $vat;

        return $this;
    }

    /**
     * Get vat
     *
     * @return integer
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * Set order_hash
     *
     * @param string $orderHash
     * @return Order
     */
    public function setOrderHash($orderHash)
    {
        $this->order_hash = $orderHash;

        return $this;
    }

    /**
     * Get order_hash
     *
     * @return string
     */
    public function getOrderHash()
    {
        return $this->order_hash;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return Order
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set place_comment
     *
     * @param string $placeComment
     * @return Order
     */
    public function setPlaceComment($placeComment)
    {
        $this->place_comment = $placeComment;

        return $this;
    }

    /**
     * Get place_comment
     *
     * @return string
     */
    public function getPlaceComment()
    {
        return $this->place_comment;
    }

    /**
     * Set paymentStatus
     *
     * @param string $paymentStatus
     * @return Order
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    /**
     * Get paymentStatus
     *
     * @return string
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * Set lastPaymentError
     *
     * @param string $lastPaymentError
     * @return Order
     */
    public function setLastPaymentError($lastPaymentError)
    {
        $this->lastPaymentError = $lastPaymentError;

        return $this;
    }

    /**
     * Get lastPaymentError
     *
     * @return string
     */
    public function getLastPaymentError()
    {
        return $this->lastPaymentError;
    }

    /**
     * Set paymentMethod
     *
     * @param string $paymentMethod
     * @return Order
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * Get paymentMethod
     *
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * Set submittedForPayment
     *
     * @param \DateTime $submittedForPayment
     * @return Order
     */
    public function setSubmittedForPayment($submittedForPayment)
    {
        $this->submittedForPayment = $submittedForPayment;

        return $this;
    }

    /**
     * Get submittedForPayment
     *
     * @return \DateTime
     */
    public function getSubmittedForPayment()
    {
        return $this->submittedForPayment;
    }

    /**
     * Set lastUpdated
     *
     * @param \DateTime $lastUpdated
     * @return Order
     */
    public function setLastUpdated($lastUpdated)
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }

    /**
     * Get lastUpdated
     *
     * @return \DateTime
     */
    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }

    /**
     * Set deliveryType
     *
     * @param string $deliveryType
     * @return Order
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
     * Convert order to array
     *
     * TODO detailsu sudejimas i masyva
     *
     * @return array
     */
    public function __toArray()
    {
        $user = $this->getUser();
        $userId = null;
        if (!empty($user) && is_object($user)) {
            $userId = $user->getId();
        }

        $submittedForPayment = $this->getSubmittedForPayment();
        if (!empty($submittedForPayment) && $submittedForPayment instanceof \DateTime) {
            $submittedForPayment = $submittedForPayment->format("Y-m-d H:i:s");
        } else {
            $submittedForPayment = null;
        }

        $lastUpdated = $this->getSubmittedForPayment();
        if (!empty($lastUpdated) && $lastUpdated instanceof \DateTime) {
            $lastUpdated = $lastUpdated->format("Y-m-d H:i:s");
        } else {
            $lastUpdated = null;
        }

        return array(
            'id' => $this->getId(),
            'userId' => $userId,
            'addressId' => $this->getAddressId(),
            'userIp' => $this->getUserIp(),
            'details' => 'TODO', // TODO
            'orderStatus' => $this->getOrderStatus(),
            'orderDate' => $this->getOrderDate()->format("Y-m-d H:i:s"),
            'deliveryDate' => $this->getDeliveryTime()->format("Y-m-d H:i"),
            'total' => $this->getTotal(),
            'vat' => $this->getVat(),
            'coupon_code' => $this->getCouponCode(),
            'discount_size' => $this->getDiscountSize(),
            'discount_sum' => $this->getDiscountSum(),
            'orderHash' => $this->getOrderHash(),
            'comment' => $this->getComment(),
            'placeComent' => $this->getPlaceComment(),
            'paymentMethod' => $this->getPaymentMethod(),
            'paymentStatus' => $this->getPaymentStatus(),
            'submittedForPayment' => $submittedForPayment,
            'lastUpdated' => $lastUpdated,
            'lastPaymentError' => $this->getLastPaymentError(),
            'deliveryType' => $this->getDeliveryType(),
        );
    }

    /**
     * Set place_name
     *
     * @param string $placeName
     * @return Order
     */
    public function setPlaceName($placeName)
    {
        $this->place_name = $placeName;

        return $this;
    }

    /**
     * Get place_name
     *
     * @return string
     */
    public function getPlaceName()
    {
        return $this->place_name;
    }

    /**
     * Set place_point_address
     *
     * @param string $placePointAddress
     * @return Order
     */
    public function setPlacePointAddress($placePointAddress)
    {
        $this->place_point_address = $placePointAddress;

        return $this;
    }

    /**
     * Get place_point_address
     *
     * @return string
     */
    public function getPlacePointAddress()
    {
        return $this->place_point_address;
    }

    /**
     * Set place
     *
     * @param \Food\DishesBundle\Entity\Place $place
     * @return Order
     */
    public function setPlace(\Food\DishesBundle\Entity\Place $place = null)
    {
        $this->place = $place;

        return $this;
    }

    /**
     * Get place
     *
     * @return \Food\DishesBundle\Entity\Place
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Remove place_point
     *
     * @param \Food\DishesBundle\Entity\PlacePoint $placePoint
     */
    public function removePlacePoint(\Food\DishesBundle\Entity\PlacePoint $placePoint)
    {
        $this->place_point->removeElement($placePoint);
    }

    /**
     * Get place_point
     *
     * @return \Food\DishesBundle\Entity\PlacePoint
     */
    public function getPlacePoint()
    {
        return $this->place_point;
    }

    /**
     * Set place_point
     *
     * @param \Food\DishesBundle\Entity\PlacePoint $placePoint
     * @return Order
     */
    public function setPlacePoint(\Food\DishesBundle\Entity\PlacePoint $placePoint = null)
    {
        $this->place_point = $placePoint;

        return $this;
    }

    /**
     * Add details
     *
     * @param \Food\OrderBundle\Entity\OrderDetails $details
     * @return Order
     */
    public function addDetail(\Food\OrderBundle\Entity\OrderDetails $details)
    {
        $this->details[] = $details;

        return $this;
    }

    /**
     * Remove details
     *
     * @param \Food\OrderBundle\Entity\OrderDetails $details
     */
    public function removeDetail(\Food\OrderBundle\Entity\OrderDetails $details)
    {
        $this->details->removeElement($details);
    }

    /**
     * Get details
     *
     * @return OrderDetails[]
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @deprecated
     * Set place_point_city
     *
     * @param string $placePointCity
     * @return Order
     */
    public function setPlacePointCity($placePointCity)
    {
        $this->place_point_city = $placePointCity;

        return $this;
    }

    /**
     * @deprecated
     * Get place_point_city
     *
     * @return string
     */
    public function getPlacePointCity()
    {
        $placePointCity = $this->place_point_city;
        if (!$this->place_point_city) {
            $placePointCity = $this->getPlacePoint()->getCityId()->getTitle();
        }

        return $placePointCity;
    }

    /**
     * Set place_point_self_delivery
     *
     * @param boolean $placePointSelfDelivery
     * @return Order
     */
    public function setPlacePointSelfDelivery($placePointSelfDelivery)
    {
        $this->place_point_self_delivery = $placePointSelfDelivery;

        return $this;
    }

    /**
     * Get place_point_self_delivery
     *
     * @return boolean
     */
    public function getPlacePointSelfDelivery()
    {
        return $this->place_point_self_delivery;
    }

    /**
     * Set driver
     *
     * @param \Food\AppBundle\Entity\Driver $driver
     * @return Order
     */
    public function setDriver(\Food\AppBundle\Entity\Driver $driver = null)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Get driver
     *
     * @return \Food\AppBundle\Entity\Driver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Add orderStatusLog
     *
     * @param \Food\OrderBundle\Entity\OrderStatusLog $orderStatusLog
     * @return Order
     */
    public function addOrderStatusLog(\Food\OrderBundle\Entity\OrderStatusLog $orderStatusLog)
    {
        $this->orderStatusLog[] = $orderStatusLog;

        return $this;
    }

    /**
     * Remove orderStatusLog
     *
     * @param \Food\OrderBundle\Entity\OrderStatusLog $orderStatusLog
     */
    public function removeOrderStatusLog(\Food\OrderBundle\Entity\OrderStatusLog $orderStatusLog)
    {
        $this->orderStatusLog->removeElement($orderStatusLog);
    }

    /**
     * Get orderStatusLog
     *
     * @return \Food\OrderBundle\Entity\OrderStatusLog
     */
    public function getOrderStatusLog()
    {
        return $this->orderStatusLog;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return Order
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set total
     *
     * @param float $total
     * @return Order
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Get total
     *
     * @return string
     */
    public function getTotalLocalized()
    {
        return str_replace('.', ',', $this->total);
    }

    /**
     * Set delayDuration
     *
     * @param integer $delayDuration
     * @return Order
     */
    public function setDelayDuration($delayDuration)
    {
        $this->delayDuration = $delayDuration;

        return $this;
    }

    /**
     * Get delayDuration
     *
     * @return integer
     */
    public function getDelayDuration()
    {
        return $this->delayDuration;
    }

    /**
     * Set delayReason
     *
     * @param string $delayReason
     * @return Order
     */
    public function setDelayReason($delayReason)
    {
        $this->delayReason = $delayReason;

        return $this;
    }

    /**
     * Get delayReason
     *
     * @return string
     */
    public function getDelayReason()
    {
        return $this->delayReason;
    }

    /**
     * Set acceptTime
     *
     * @param \DateTime $acceptTime
     * @return Order
     */
    public function setAcceptTime($acceptTime)
    {
        $this->acceptTime = $acceptTime;

        return $this;
    }

    /**
     * Get acceptTime
     *
     * @return \DateTime
     */
    public function getAcceptTime()
    {
        return $this->acceptTime;
    }

    /**
     * Set deliveryTime
     *
     * @param \DateTime $deliveryTime
     * @return Order
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->deliveryTime = $deliveryTime;

        return $this;
    }

    /**
     * Get deliveryTime
     *
     * @return \DateTime
     */
    public function getDeliveryTime()
    {
        return $this->deliveryTime;
    }

    /**
     * Set delayed
     *
     * @param boolean $delayed
     * @return Order
     */
    public function setDelayed($delayed)
    {
        $this->delayed = $delayed;

        return $this;
    }

    /**
     * Get delayed
     *
     * @return boolean
     */
    public function getDelayed()
    {
        return $this->delayed;
    }

    /**
     * Set userIp
     *
     * @param string $userIp
     * @return Order
     */
    public function setUserIp($userIp)
    {
        $this->userIp = $userIp;

        return $this;
    }

    /**
     * Get userIp
     *
     * @return string
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * Add paymentLog
     *
     * @param \Food\OrderBundle\Entity\PaymentLog $paymentLog
     * @return Order
     */
    public function addPaymentLog(\Food\OrderBundle\Entity\PaymentLog $paymentLog)
    {
        $this->paymentLog[] = $paymentLog;

        return $this;
    }

    /**
     * Remove paymentLog
     *
     * @param \Food\OrderBundle\Entity\PaymentLog $paymentLog
     */
    public function removePaymentLog(\Food\OrderBundle\Entity\PaymentLog $paymentLog)
    {
        $this->paymentLog->removeElement($paymentLog);
    }

    /**
     * Get paymentLog
     *
     * @return PaymentLog
     */
    public function getPaymentLog()
    {
        return $this->paymentLog;
    }

    /**
     * Set couponCode
     *
     * @param string $couponCode
     * @return Order
     */
    public function setCouponCode($couponCode)
    {
        $this->couponCode = $couponCode;

        return $this;
    }

    /**
     * Get couponCode
     *
     * @return string
     */
    public function getCouponCode()
    {
        return $this->couponCode;
    }

    /**
     * Set discountSize
     *
     * @param integer $discountSize
     * @return Order
     */
    public function setDiscountSize($discountSize)
    {
        $this->discountSize = $discountSize;

        return $this;
    }

    /**
     * Get discountSize
     *
     * @return integer
     */
    public function getDiscountSize()
    {
        return $this->discountSize;
    }

    /**
     * Set discountSum
     *
     * @param float $discountSum
     * @return Order
     */
    public function setDiscountSum($discountSum)
    {
        $this->discountSum = $discountSum;

        return $this;
    }

    /**
     * Get discountSum
     *
     * @return float
     */
    public function getDiscountSum()
    {
        return $this->discountSum;
    }

    /**
     * Set coupon
     *
     * @param \Food\OrderBundle\Entity\Coupon $coupon
     * @return Order
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

    /**
     * @param mixed $driverSafe
     */
    public function setDriverSafe($driverSafe)
    {
        $this->driverSafe = $driverSafe;
    }

    /**
     * @return mixed
     */
    public function getDriverSafe()
    {
        return $this->driverSafe;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->details = new \Doctrine\Common\Collections\ArrayCollection();
        $this->orderStatusLog = new \Doctrine\Common\Collections\ArrayCollection();
        $this->paymentLog = new \Doctrine\Common\Collections\ArrayCollection();
        $this->orderLog = new \Doctrine\Common\Collections\ArrayCollection();
        $this->orderCallLog = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add orderLog
     *
     * @param \Food\OrderBundle\Entity\OrderLog $orderLog
     * @return Order
     */
    public function addOrderLog(\Food\OrderBundle\Entity\OrderLog $orderLog)
    {
        $this->orderLog[] = $orderLog;

        return $this;
    }

    /**
     * Remove orderLog
     *
     * @param \Food\OrderBundle\Entity\OrderLog $orderLog
     */
    public function removeOrderLog(\Food\OrderBundle\Entity\OrderLog $orderLog)
    {
        $this->orderLog->removeElement($orderLog);
    }

    /**
     * Get orderLog
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrderLog()
    {
        return $this->orderLog;
    }

    /**
     * Set version
     *
     * @param integer $version
     * @return Order
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

    /**
     * Set mobile
     *
     * @param boolean $mobile
     * @return Order
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return boolean
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set sfSeries
     *
     * @param string $sfSeries
     * @return Order
     */
    public function setSfSeries($sfSeries)
    {
        $this->sfSeries = $sfSeries;

        return $this;
    }

    /**
     * Get sfSeries
     *
     * @return string
     */
    public function getSfSeries()
    {
        return $this->sfSeries;
    }

    /**
     * Set sfNumber
     *
     * @param integer $sfNumber
     * @return Order
     */
    public function setSfNumber($sfNumber)
    {
        $this->sfNumber = $sfNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getSfLine()
    {
        return $this->sfSeries . $this->sfNumber;
    }

    /**
     * Get sfNumber
     *
     * @return integer
     */
    public function getSfNumber()
    {
        return $this->sfNumber;
    }

    /**
     * Set navPriceUpdated
     *
     * @param boolean $navPriceUpdated
     * @return Order
     */
    public function setNavPriceUpdated($navPriceUpdated)
    {
        $this->navPriceUpdated = $navPriceUpdated;

        return $this;
    }

    /**
     * Get navPriceUpdated
     *
     * @return boolean
     */
    public function getNavPriceUpdated()
    {
        return $this->navPriceUpdated;
    }

    /**
     * Set navPorcessedOrder
     *
     * @param boolean $navPorcessedOrder
     * @return Order
     */
    public function setNavPorcessedOrder($navPorcessedOrder)
    {
        $this->navPorcessedOrder = $navPorcessedOrder;

        return $this;
    }

    /**
     * Get navPorcessedOrder
     *
     * @return boolean
     */
    public function getNavPorcessedOrder()
    {
        return $this->navPorcessedOrder;
    }

    /**
     * Set navDeliveryOrder
     *
     * @param integer $navDeliveryOrder
     * @return Order
     */
    public function setNavDeliveryOrder($navDeliveryOrder)
    {
        $this->navDeliveryOrder = $navDeliveryOrder;

        return $this;
    }

    /**
     * Get navDeliveryOrder
     *
     * @return integer
     */
    public function getNavDeliveryOrder()
    {
        return $this->navDeliveryOrder;
    }

    /**
     * Set deliveryPrice
     *
     * @param float $deliveryPrice
     * @return Order
     */
    public function setDeliveryPrice($deliveryPrice)
    {
        $this->deliveryPrice = $deliveryPrice;

        return $this;
    }

    /**
     * Get deliveryPrice
     *
     * @return float
     */
    public function getDeliveryPrice()
    {
        return $this->deliveryPrice;
    }

    /**
     * Set orderFromNav
     *
     * @param boolean $orderFromNav
     * @return Order
     */
    public function setOrderFromNav($orderFromNav)
    {
        $this->orderFromNav = $orderFromNav;

        return $this;
    }

    /**
     * Get orderFromNav
     *
     * @return boolean
     */
    public function getOrderFromNav()
    {
        return $this->orderFromNav;
    }

    public function isDriverNotDeleted()
    {
        try {
            $driver = $this->getDriver();

            if ($driver == null) {
                return true;
            }

            if (!$driver instanceof Driver) {
                return false;
            }

            $driver->getName();

            return true;
        } catch (\Exception $e) {
            // No driver set or he is deleted :|
            return false;
        }
    }

    /**
     * @return string
     */
    public function getDriverContact()
    {
        try {
            $driver = $this->getDriver();

            if (!$driver instanceof Driver) {
                return '';
            }

            return $driver->getContact();
        } catch (\Exception $e) {
            // No driver set or he is deleted :|
            return '';
        }
    }

    /**
     * Set company
     *
     * @param boolean $company
     * @return Order
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company
     *
     * @return boolean
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set companyName
     *
     * @param string $companyName
     * @return Order
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

    /**
     * Set companyCode
     *
     * @param string $companyCode
     * @return Order
     */
    public function setCompanyCode($companyCode)
    {
        $this->companyCode = $companyCode;

        return $this;
    }

    /**
     * Get companyCode
     *
     * @return string
     */
    public function getCompanyCode()
    {
        return $this->companyCode;
    }

    /**
     * Set vatCode
     *
     * @param string $vatCode
     * @return Order
     */
    public function setVatCode($vatCode)
    {
        $this->vatCode = $vatCode;

        return $this;
    }

    /**
     * Get vatCode
     *
     * @return string
     */
    public function getVatCode()
    {
        return $this->vatCode;
    }

    /**
     * Set company_address
     *
     * @param string $companyAddress
     * @return Order
     */
    public function setCompanyAddress($companyAddress)
    {
        $this->company_address = $companyAddress;

        return $this;
    }

    /**
     * Get company_address
     *
     * @return string
     */
    public function getCompanyAddress()
    {
        return $this->company_address;
    }

    /**
     * Set navDriverCode
     *
     * @param string $navDriverCode
     * @return Order
     */
    public function setNavDriverCode($navDriverCode)
    {
        $this->navDriverCode = $navDriverCode;

        return $this;
    }

    /**
     * Get navDriverCode
     *
     * @return string
     */
    public function getNavDriverCode()
    {
        return $this->navDriverCode;
    }

    /**
     * Set lateOrderInformed
     *
     * @param boolean $lateOrderInformed
     * @return Order
     */
    public function setLateOrderInformed($lateOrderInformed)
    {
        $this->lateOrderInformed = $lateOrderInformed;

        return $this;
    }

    /**
     * Get lateOrderInformed
     *
     * @return boolean
     */
    public function getLateOrderInformed()
    {
        return $this->lateOrderInformed;
    }

    /**
     * Set clientContacted
     *
     * @param boolean $clientContacted
     * @return Order
     */
    public function setClientContacted($clientContacted)
    {
        $this->clientContacted = $clientContacted;

        return $this;
    }

    /**
     * Get clientContacted
     *
     * @return boolean
     */
    public function getClientContacted()
    {
        return $this->clientContacted;
    }

    /**
     * Add messages
     *
     * @param \Food\SmsBundle\Entity\Message $messages
     * @return Order
     */
    public function addMessage(\Food\SmsBundle\Entity\Message $messages)
    {
        $this->messages[] = $messages;

        return $this;
    }

    /**
     * Remove messages
     *
     * @param \Food\SmsBundle\Entity\Message $messages
     */
    public function removeSmsMessage(\Food\SmsBundle\Entity\Message $messages)
    {
        $this->smsMessages->removeElement($messages);
    }

    /**
     * Get messages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSmsMessages()
    {
        return $this->smsMessages;
    }

    /**
     * Add orderMailLog
     *
     * @param \Food\OrderBundle\Entity\OrderMailLog $orderMailLog
     * @return Order
     */
    public function addOrderMailLog(\Food\OrderBundle\Entity\OrderMailLog $orderMailLog)
    {
        $this->orderMailLog[] = $orderMailLog;

        return $this;
    }

    /**
     * Remove orderMailLog
     *
     * @param \Food\OrderBundle\Entity\OrderMailLog $orderMailLog
     */
    public function removeOrderMailLog(\Food\OrderBundle\Entity\OrderMailLog $orderMailLog)
    {
        $this->orderMailLog->removeElement($orderMailLog);
    }

    /**
     * Get orderMailLog
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrderMailLog()
    {
        return $this->orderMailLog;
    }

    /**
     * Add smsMessages
     *
     * @param \Food\SmsBundle\Entity\Message $smsMessages
     * @return Order
     */
    public function addSmsMessage(\Food\SmsBundle\Entity\Message $smsMessages)
    {
        $this->smsMessages[] = $smsMessages;

        return $this;
    }

    /**
     * Set orderExtra
     *
     * @param \Food\OrderBundle\Entity\OrderExtra $orderExtra
     * @return Order
     */
    public function setOrderExtra(\Food\OrderBundle\Entity\OrderExtra $orderExtra = null)
    {
        $this->orderExtra = $orderExtra;

        return $this;
    }

    /**
     * Get orderExtra
     *
     * @return \Food\OrderBundle\Entity\OrderExtra
     */
    public function getOrderExtra()
    {
        return $this->orderExtra;
    }

    /**
     * @return string
     */
    public function getUserContact()
    {
        if (!$this->getId()) {
            return '';
        }
        $userContactData = $this->getOrderExtra()->getFirstname();
        $surname = $this->getOrderExtra()->getLastname();
        $email = $this->getOrderExtra()->getEmail();
        $phone = $this->getOrderExtra()->getPhone();

        if (!empty($surname)) {
            $userContactData .= ' ' . $surname;
        }
        if (!empty($email)) {
            $userContactData .= ', ' . $email;
        }
        if (!empty($phone)) {
            $userContactData .= ', ' . $phone;
        }

        return $userContactData;
    }

    /**
     * Set divisionCode
     *
     * @param string $divisionCode
     * @return Order
     */
    public function setDivisionCode($divisionCode)
    {
        $this->divisionCode = $divisionCode;

        return $this;
    }

    /**
     * Get divisionCode
     *
     * @return string
     */
    public function getDivisionCode()
    {
        return $this->divisionCode;
    }

    /**
     * Set isCorporateClient
     *
     * @param boolean $isCorporateClient
     * @return Order
     */
    public function setIsCorporateClient($isCorporateClient)
    {
        $this->isCorporateClient = $isCorporateClient;

        return $this;
    }

    /**
     * Get isCorporateClient
     *
     * @return boolean
     */
    public function getIsCorporateClient()
    {
        return $this->isCorporateClient;
    }

    /**
     * Set problemSolved
     *
     * @param boolean $problemSolved
     * @return Order
     */
    public function setProblemSolved($problemSolved)
    {
        $this->problemSolved = $problemSolved;

        return $this;
    }

    /**
     * Get problemSolved
     *
     * @return boolean
     */
    public function getProblemSolved()
    {
        return $this->problemSolved;
    }

    /**
     * Set order_picked
     *
     * @param boolean $orderPicked
     * @return Order
     */
    public function setOrderPicked($orderPicked)
    {
        $this->order_picked = $orderPicked;

        return $this;
    }

    /**
     * Get order_picked
     *
     * @return boolean
     */
    public function getOrderPicked()
    {
        return $this->order_picked;
    }

    /**
     * Add orderDeliveryLog
     *
     * @param \Food\OrderBundle\Entity\OrderDeliveryLog $orderDeliveryLog
     * @return Order
     */
    public function addOrderDeliveryLog(\Food\OrderBundle\Entity\OrderDeliveryLog $orderDeliveryLog)
    {
        $this->orderDeliveryLog[] = $orderDeliveryLog;

        return $this;
    }

    /**
     * Remove orderDeliveryLog
     *
     * @param \Food\OrderBundle\Entity\OrderDeliveryLog $orderDeliveryLog
     */
    public function removeOrderDeliveryLog(\Food\OrderBundle\Entity\OrderDeliveryLog $orderDeliveryLog)
    {
        $this->orderDeliveryLog->removeElement($orderDeliveryLog);
    }

    /**
     * Get orderDeliveryLog
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrderDeliveryLog()
    {
        return $this->orderDeliveryLog;
    }

    /**
     * Set preorder
     *
     * @param boolean $preorder
     * @return Order
     */
    public function setPreorder($preorder)
    {
        $this->preorder = $preorder;

        return $this;
    }

    /**
     * Get preorder
     *
     * @return boolean
     */
    public function getPreorder()
    {
        return $this->preorder;
    }

    /**
     * Set completedTime
     *
     * @param \DateTime $completedTime
     * @return Order
     */
    public function setCompletedTime($completedTime)
    {
        $this->completedTime = $completedTime;

        return $this;
    }

    /**
     * Get completedTime
     *
     * @return \DateTime
     */
    public function getCompletedTime()
    {
        return $this->completedTime;
    }

    /**
     * Set reminded
     *
     * @param \DateTime $reminded
     * @return Order
     */
    public function setReminded($reminded)
    {
        $this->reminded = $reminded;

        return $this;
    }

    /**
     * Get reminded
     *
     * @return \DateTime
     */
    public function getReminded()
    {
        return $this->reminded;
    }

    /**
     * Set newsletterSubscribe
     *
     * @param boolean $newsletterSubscribe
     * @return Order
     */
    public function setNewsletterSubscribe($newsletterSubscribe)
    {
        $this->newsletterSubscribe = $newsletterSubscribe;

        return $this;
    }

    /**
     * Get newsletterSubscribe
     *
     * @return boolean
     */
    public function getNewsletterSubscribe()
    {
        return $this->newsletterSubscribe;
    }

    /**
     * Set assignLate
     *
     * @param integer $assignLate
     * @return Order
     */
    public function setAssignLate($assignLate)
    {
        $this->assignLate = $assignLate;

        return $this;
    }

    /**
     * Get assignLate
     *
     * @return integer
     */
    public function getAssignLate()
    {
        return $this->assignLate;
    }

    public function getTotalWithoutVat()
    {
        if ($this->getVat()) {
            return round($this->getTotal() / (1 + $this->getVat() / 100), 2);
        }

        return $this->getTotal();
    }

    public function getAdminFeeWithoutVat()
    {
        if ($this->getVat()) {
            return round($this->getAdminFee() / (1 + $this->getVat() / 100), 2);
        }

        return $this->getAdminFee();
    }


    public function getFoodTotalForNav()
    {
        return $this->getTotal() - $this->getDeliveryPrice() + $this->getDiscountSum() - $this->getAdminFee();

    }


    public function getDeliveryPriceForNav()
    {
        return $this->getDeliveryPrice() + $this->getAdminFee();

    }

    public function getVatSize()
    {
        return $this->getTotal() - $this->getTotalWithoutVat();
    }

    public function getDeliveryWithoutVat()
    {
        if ($this->getVat()) {
            return round($this->getDeliveryPrice() / (1 + $this->getVat() / 100), 2);
        }

        return $this->getDeliveryPrice();
    }

    public function getDishesWithoutVat()
    {
        return $this->getTotalWithoutVat() - $this->getDeliveryWithoutVat();
    }

    public function getFoodTotal()
    {
        return $this->getTotal() - $this->getDeliveryPrice() - $this->getAdminFee();
    }

    /**
     * Set dispatcher_id
     *
     * @param \Food\UserBundle\Entity\User $dispatcherId
     * @return Order
     */
    public function setDispatcherId(\Food\UserBundle\Entity\User $dispatcherId = null)
    {
        $this->dispatcher_id = $dispatcherId;

        return $this;
    }

    /**
     * Get dispatcher_id
     *
     * @return \Food\UserBundle\Entity\User
     */
    public function getDispatcherId()
    {
        return $this->dispatcher_id;
    }

    /**
     * Set place_informed
     *
     * @param boolean $placeInformed
     * @return Order
     */
    public function setPlaceInformed($placeInformed)
    {
        $this->place_informed = $placeInformed;

        return $this;
    }

    /**
     * Get place_informed
     *
     * @return boolean
     */
    public function getPlaceInformed()
    {
        return $this->place_informed;
    }

    /**
     * Set driverAutoAssigned
     *
     * @param boolean $driverAutoAssigned
     * @return Order
     */
    public function setDriverAutoAssigned($driverAutoAssigned)
    {
        $this->driverAutoAssigned = $driverAutoAssigned;

        return $this;
    }

    /**
     * Get driverAutoAssigned
     *
     * @return boolean
     */
    public function getDriverAutoAssigned()
    {
        return $this->driverAutoAssigned;
    }

    /**
     * @return OrderFieldChangelog
     */
    public function getOrderFieldChangelog()
    {
        return $this->orderFieldChangelog;
    }

    /**
     * @return boolean
     */
    public function isDuringZavalas()
    {
        return $this->duringZavalas;
    }

    /**
     * @param boolean $duringZavalas
     */
    public function setDuringZavalas($duringZavalas)
    {
        $this->duringZavalas = $duringZavalas;
    }

    /**
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return $this->paymentMethodCode;
    }

    /**
     * @param string $paymentMethodCode
     */
    public function setPaymentMethodCode($paymentMethodCode)
    {
        $this->paymentMethodCode = $paymentMethodCode;
    }

    /**
     * @return float
     */
    public function getTotalBeforeDiscount()
    {
        return $this->totalBeforeDiscount;
    }

    /**
     * @param float $totalBeforeDiscount
     */
    public function setTotalBeforeDiscount($totalBeforeDiscount)
    {
        $this->totalBeforeDiscount = $totalBeforeDiscount;

        return $this;
    }


    /**
     * Get duringZavalas
     *
     * @return boolean
     */
    public function getDuringZavalas()
    {
        return $this->duringZavalas;
    }

    /**
     * Add orderFieldChangelog
     *
     * @param \Food\OrderBundle\Entity\OrderFieldChangelog $orderFieldChangelog
     * @return Order
     */
    public function addOrderFieldChangelog(\Food\OrderBundle\Entity\OrderFieldChangelog $orderFieldChangelog)
    {
        $this->orderFieldChangelog[] = $orderFieldChangelog;

        return $this;
    }

    /**
     * Remove orderFieldChangelog
     *
     * @param \Food\OrderBundle\Entity\OrderFieldChangelog $orderFieldChangelog
     */
    public function removeOrderFieldChangelog(\Food\OrderBundle\Entity\OrderFieldChangelog $orderFieldChangelog)
    {
        $this->orderFieldChangelog->removeElement($orderFieldChangelog);
    }

    /**
     * Add orderLog
     *
     * @param \Food\AppBundle\Entity\CallLog $orderCallLog
     * @return Order
     */
    public function addOrderCallLog(\Food\AppBundle\Entity\CallLog $orderCallLog)
    {
        $this->orderCallLog[] = $orderCallLog;

        return $this;
    }

    /**
     * Remove orderLog
     *
     * @param \Food\AppBundle\Entity\CallLog $orderCallLog
     */
    public function removeOrderCallLog(\Food\AppBundle\Entity\CallLog $orderCallLog)
    {
        $this->orderCallLog->removeElement($orderCallLog);
    }

    /**
     * Get orderLog
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrderCallLog()
    {
        return $this->orderCallLog;
    }

    /**
     * @return int
     */
    public function getFoodPrepareTime()
    {
        return $this->foodPrepareTime;
    }

    /**
     * @param int $foodPrepareTime
     */
    public function setFoodPrepareTime($foodPrepareTime)
    {
        $this->foodPrepareTime = $foodPrepareTime;
    }

    /**
     * @return \DateTime
     */
    public function getFoodPrepareDate()
    {
        return $this->foodPrepareDate;
    }

    /**
     * @param \DateTime $foodPrepareDate
     */
    public function setFoodPrepareDate($foodPrepareDate)
    {
        $this->foodPrepareDate = $foodPrepareDate;
    }

    /**
     * Set cityId
     *
     * @param \Food\AppBundle\Entity\City $cityId
     * @return Order
     */
    public function setCityId(\Food\AppBundle\Entity\City $cityId = null)
    {
        $this->cityId = $cityId;

        return $this;
    }

    /**
     * Get cityId
     *
     * @return \Food\AppBundle\Entity\City
     */
    public function getCityId()
    {
        return $this->cityId;
    }

    /**
     * @return decimal
     */
    public function getAdminFee()
    {
        return $this->adminFee;
    }

    /**
     * @param decimal $adminFee
     */
    public function setAdminFee($adminFee)
    {
        $this->adminFee = $adminFee;
    }

    /**
     * @return String
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param String $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    public function getShitfoksReal()
    {
        return $this->shitfoks;
    }

    public function getOriginAddressReal()
    {
        return $this->originAddress;
    }

    public function getHouseReal()
    {
        return $this->house;
    }

    public function getFlatReal()
    {
        return $this->flat;
    }



    public function getHouse()
    {
        if ($this->getAddressId()) {

            $val = $this->getAddressId()->getHouse();
            $empt = empty($val);
            if (!$empt) {
                return $this->getAddressId()->getHouse();
            } else {
                return '';
            }
        } else {
            return '';
        }
    }


    /**
     * @return mixed
     */
    public function getShitfoks()
    {
        if ($this->getAddressId()) {

            $val = $this->getAddressId()->getAddressAdmin();
            $empt = empty($val);
            if (!$empt) {
                return $this->getAddressId()->getAddressAdmin();
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    /**
     * @param mixed $shitfoks
     */
    public function setShitfoks($shitfoks)
    {
        $this->shitfoks = $shitfoks;
    }

    /**
     * @param mixed $house
     */
    public function setHouse($house)
    {
        $this->house = $house;
    }

    /**
     * @param mixed $originAddress
     */
    public function setOriginAddress($originAddress)
    {
        $this->originAddress = $originAddress;
    }

    public function getOriginAddress()
    {
        if ($this->getAddressId()) {

            $val = $this->getAddressId()->getOriginAddressAdmin();
            $empt = empty($val);
            if (!$empt) {
                return $this->getAddressId()->getOriginAddressAdmin();
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    public function setFlat($flat)
    {
        $this->flat = $flat;
    }

    public function getFlat()
    {
        if ($this->getAddressId()) {

            $val = $this->getAddressId()->getFlat();

            $empt = empty($val);
            if (!$empt) {
                return $this->getAddressId()->getFlat();

            } else {
                return '';
            }
        } else {
            return '';
        }
    }


    /**
     * Set signalToken
     *
     * @param string $signalToken
     * @return Order
     */
    public function setSignalToken($signalToken)
    {
        $this->signalToken = $signalToken;

        return $this;
    }

    /**
     * Get signalToken
     *
     * @return string 
     */
    public function getSignalToken()
    {
        return $this->signalToken;
    }
}
