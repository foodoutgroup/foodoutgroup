<?php

namespace Food\OrderBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\OptimisticLockException;
use Food\AppBundle\Entity\Driver;
use Food\AppBundle\Service\MailService;
use Food\CartBundle\Service\CartService;
use Food\DishesBundle\Entity\Dish;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\Coupon;
use Food\OrderBundle\Entity\CouponGenerator;
use Food\OrderBundle\Entity\CouponUser;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderDeliveryLog;
use Food\OrderBundle\Entity\OrderDetails;
use Food\OrderBundle\Entity\OrderDetailsOptions;
use Food\OrderBundle\Entity\OrderExtra;
use Food\OrderBundle\Entity\OrderLog;
use Food\OrderBundle\Entity\OrderMailLog;
use Food\OrderBundle\Entity\OrderStatusLog;
use Food\OrderBundle\Entity\OrderToDriver;
use Food\OrderBundle\Entity\PaymentLog;
use Food\UserBundle\Entity\User;
use Food\UserBundle\Entity\UserAddress;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Food\OrderBundle\Service\Events\NavOrderEvent;

class OrderService extends ContainerAware
{
    private $localBiller = null;
    private $payseraBiller = null;
    private $swedbankGatewayBiller = null;

    // TODO statusu paaiskinimai
    /**
     * Naujas uzsakymas. Dar neperduotas restoranui
     * @var string
     */
    public static $status_new = "new";
    /**
     * @var string Nepavyko apmokejimas
     */
    public static $status_failed = "failed";
    public static $status_accepted = "accepted";
    public static $status_assiged = "assigned";
    public static $status_delayed = "delayed";
    public static $status_forwarded = "forwarded";
    public static $status_completed = "completed";
    public static $status_finished = "finished";
    public static $status_canceled = "canceled";
    public static $status_canceled_produced = "canceled_produced";

    /**
     * Nemaisyti su pre.. cia orderis laikui
     * @var string
     */
    public static $status_preorder = "preorder";
    public static $status_pre = "pre";
    public static $status_unapproved = "unapproved";
    public static $status_nav_problems = "nav_problems";
    public static $status_partialy_completed = "partialy_completed";

    // TODO o gal sita mapa i configa? What do You think?
    private $paymentSystemByMethod = [
        'local'                        => 'food.local_biller',
        'local.card'                   => 'food.local_biller',
        'postpaid'                     => 'food.local_biller',
        'paysera'                      => 'food.paysera_biller',
        'swedbank-gateway'             => 'food.swedbank_gateway_biller',
        'swedbank-credit-card-gateway' => 'food.swedbank_credit_card_gateway_biller',
        'seb-banklink'                 => 'food.seb_banklink_biller',
        'nordea-banklink'              => 'food.nordea_banklink_biller'
    ];

    private $onlinePayments = [
        'paysera', 'swedbank-gateway', 'swedbank-credit-card-gateway', 'seb-banklink', 'nordea-banklink'
    ];

    public static $deliveryTrans = [
        'deliver' => 'PRISTATYMAS',
        'pickup'  => 'ATSIEMIMAS'
    ];

    public static $deliveryBoth = "delivery_and_pickup";
    public static $deliveryDeliver = "deliver";
    public static $deliveryPickup = "pickup";

    /**
     * Payment did not start yet
     * @var string
     */
    public static $paymentStatusNew = "new";

    /**
     * Payment started in billing system
     * @var string
     */
    public static $paymentStatusWait = "wait";

    /**
     * Payment started in billing system and accepted. Waiting for transfer
     * @var string
     */
    public static $paymentStatusWaitFunds = "wait_funds";

    /**
     * Payment has been canceled by user or billing system
     * @var string
     */
    public static $paymentStatusCanceled = "cancel";

    /**
     * Payment completed
     * @var string
     */
    public static $paymentStatusComplete = "complete";

    /**
     * Payment raised an error
     * @var string
     */
    public static $paymentStatusError = "error";

    /**
     * @var ObjectManager
     */
    private $em;

    private $context;
    /**
     * @var \Food\UserBundle\Entity\User
     */
    private $user;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var string
     */
    private $locale;

    /**
     * @param \Food\CartBundle\Service\CartService $cartService
     */
    public function setCartService($cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * @return \Food\CartBundle\Service\CartService
     */
    public function getCartService()
    {
        return $this->cartService;
    }


    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     *
     * @return $this
     *
     * @codeCoverageIgnore
     */
    public function setEm($em)
    {
        $this->em = $em;

        return $this;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     *
     * @codeCoverageIgnore
     */
    public function getEm()
    {
        if (empty($this->em)) {
            $this->setEm($this->container->get('doctrine')->getManager());
        }

        return $this->em;
    }

    /**
     * @param \Food\UserBundle\Entity\User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return \Food\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param mixed $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    public function getEventDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     * @param int         $placeId
     * @param PlacePoint  $placePoint
     * @param boolean     $fromConsole
     * @param string|null $orderDate
     *
     * @return Order
     */
    public function createOrder($placeId, $placePoint = null, $fromConsole = false, $orderDate = null)
    {
        $placeRecord = $this->getEm()->getRepository('FoodDishesBundle:Place')->find($placeId);
        if (empty($placePoint)) {
            $placePointMap = $this->container->get('session')->get('point_data');
            $pointRecord = $this->getEm()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointMap[$placeId]);
        } else {
            $pointRecord = $placePoint;
        }

        $this->order = new Order();
        if (!$fromConsole) {
            $user = $this->container->get('security.context')->getToken()->getUser();
            if ($user == 'anon.') {
                $user = null;
            }
        } else {
            $user = null;
        }
        $this->order->setPlace($placeRecord);
        $this->order->setPlaceName($placeRecord->getName());
        $this->order->setPlacePointSelfDelivery($placeRecord->getSelfDelivery());

        $this->order->setPlacePoint($pointRecord);
        $this->order->setPlacePointCity($pointRecord->getCity());
        $this->order->setPlacePointAddress($pointRecord->getAddress());

        $this->order->setOrderDate(new \DateTime("now"));

        if (empty($orderDate)) {
            $miscService = $this->container->get('food.app.utils.misc');
            $placeService = $this->container->get('food.places');

            $timeShift = $miscService->parseTimeToMinutes($placeService->getDeliveryTime($placeRecord));

            if (empty($timeShift) || $timeShift <= 0) {
                $timeShift = 60;
            }

            $deliveryTime = new \DateTime("now");
            $deliveryTime->modify("+" . $timeShift . " minutes");
        } else {
            $deliveryTime = new \DateTime($orderDate);
        }

        $this->order->setUser($user);
        $this->order->setDeliveryTime($deliveryTime);
        $this->order->setDeliveryPrice($placeRecord->getDeliveryPrice());
        $this->order->setVat($this->container->getParameter('vat'));
        $this->order->setOrderHash(
            $this->generateOrderHash($this->order)
        );

        // Log user IP address
        if (!$fromConsole) {
            $this->order->setUserIp($this->container->get('request')->getClientIp());
        } else {
            $this->order->setUserIp('');
        }

        return $this->getOrder();
    }

    /**
     * Counts order late time at current status
     *
     * @param Order $order
     *
     * @return bool|\DateInterval
     */
    public function getLateDiff(Order $order)
    {
        switch ($order->getOrderStatus()) {
            // 5 minutes from order create
            case OrderService::$status_unapproved:
                $date = clone $order->getOrderDate();
                $date->modify('+ 5 minutes');
                break;

            // 5 minutes from order start
            case OrderService::$status_new:
            case OrderService::$status_preorder:
                $date = clone $order->getDeliveryTime();
                $date->modify('-' . $this->getDuration($order) . ' minutes');
                $date->modify('+ 5 minutes');
                break;

            // 5 minutes from order start
            case OrderService::$status_accepted:
            case OrderService::$status_finished:
                $date = clone $order->getDeliveryTime();
                $date->modify('-' . $this->getDuration($order) . ' minutes');
                $date->modify('+ 5 minutes');
                break;

            default:
                $date = clone $order->getDeliveryTime();
                break;
        }

        return $date->diff(new \DateTime());
    }

    /**
     * Checks is order is late at current status
     *
     * @param Order $order
     *
     * @return bool
     */
    public function isLate(Order $order)
    {
        switch ($order->getOrderStatus()) {
            case OrderService::$status_unapproved:
            case OrderService::$status_assiged:
            case OrderService::$status_new:
            case OrderService::$status_preorder:
                return !$this->getLateDiff($order)->invert;

            case OrderService::$status_accepted:
            case OrderService::$status_finished:
                if ($order->getDeliveryType() != 'pickup' && !$order->getPlacePointSelfDelivery()) {
                    return !$this->getLateDiff($order)->invert;
                }
                break;
        }

        return false;
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function isBigDaddyOrder(Order $order)
    {
        return $order->getTotal() >= 40;
    }

    /**
     * @param Order $order
     * here we go again.. please close your eyes.
     *
     * @return bool
     */
    public function isHesburger(Order $order)
    {
        if ($order->getPlace() && $order->getPlace() instanceof Place) {
            $place_title = mb_strtolower($order->getPlace()->getName(), 'UTF-8');
            if (strstr($place_title, 'hesbur', true)) {
                return true;
            }
        } else {
            $this->logOrder($order, 'isHesburger', 'Cannot get Place, Order ID: ' . $order->getId() . '');
        }

        return false;
    }

    /**
     * this method is checking that
     *
     * @param Order $order
     *
     * @return bool
     */
    public function deliveryComing(Order $order)
    {
        if ($order->getOrderStatus() != OrderService::$status_assiged) {
            return false;
        }
        $deliveryTime = strtotime($order->getDeliveryTime()->format("Y-m-d H:i:s"));
        $currentTime = time();
        $currentFutureTime = $currentTime + 600; // + 10 min

        if ($deliveryTime < $currentFutureTime && $deliveryTime > $currentTime) {
            return true;
        }

        return false;
    }

    /**
     * @param string      $status
     * @param string|null $source
     * @param string|null $message
     */
    protected function chageOrderStatus($status, $source = null, $message = null)
    {
        // Let's log the shit out of it
        $this->logStatusChange($this->getOrder(), $status, $source, $message);

        $this->getOrder()->setOrderStatus($status);
    }

    /**
     * @param string|null $source
     * @param string|null $statusMessage
     *
     * @return $this
     */
    public function statusUnapproved($source = null, $statusMessage = null)
    {
        $this->chageOrderStatus(self::$status_unapproved, $source, $statusMessage);

        return $this;
    }

    /**
     * @param string|null $source
     * @param string|null $statusMessage
     *
     * @return $this
     */
    public function statusNew($source = null, $statusMessage = null)
    {
        $this->chageOrderStatus(self::$status_new, $source, $statusMessage);

        return $this;
    }

    /**
     * @param string|null $source
     * @param string|null $statusMessage
     *
     * @return $this
     */
    public function statusNewPreorder($source = null, $statusMessage = null)
    {
        $this->chageOrderStatus(self::$status_preorder, $source, $statusMessage);

        return $this;
    }

    /**
     * When payment has failed
     *
     * @param string|null $source
     * @param string|null $statusMessage
     *
     * @return $this
     */
    public function statusFailed($source = null, $statusMessage = null)
    {
        $this->chageOrderStatus(self::$status_failed, $source, $statusMessage);

        return $this;
    }

    /**
     * @param string|null $source
     * @param string|null $statusMessage
     *
     * @return $this
     */
    public function statusAccepted($source = null, $statusMessage = null)
    {
        $order = $this->getOrder();
        // Inform poor user, that his order was accepted
        if (in_array($order->getOrderStatus(), [self::$status_new, self::$status_preorder])) {
            $recipient = $order->getOrderExtra()->getPhone();
            $placeService = $this->container->get('food.places');

            // SMS siunciam tik tuo atveju jei orderis ne is callcentro
            if ($order->getOrderFromNav() == false) {
                if (!empty($recipient)) {
                    $smsService = $this->container->get('food.messages');

                    $sender = $this->container->getParameter('sms.sender');

                    $translation = 'general.sms.user.order_accepted';
                    // Preorder message differs
                    if ($order->getPreorder()) {
                        $translation = 'general.sms.user.order_accepted_preorder';
                    }

                    if ($order->getDeliveryType() == self::$deliveryPickup) {
                        $translation = 'general.sms.user.order_accepted_pickup';

                        if ($order->getPreorder()) {
                            $translation = 'general.sms.user.order_accepted_pickup_preorder';
                        }
                    }

                    $placeName = $this->container->get('food.app.utils.language')
                        ->removeChars('lt', $order->getPlaceName(), false, false)
                    ;
                    $placeName = ucfirst($placeName);
                    // Hack for too long restaurant names in LT :) Sorry mates, had to do this for whale :D
                    // Add others if needed
                    if ($placeName == 'Cili GREITA (tik issinesimui)') {
                        $placeName = 'Cili GREITA';
                    }

                    $place = $order->getPlace();

                    $text = $this->container->get('translator')
                        ->trans(
                            $translation,
                            [
                                'order_id'          => $order->getId(),
                                'restourant_name'   => $placeName,
                                'delivery_time'     => ($order->getDeliveryType() == self::$deliveryDeliver ? $placeService->getDeliveryTime($place) : $place->getPickupTime()),
                                'pre_delivery_time' => ($order->getDeliveryTime()->format('m-d H:i')),
//                                'restourant_phone' => $order->getPlacePoint()->getPhone()
                            ],
                            null,
                            $order->getLocale()
                        )
                    ;

                    $message = $smsService->createMessage($sender, $recipient, $text, $order);
                    $smsService->saveMessage($message);
                }
            }

            if (!$order->getOrderFromNav()) {
                if (!$order->getPreorder()) {
                    $miscService = $this->container->get('food.app.utils.misc');

                    if ($order->getDeliveryType() == self::$deliveryPickup) {
                        $timeShift = $miscService->parseTimeToMinutes($order->getPlace()->getPickupTime());
                    } else {
                        $place = $order->getPlace();
                        $timeShift = $miscService->parseTimeToMinutes($placeService->getDeliveryTime($order->getPlace(), $order->getPlacePoint()));
                    }

                    $this->logOrder($order, 'calculating_delivery_time', 'Setting delivery time with a parsed value of ' . $timeShift . ' minutes');
                    if (empty($timeShift) || $timeShift <= 0) {
                        $timeShift = 60;
                    }

                    $dt = new \DateTime('now');
                    $dt->modify('+' . $timeShift . ' minutes');
                    $order->setDeliveryTime($dt);
                }
                $this->saveOrder();
                $this->_notifyOnAccepted();

                // Notify Dispatchers
                $this->notifyOrderAccept();
            }

            // Put for logistics
            $this->container->get('food.logistics')->putOrderForSend($order);

            // Kitais atvejais tik keiciam statusa, nes gal taip reikia
        }

        $order->setAcceptTime(new \DateTime("now"));
        $this->chageOrderStatus(self::$status_accepted, $source, $statusMessage);

        $this->updateDriver();

        $this->logDeliveryEvent($order, 'order_accepted');

        return $this;
    }

    /**
     * Inform client, that restourant accepted their order
     */
    private function _notifyOnAccepted()
    {
        $ml = $this->container->get('food.mailer');
        $placeService = $this->container->get('food.places');

        // TODO pansu, kad naujame sablone sitie nebereikalingi
        /*$userName = "";
        if ($this->getOrder()->getUser()->getFirstname()) {
            $userName = $this->getOrder()->getUser()->getFirstname();
        }
        if ($this->getOrder()->getUser()->getLastname()) {
            if (!empty($userName)) {
                $userName.= " ";
            }
            $userName.= $this->getOrder()->getUser()->getLastname();
        }
        $ordersText = "<br />";
        $ordersText.= "<ul>";*/
        $invoice = [];
        foreach ($this->getOrder()->getDetails() as $ord) {
//            $ordersText.="<li>".$ord->getDishName()." (".$ord->getQuantity()." vnt.)";
            $options = $ord->getOptions();
            $invoice[] = [
                'itm_name'   => $ord->getDishName(),
                'itm_amount' => $ord->getQuantity(),
                'itm_price'  => $ord->getPrice(),
                'itm_sum'    => $ord->getPrice() * $ord->getQuantity(),
            ];
            if (sizeof($options) > 0) {
                /*$ordersText.="<ul>";
                foreach ($options as $opt) {
                    $ordersText.="<li>".$opt->getDishOptionName()."</li>";
                }
                $ordersText.="</ul>";


                $ordersText.=" (".$this->container->get('translator')->trans('email.dishes.options').": ";*/
                foreach ($options as $k => $opt) {
                    /*if ($k !=0) {
                        $ordersText.=", ";
                    }
                    $ordersText.=$opt->getDishOptionName();*/
                    $invoice[] = [
                        'itm_name'   => "  - " . $opt->getDishOptionName(),
                        'itm_amount' => $ord->getQuantity(),
                        'itm_price'  => $opt->getPrice(),
                        'itm_sum'    => $opt->getPrice() * $ord->getQuantity(),
                    ];
                }
//                $ordersText.=")";

            }
//            $ordersText.="</li>";
        }
//        $ordersText.= "</ul>";

        /*
                $variables = array(
                    'username' => $userName,
                    'maisto_gamintojas' => $this->getOrder()->getPlace()->getName(),
                    'maisto_ruosejas' => $this->getOrder()->getPlacePoint()->getAddress(),
                    'uzsakymas' => $ordersText,
                    'order_hash' => $this->getOrder()->getOrderHash(),
                    'adresas' => ($this->getOrder()->getDeliveryType() != self::$deliveryPickup ? $this->getOrder()->getAddressId()->getAddress().", ".$this->getOrder()->getAddressId()->getCity() : "--"),
                    'pristatymo_data' => $this->getOrder()->getDeliveryTime()->format('Y-m-d H:i:s')
                );

        */

        // TODO temp Beta.lt code
        $betaCode = '';
        if ($this->container->get('food.app.utils.misc')->getParam('beta_code_on', true) == 'on') {
            // TODO Kavos akcija tik mobilkom
            if ($this->getOrder()->getMobile()) {
                $betaCode = $this->getBetaCode();
            }
        }

        $variables = [
            'maisto_gamintojas' => $this->getOrder()->getPlace()->getName(),
            'maisto_ruosejas'   => $this->getOrder()->getPlacePoint()->getAddress(),
            'uzsakymas'         => $this->getOrder()->getId(),
            'order_hash'        => $this->getOrder()->getOrderHash(),
            'adresas'           => ($this->getOrder()->getDeliveryType() != self::$deliveryPickup ? $this->getOrder()->getAddressId()->getAddress() . ", " . $this->getOrder()->getAddressId()->getCity() : "--"),
            'pristatymo_data'   => $placeService->getDeliveryTime($this->getOrder()->getPlace()),
            'total_sum'         => $this->getOrder()->getTotal(),
            'total_delivery'    => ($this->getOrder()->getDeliveryType() == self::$deliveryDeliver ? $this->getOrder()->getDeliveryPrice() : 0),
            'total_card'        => ($this->getOrder()->getDeliveryType() == self::$deliveryDeliver ? ($this->getOrder()->getTotal() - $this->getOrder()->getDeliveryPrice()) : $this->getOrder()->getTotal()),
            'invoice'           => $invoice,
            'beta_kodas'        => $betaCode,
        ];


//        $ml->setVariables( $variables )->setRecipient($this->getOrder()->getUser()->getEmail(), $this->getOrder()->getUser()->getEmail())->setId( 30009269  )->send();

        // Pickup sablonas kitoks
        if ($this->getOrder()->getDeliveryType() == self::$deliveryPickup) {
            $mailTemplate = $this->container->getParameter('mailer_notify_pickup_on_accept');

            // Cili express omg hack :( TODO isimt sita velnio ismisla ir nueit ispazinties :(
            if ($this->getOrder()->getPlace()->getId() == 142 && $this->container->getParameter('country') == 'LT') {
                $mailTemplate = 41586573;
            }
        } else {
            $mailTemplate = $this->container->getParameter('mailer_notify_on_accept');
        }

        $ml->setVariables($variables)
            ->setRecipient($this->getOrder()->getOrderExtra()->getEmail(), $this->getOrder()->getOrderExtra()->getEmail())
            ->setId($mailTemplate)
            ->send()
        ;

        $this->logMailSent(
            $this->getOrder(),
            'notify_on_accept',
            $mailTemplate,
            $variables
        );
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusAssigned($source = null, $statusMessage = null, $api = false)
    {
        // Inform poor user, that his order was accepted
        $order = $this->getOrder();
        $driver = $order->getDriver();

        if (!$api) {
            $messagingService = $this->container->get('food.messages');
            $logger = $this->container->get('logger');

            // Inform driver about new order that was assigned to him
            $orderConfirmRoute = $this->container->get('router')
                ->generate('drivermobile', ['hash' => $order->getOrderHash()], true)
            ;

            $restaurant_title = $order->getPlace()->getName();
            $internal_code = $order->getPlacePoint()->getInternalCode();
            if (!empty($internal_code)) {
                $restaurant_title = $restaurant_title . " - " . $internal_code;
            }

            $restaurant_address = $order->getAddressId()->getAddress() . " " . $order->getAddressId()->getCity();
            $pickup_restaurant_address = $order->getPlacePointAddress() . ' ' . $order->getPlacePointCity();
            $curr_locale = $this->container->getParameter('locale');
            $languageUtil = $this->container->get('food.app.utils.language');

            $messageText = $languageUtil->removeChars(
                $curr_locale,
                $this->container->get('translator')->trans(
                    'general.sms.driver_assigned_order',
                    [
                        'order_id'           => $order->getId(),
                        'restaurant_title'   => $restaurant_title,
                        'restaurant_address' => $restaurant_address,
                        'pickup_restaurant_address' => $pickup_restaurant_address,
                        'deliver_time'       => $order->getDeliveryTime()->format("H:i")
                    ]
                ) . $orderConfirmRoute,
                false
            );

            $messageText = $this->fitDriverMessage(
                $messageText,
                $order->getId(),
                $restaurant_title,
                $restaurant_address,
                $pickup_restaurant_address,
                $order->getDeliveryTime()->format("H:i"),
                $orderConfirmRoute,
                $curr_locale
            );

            $logMessage = sprintf(
                'Sending message for driver about assigned order #%d to number: %s with text "%s". Used address Id: %d',
                $order->getId(),
                $driver->getPhone(),
                $messageText,
                $order->getAddressId()->getId()
            );
            $logger->alert($logMessage);

            $message = $messagingService->createMessage(
                $this->container->getParameter('sms.sender'),
                $driver->getPhone(),
                $messageText,
                $order
            );
            $messagingService->saveMessage($message);
        }

        $this->logDeliveryEvent($this->getOrder(), 'order_assigned');

        if ($this->isLate($order)) {
            $late = $this->getLateDiff($order);
            $order->setAssignLate($late->d * 1440 + $late->h * 60 + $late->i);
        }
        $this->chageOrderStatus(self::$status_assiged, $source, $statusMessage);

        if (!$api) {
            $this->updateDriver();
        }

        return $this;
    }

    /**
     * Fit driver assign mesage to 160 chars
     *
     * @param string $messageText
     * @param int    $orderId
     * @param string $restaurantTitle
     * @param string $restaurantAddress
     * @param string $deliverTime
     * @param string $orderRoute
     * @param string $locale
     *
     * @returns string
     * @throws \Exception
     */
    public function fitDriverMessage($messageText, $orderId, $restaurantTitle, $restaurantAddress, $pickup_restaurant_address, $deliverTime, $orderRoute, $locale)
    {
        $languageUtil = $this->container->get('food.app.utils.language');

        if (strpos($messageText, 'Cili pica Kaunas/Klaipeda') !== false) {
            $messageText = str_replace('Cili pica Kaunas/Klaipeda', 'Cili pica', $messageText);
        }
        if (strpos($messageText, 'Cili Kaimas Kaunas') !== false) {
            $messageText = str_replace('Cili Kaimas Kaunas', 'Cili Kaimas', $messageText);
        }

        $max_len = 160;
        $all_message_len = mb_strlen($messageText, 'UTF-8');

        if ($all_message_len > $max_len) {
            $restaurant_title_len = mb_strlen($restaurantTitle, 'UTF-8');
            $restaurant_address_len = mb_strlen($restaurantAddress, 'UTF-8');
            $pickup_restaurant_address_len = mb_strlen($pickup_restaurant_address, 'UTF-8');
            $too_long_len = ($all_message_len - $max_len);

            if ($pickup_restaurant_address_len > 30) {
                $pickup_restaurant_address = mb_strimwidth($pickup_restaurant_address, 0, ($pickup_restaurant_address_len - $too_long_len / 2), '');
            }
            if ($restaurant_title_len > 30 && $restaurant_address_len > 30) {
                $restaurantTitle = mb_strimwidth($restaurantTitle, 0, ($restaurant_title_len - $too_long_len / 2), '');
                $restaurantAddress = mb_strimwidth($restaurantAddress, 0, ($restaurant_address_len - $too_long_len / 2), '');
            } else {
                if ($restaurant_title_len > $too_long_len) {
                    $restaurantTitle = mb_strimwidth($restaurantTitle, 0, ($restaurant_title_len - $too_long_len), '');
                } elseif ($restaurant_address_len > $too_long_len) {
                    $restaurantAddress = mb_strimwidth($restaurantAddress, 0, ($restaurant_address_len - $too_long_len), '');
                }
            }

            return $languageUtil->removeChars(
                $locale,
                $this->container->get('translator')->trans(
                    'general.sms.driver_assigned_order',
                    [
                        'order_id'           => $orderId,
                        'restaurant_title'   => $restaurantTitle,
                        'restaurant_address' => $restaurantAddress,
                        'pickup_restaurant_address' => $pickup_restaurant_address,
                        'deliver_time'       => $deliverTime
                    ]
                ) . $orderRoute,
                false
            );
        }

        return $messageText;
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusForwarded($source = null, $statusMessage = null)
    {
        $this->chageOrderStatus(self::$status_forwarded, $source, $statusMessage);

        $this->updateDriver();

        return $this;
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusCompleted($source = null, $statusMessage = null)
    {
        $order = $this->getOrder();
        $this->logDeliveryEvent($this->getOrder(), 'order_completed');
        $this->chageOrderStatus(self::$status_completed, $source, $statusMessage);
        $this->getOrder()->setCompletedTime(new \DateTime());

        $this->createDiscountCode($order);

        if ($this->getOrder()->getOrderFromNav() == false) {
            $this->container->get('food.mail')->addEmailForSend(
                $order,
                MailService::$typeCompleted,
                new \DateTime('+2 hour')
            )
            ;
        }

        // Generuojam SF skaicius tik tada, jei restoranui ijungtas fakturu siuntimas
        if ($order->getPlace()->getSendInvoice()
            && !$order->getPlacePointSelfDelivery()
            && $order->getDeliveryType() == OrderService::$deliveryDeliver
        ) {
            // Patikrinam ar sitam useriui reikia generuoti sf
            if (!$order->getUser()->getNoInvoice()) {
                $mustDoNavDelete = $this->setInvoiceDataForOrder();

                // Suplanuojam sf siuntima klientui
                $this->container->get('food.invoice')->addInvoiceToSend($order, $mustDoNavDelete);
            }
        }

        $this->updateDriver();

        return $this;
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusCanceled_produced($source = null, $statusMessage = null)
    {
        $order = $this->getOrder();
        $this->logDeliveryEvent($this->getOrder(), 'order_canceled_produced');
        $this->chageOrderStatus(self::$status_canceled_produced, $source, $statusMessage);
        $this->getOrder()->setCompletedTime(new \DateTime());
        $this->createDiscountCode($order);

        $this->updateDriver();

        return $this;
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusPartialyCompleted($source = null, $statusMessage = null)
    {
        $this->chageOrderStatus(self::$status_partialy_completed, $source, $statusMessage);

        $this->container->get('food.mail')->addEmailForSend(
            $this->getOrder(),
            MailService::$typePartialyCompleted,
            new \DateTime('+2 hour')
        )
        ;

        // Informuojam buhalterija
        $mailer = $this->container->get('mailer');
        $translator = $this->container->get('translator');
        $domain = $this->container->getParameter('domain');
        $financeEmail = $this->container->getParameter('accounting_email');
        $order = $this->getOrder();

        $message = \Swift_Message::newInstance()
            ->setSubject(
                $this->container->getParameter('title') . ': '
                . $translator->trans('general.email.partialy_completed')
                . ' (#' . $order->getId() . ')'
            )
            ->setFrom('info@' . $domain)
        ;

        $message->addTo($financeEmail);
        // Issiimti
        //$message->addCc('karolis.m@foodout.lt');

        $driver = $order->getDriver();
        if (!empty($driver)) {
            $driverName = $driver->getName();
        } else {
            $driverName = '';
        }

        $emailBody = $translator->trans('general.email.partialy_completed') . "\n\n"
            . 'Order ID: ' . $order->getId() . "\n"
            . 'Vairuotojas: ' . $driverName;

        $message->setBody($emailBody);
        $mailer->send($message);

        return $this;
    }

    /**
     * Sends client an email after order complete
     *
     * @param boolean $partialy
     *
     * @throws \Exception
     */
    public function sendCompletedMail($partialy = false)
    {
        $ml = $this->container->get('food.mailer');
        $slugUtil = $this->container->get('food.dishes.utils.slug');
        $slugUtil->setLocale($this->getOrder()->getLocale());

        // TODO darant LV - sutvarkyti URL ir sablonu ID
        $variables = [
            'maisto_gamintojas' => $this->getOrder()->getPlace()->getName(),
            'uzsakymo_nr'       => $this->getOrder()->getId(),
            'order_hash'        => $this->getOrder()->getOrderHash(),
            'miestas'           => $this->getOrder()->getPlacePoint()->getCity(),
            'maisto_review_url' => 'http://www.foodout.lt/lt/' . $slugUtil->getSlugByItem(
                    $this->getOrder()->getPlace()->getId(),
                    'place'
                ) . '/#detailed-restaurant-review'
        ];

        if ($partialy) {
            $template = $this->container->getParameter('mailer_partialy_deliverer');
            $source = 'mailer_partialy_deliverer';
        } else {
            $template = $this->container->getParameter('mailer_rate_your_food');
            $source = 'mailer_rate_your_food';
        }

        $ml->setVariables($variables)
            ->setRecipient(
                $this->getOrder()->getOrderExtra()->getEmail(),
                $this->getOrder()->getOrderExtra()->getEmail()
            )
            ->setId($template)
            ->send()
        ;

        $this->logMailSent(
            $this->getOrder(),
            $source,
            $template,
            $variables
        );
    }

    /**
     * @throws \Exception
     * @return boolean
     */
    public function setInvoiceDataForOrder()
    {
        $order = $this->getOrder();
        $mustPerformDelete = false;

        $orderSeries = $order->getSfSeries();
        $orderSfNumber = $order->getSfNumber();

        if (empty($orderSeries) || empty($orderSfNumber)) {
            $miscService = $this->container->get('food.app.utils.misc');
            $invoiceService = $this->container->get('food.invoice');

            // First try to use unused number

            try {
                $sfNumber = $invoiceService->getUnusedSfNumber();
                $mustPerformDelete = true;
            } catch (OptimisticLockException $e) {
                // It was locked.. lets take new one and dont mess with DB
                $sfNumber = null;
            } catch (\Exception $e) {
                $sfNumber = null;
                $this->container->get('logger')->error('Error while getting unused SF number: ' . $e->getMessage());
            }

            if (empty($sfNumber)) {
                // We failed. lets take a new one
                try {
                    $sfNumber = (int)$miscService->getParam('sf_next_number');
                    $miscService->setParam('sf_next_number', ($sfNumber + 1));
                    $this->logOrder($order, 'sf_number_assign', 'Assigning new SF number: ' . $sfNumber);
                } catch (OptimisticLockException $e) {
                    sleep(1);
                    $sfNumber = (int)$miscService->getParam('sf_next_number');
                    $miscService->setParam('sf_next_number', ($sfNumber + 1));
                    $this->logOrder($order, 'sf_number_assign', 'Assigning new SF number: ' . $sfNumber);
                }
            } else {
                // Log da shit for debuging purposes
                $this->logOrder($order, 'sf_number_assign', 'Assigning old unused SF number: ' . $sfNumber);
            }

            $order->setSfSeries($this->container->getParameter('invoice.series'));
            $order->setSfNumber($sfNumber);

            $this->saveOrder();

            return $mustPerformDelete;
        }
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusFinished($source = null, $statusMessage = null)
    {
        $this->chageOrderStatus(self::$status_finished, $source, $statusMessage);
        $this->logDeliveryEvent($this->getOrder(), 'order_finished');

        $this->updateDriver();

        return $this;
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusCanceled($source = null, $statusMessage = null)
    {
        // Put for logistics to cancel on their side
        $this->container->get('food.logistics')->putOrderForSend($this->getOrder());

        // Importuotiems is 1822 nesiunciame cancel
        if ($this->getOrder()->getOrderFromNav() == false) {
            $this->informPaidOrderCanceled();
        }

        $this->logDeliveryEvent($this->getOrder(), 'order_canceled');

        $this->chageOrderStatus(self::$status_canceled, $source, $statusMessage);

        $this->updateDriver();

        return $this;
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusDelayed($source = null, $statusMessage = null)
    {
        $this->chageOrderStatus(self::$status_delayed, $source, $statusMessage);

        $this->logDeliveryEvent($this->getOrder(), 'order_delayed');

        // Inform logistics
        $this->container->get('food.logistics')->putOrderForSend($this->getOrder());

        $this->updateDriver();

        return $this;
    }

    /**
     * @return Order
     * @throws \Exception
     */
    public function getOrder()
    {
        if (empty($this->order)) {
            $e = new \Exception("Dude - no order here :)");
            // Log this shit, as this happens alot so we need info to debug
            $this->container->get('logger')->error(
                $e->getMessage() . "\nTrace: " . $e->getTraceAsString()
            )
            ;
            throw $e;
        }

        return $this->order;
    }

    /**
     * @param Order $order
     *
     * @throws \InvalidArgumentException
     */
    public function setOrder($order)
    {
        if (empty($order)) {
            throw new \InvalidArgumentException("An empty variable is not allowed on our company!");
        }
        if (!($order instanceof Order)) {
            throw new \InvalidArgumentException("This is not an order, You gave me!");
        }

        $this->order = $order;
    }

    /**
     * @param \Food\UserBundle\Entity\User $user
     * @param array                        $location
     * @param UserAddress                  $userAddress
     *
     * @return UserAddress
     */
    public function createAddressFromLocation($user, $location, $comment = null)
    {
        $params = [
            'user'    => $user,
            'cities'  => $location['city_id'],
            'address' => $location['address'],
        ];
        if ($this->container->getParameter('neighbourhoods')) {
            if (empty($location['neighbourhood_id'])) {
                throw new \InvalidArgumentException("An empty variable is not allowed on our company!");
            }
            $params['neighbourhood'] = $location['neighbourhood_id'];
        }

        $userAddress = $this->getEm()
            ->getRepository('Food\UserBundle\Entity\UserAddress')
            ->findOneBy($params)
        ;

        if (!$userAddress) {
            $userAddress = new UserAddress();
        }

        $userAddress->setUser($user)
            ->setCities($this->getEm()->getRepository('FoodAppBundle:Cities')->find($location['city_id']))
            ->setAddress($location['address'])
            ->setComment($comment)
        ;
        if ($this->container->getParameter('neighbourhoods')) {
            $userAddress->setNeighbourhood($this->getEm()->getRepository('FoodAppBundle:Neighbourhood')->find($location['neighbourhood_id']));
        }

        $this->getEm()->persist($userAddress);
        $this->getEm()->flush();

        return $userAddress;
    }

    /**
     * @param \Food\UserBundle\Entity\User $user
     * @param string                       $city
     * @param string                       $address
     * @param string                       $lat
     * @param string                       $lon
     * @param string                       $comment
     *
     * @return UserAddress
     */
    public function createAddressMagic($user, $city, $address, $lat, $lon, $comment = null)
    {
        $userAddress = $this->getEm()
            ->getRepository('Food\UserBundle\Entity\UserAddress')
            ->findOneBy([
                'user'    => $user,
                'city'    => $city,
                'address' => $address,
            ])
        ;

        if (!$userAddress) {
            $userAddress = new UserAddress();
        }

        $userAddress->setUser($user)
            ->setCity($city)
            ->setAddress($address)
            ->setLat($lat)
            ->setLon($lon)
            ->setComment($comment)
        ;

        $this->getEm()->persist($userAddress);
        $this->getEm()->flush();

        return $userAddress;
    }

    /**
     * @param int                          $place
     * @param string                       $locale
     * @param \Food\UserBundle\Entity\User $user
     * @param PlacePoint                   $placePoint - placePoint, jei atsiima pats
     * @param bool                         $selfDelivery - ar klientas atsiims pats?
     * @param Coupon|null                  $coupon
     * @param array|null                   $userData
     * @param string|null                  $orderDate
     */
    public function createOrderFromCart($place, $locale = 'lt', $user, PlacePoint $placePoint = null, $selfDelivery = false, $coupon = null, $userData = null, $orderDate = null)
    {
        $this->createOrder($place, $placePoint, false, $orderDate);
        $this->getOrder()->setDeliveryType(($selfDelivery ? 'pickup' : 'deliver'));
        $this->getOrder()->setLocale($locale);
        $this->getOrder()->setUser($user);

        $placeObject = $this->container->get('food.places')->getPlace($place);

        if (!empty($orderDate)) {
            $this->getOrder()->setOrderStatus(self::$status_preorder)
                ->setPreorder(true)
            ;
        } else if (empty($orderDate) && $selfDelivery) {
            // Lets fix pickup situation
            $miscService = $this->container->get('food.app.utils.misc');

            $timeShift = $miscService->parseTimeToMinutes($placeObject->getPickupTime());

            if (empty($timeShift) || $timeShift <= 0) {
                $timeShift = 60;
            }

            $deliveryTime = new \DateTime("now");
            $deliveryTime->modify("+" . $timeShift . " minutes");
            $this->getOrder()->setDeliveryTime($deliveryTime);
        }

        $this->saveOrder();


        // save extra order data to separate table
        $orderExtra = new OrderExtra();
        $orderExtra->setOrder($this->getOrder());
        $orderExtra->setMetaData($_SERVER['HTTP_USER_AGENT']);

        if (!empty($userData)) {
            $orderExtra->setFirstname($userData['firstname'])
                ->setLastname($userData['lastname'])
                ->setPhone($userData['phone'])
                ->setEmail($userData['email'])
            ;
        } else {
            $orderExtra->setFirstname($user->getFirstname())
                ->setLastname($user->getLastname())
                ->setPhone($user->getPhone())
                ->setEmail($user->getEmail())
            ;
        }

        $this->getOrder()->setOrderExtra($orderExtra);

        $sumTotal = 0;

        $preSum = $this->getCartService()->getCartTotal($this->getCartService()->getCartDishes($placeObject));

        $deliveryPrice = $this->getCartService()->getDeliveryPrice(
            $this->getOrder()->getPlace(),
            $this->container->get('food.location')->getLocationFromSession(),
            $this->getOrder()->getPlacePoint()
        )
        ;

        // Pritaikom nuolaida
        $discountPercent = 0;
        $discountSum = 0;
        $self_delivery = $this->getOrder()->getPlace()->getSelfDelivery();

        $includeDelivery = true;
        if (!empty($coupon) && $coupon instanceof Coupon) {
            $order = $this->getOrder();
            $order->setCoupon($coupon)
                ->setCouponCode($coupon->getCode())
            ;

            $discountSize = $coupon->getDiscount();

            if (!empty($discountSize)) {
                $discountSum = $this->getCartService()->getTotalDiscount($this->getCartService()->getCartDishes($placeObject), $discountSize);
                $discountPercent = $discountSize;
            } else {
                $discountSum = $coupon->getDiscountSum();
            }

            $order->setDiscountSize($discountSize)
                ->setDiscountSum($discountSum)
            ;

            if ($coupon->getFreeDelivery()) {
                $deliveryPrice = 0;
            }


            if ($coupon->getIgnoreCartPrice() && !$coupon->getFreeDelivery()
                || !$coupon->getIncludeDelivery()
            ) {
                $includeDelivery = false;
            }

        } elseif ($user->getIsBussinesClient()) {
            // Jeigu musu logistika, tada taikom fiksuota nuolaida
            if ($self_delivery == 0) {
                $discountSize = $this->container->get('food.user')->getDiscount($user);
                $discountSum = $this->getCartService()->getTotalDiscount($this->getCartService()->getCartDishes($placeObject), $discountSize);
                $discountPercent = $discountSize;
                $this->getOrder()
                    ->setDiscountSize($discountSize)
                    ->setDiscountSum($discountSum)
                ;
            }
        }

        /**
         * Na daugiau kintamuju jau nebesugalvojau :/
         */
        $discountOverTotal = 0;
        if ($discountSum > $preSum) {
            $discountOverTotal = $discountSum - $preSum;
            $discountSum = $preSum;
        }
        $discountSumLeft = $discountSum;
        $discountSumTotal = $discountSum;
        $discountUsed = 0;
        $relationPart = $discountSum / $preSum;
        foreach ($this->getCartService()->getCartDishes($placeObject) as $cartDish) {
            $options = $this->getCartService()->getCartDishOptions($cartDish);
            $price = $cartDish->getDishSizeId()->getCurrentPrice();
            $origPrice = $cartDish->getDishSizeId()->getPrice();
            $discountPercentForInsert = 0;
            if ($cartDish->getIsFree()) {
                $price = 0;
                $origPrice = $cartDish->getDishSizeId()->getCurrentPrice();
            } else {
                if ($origPrice == $price && $discountPercent > 0) {
                    $price = round($origPrice * ((100 - $discountPercent) / 100), 2);
                    $discountPercentForInsert = $discountPercent;
                } elseif ($discountSumLeft > 0) {
                    /**
                     * Uz toki graba ash degsiu pragare.... :/
                     */
                    $priceForInsert = $price;
                    $discountPart = (float)round($price * $cartDish->getQuantity() * $relationPart * 100, 2) / 100;
                    if ($discountPart < $discountSumLeft) {
                        $discountSum = $discountPart;
                    } else {
                        if ($discountUsed + $discountPart > $discountSumTotal) {
                            $discountSum = $discountSumTotal - $discountUsed;
                        } else {
                            $discountSum = $discountSumLeft;
                        }
                    }
                    $discountSum = (float)round($discountSum / $cartDish->getQuantity() * 100, 2) / 100;
                    $priceForInsert = $price - $discountSum;
                    $discountSumLeft = $discountSumLeft - $discountSum;
                    $discountUsed = $discountUsed + $discountSum;
                    $price = $priceForInsert;
                }
            }

            $dish = new OrderDetails();

            $dish->setDishId($cartDish->getDishId())
                ->setOrderId($this->getOrder())
                ->setQuantity($cartDish->getQuantity())
                ->setDishSizeCode($cartDish->getDishSizeId()->getCode())
                ->setPrice($price)
                ->setOrigPrice($origPrice)
                ->setPercentDiscount($discountPercentForInsert)
                ->setDishName($cartDish->getDishId()->getName())
                ->setDishUnitId($cartDish->getDishSizeId()->getUnit()->getId())
                ->setDishUnitName($cartDish->getDishSizeId()->getUnit()->getName())
                ->setDishSizeCode($cartDish->getDishSizeId()->getCode())
                ->setIsFree($cartDish->getIsFree())
            ;
            $this->getEm()->persist($dish);
            $this->getEm()->flush();

            $sumTotal += $cartDish->getQuantity() * $price;

            foreach ($options as $opt) {
                $orderOpt = new OrderDetailsOptions();
                $orderOpt->setDishOptionId($opt->getDishOptionId())
                    ->setDishOptionCode($opt->getDishOptionId()->getCode())
                    ->setDishOptionName($opt->getDishOptionId()->getName())
                    ->setPrice($opt->getDishOptionId()->getPrice())
                    ->setDishId($cartDish->getDishId())
                    ->setOrderId($this->getOrder())
                    ->setQuantity($cartDish->getQuantity())// @todo Kolkas paveldimas. Veliau taps valdomas kiekvienam topingui atskirai
                    ->setOrderDetail($dish)
                ;
                $this->getEm()->persist($orderOpt);
                $this->getEm()->flush();

                $sumTotal += $cartDish->getQuantity() * $opt->getDishOptionId()->getPrice();
            }
        }

        // Nemokamas pristatymas dideliam krepseliui
        $miscService = $this->container->get('food.app.utils.misc');
        $enable_free_delivery_for_big_basket = $miscService->getParam('enable_free_delivery_for_big_basket');
        if($enable_free_delivery_for_big_basket) {
            $enable_free_delivery_for_big_basket = $this->getOrder()->getPlace()->isAllowFreeDelivery();
        }
        $free_delivery_price = $miscService->getParam('free_delivery_price');
        if($enable_free_delivery_for_big_basket) {
            $placeMinimumFreeDeliveryPrice = $this->getOrder()->getPlace()->getMinimumFreeDeliveryPrice();
            if ($placeMinimumFreeDeliveryPrice) {
                $free_delivery_price = $placeMinimumFreeDeliveryPrice;
            }
        }

        $self_delivery = $this->getOrder()->getPlace()->getSelfDelivery();
        $left_sum = 0;
        if ($enable_free_delivery_for_big_basket) {
            // Kiek liko iki nemokamo pristatymo
            if ($free_delivery_price > $sumTotal) {
                $left_sum = sprintf('%.2f', $free_delivery_price - $sumTotal);
            }
            // Krepselio suma pasieke nemokamo pristatymo suma
            if ($left_sum == 0) {
                $deliveryPrice = 0;
            }
        }

        // jei ignoruoti pristatymo min krepseli bet yra pristatymas mokamas
        if ($includeDelivery) {
            if ($discountOverTotal > 0) {
                $deliveryPrice = $deliveryPrice - $discountOverTotal;
                if ($deliveryPrice < 0) {
                    $deliveryPrice = 0;
                }
            }
        }

        if (!$selfDelivery) {
            $sumTotal += $deliveryPrice;
        } else {
            $deliveryPrice = 0;
        }
        $this->getOrder()->setDeliveryPrice($deliveryPrice);
        $this->getOrder()->setTotal($sumTotal);
        $this->saveOrder();
    }

    public function markOrderForNav(Order $order = null)
    {
        $event = new NavOrderEvent($order);

        $this->getEventDispatcher()
            ->dispatch(NavOrderEvent::MARK_ORDER, $event)
        ;
    }

    /**
     * @throws \Exception
     */
    public function saveOrder()
    {
        if (empty($this->order) || $this->order == null) {
            throw new \Exception("Yah whatever... seivinam orderi neturedami jo ?:)");
        } else {
            //Update the last update time ;)
            $this->order->setLastUpdated(new \DateTime("now"));
            $this->getEm()->persist($this->order);
            $this->getEm()->flush();

            $this->markOrderForNav($this->order);
        }
    }

    /**
     * @param int $id
     *
     * @return Order|false
     */
    public function getOrderById($id)
    {
        $em = $this->container->get('doctrine')->getManager();
        $order = $em->getRepository('Food\OrderBundle\Entity\Order')->find($id);

        if (!$order) {
            return false;
        }

        $this->order = $order;

        return $this->order;
    }

    /**
     * @param string $hash
     *
     * @throws \Exception
     * @return Order|false
     */
    public function getOrderByHash($hash)
    {
        $em = $this->container->get('doctrine')->getManager();
        $order = $em->getRepository('Food\OrderBundle\Entity\Order')->findBy(['order_hash' => $hash], null, 1);

        if (!$order) {
            return false;
        }

        if (count($order) > 1) {
            throw new \Exception('More then one order found. How the hell? Hash: ' . $hash);
        }

        // TODO negrazu, bet laikina :(
        $this->order = $order[0];

        return $this->order;
    }

    /**
     * @param int $id Nav delivery Order Id
     *
     * @throws \Exception
     * @return Order|false
     */
    public function getOrderByNavDeliveryId($id)
    {
        $em = $this->container->get('doctrine')->getManager();
        $order = $em->getRepository('Food\OrderBundle\Entity\Order')
            ->findOneBy(
                ['navDeliveryOrder' => $id], null, 1
            )
        ;

        if (!$order) {
            return false;
        }

        $this->order = $order;

        return $this->order;
    }

    /**
     * @param null|LocalBiller $localBiller
     */
    public function setLocalBiller($localBiller)
    {
        $this->localBiller = $localBiller;
    }

    /**
     * @return LocalBiller
     */
    public function getLocalBiller()
    {
        if (empty($this->localBiller)) {
            $this->localBiller = new LocalBiller();
        }

        return $this->localBiller;
    }

    /**
     * @param null|PaySera $payseraBiller
     */
    public function setPayseraBiller($payseraBiller)
    {
        $this->payseraBiller = $payseraBiller;
    }

    /**
     * @return PaySera
     */
    public function getPayseraBiller()
    {
        if (empty($this->payseraBiller)) {
            $this->payseraBiller = new PaySera();
        }

        return $this->payseraBiller;
    }

    public function getSwedbankGatewayBiller()
    {
        if (empty($this->swedbankGatewayBiller)) {
            $this->swedbankGatewayBiller = new SwedbankGatewayBiller();
        }

        return $this->swedbankGatewayBiller;
    }

    /**
     * @param string $type
     *
     * @return BillingInterface
     */
    public function getBillingInterface($type = 'local')
    {
        switch ($type) {
            case 'local':
                return $this->getLocalBiller();

            case 'swedbank-gateway':
                return $this->getSwedbankGatewayBiller();

            case 'paysera':
            default:
                return $this->getPayseraBiller();
        }
    }

    /**
     * @param int|null    $orderId [optional] Order ID if should be loading a new one
     * @param string|null $billingType [optional] Billing type if should use another then saved in order
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public function billOrder($orderId = null, $billingType = null)
    {
        if (empty($orderId)) {
            $order = $this->getOrder();
        } else {
            $order = $this->getOrderById($orderId);
            if (!$order) {
                throw new \InvalidArgumentException(
                    sprintf('Order %d not found. Can not bill without an order', $orderId)
                );
            }
        }

        if (empty($billingType)) {
            $biller = $this->getPaymentSystemByMethod($order->getPaymentMethod());
        } else {
            $biller = $this->getBillingInterface($billingType);
        }

        $biller->setOrder($order);
        $biller->setLocale($this->getLocale());
        $redirectUrl = $biller->bill();

        $order->setSubmittedForPayment(new \DateTime("now"));

        $this->saveOrder();

        $this->logPayment($order, 'billing start', 'Billing started with method: ' . $billingType, $order);

        return $redirectUrl;
    }

    /**
     * @param string $method
     *
     * @throws \InvalidArgumentException
     */
    public function setPaymentMethod($method)
    {
        $order = $this->getOrder();

        if (!$this->isAvailablePaymentMethod($method)) {
            throw new \InvalidArgumentException('Payment method: ' . $method . ' is unknown to our system or not available');
        }

        $oldMethod = $order->getPaymentMethod();
        $order->setPaymentMethod($method);

        $this->logPayment($order, 'payement method change', sprintf('Method changed from "%s" to "%s"', $oldMethod, $method));
    }

    public function setMobileOrder($isMobile = true)
    {
        $order = $this->getOrder();
        $order->setMobile($isMobile);
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function isAvailablePaymentMethod($method)
    {
        $paymentMethods = $this->container->getParameter('payment.methods');

        if (in_array($method, $paymentMethods)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $method
     *
     * @return BillingInterface
     *
     * @throws \InvalidArgumentException
     */
    public function getPaymentSystemByMethod($method)
    {
        if (isset($this->paymentSystemByMethod[$method]) && !empty($this->paymentSystemByMethod[$method])) {
            $class = $this->paymentSystemByMethod[$method];
        } else {
            throw new \InvalidArgumentException('Sorry, no map for method "' . $method . '"');
        }

        return $this->container->get($class);
    }

    /**
     * @param string      $status Payment status
     * @param string|null $message [optional] Error message
     *
     * @throws \InvalidArgumentException
     */
    public function setPaymentStatus($status, $message = null)
    {
        $order = $this->getOrder();
        $this->setPaymentStatusWithoutSave($order, $status, $message);
        $this->saveOrder();
    }

    /**
     * @param Order  $order
     * @param string $status
     * @param string $message
     *
     * @throws \InvalidArgumentException
     */
    public function setPaymentStatusWithoutSave($order, $status, $message = null)
    {
        $this->logOrder($order, 'payment_status_change', sprintf('From %s to %s', $order->getPaymentStatus(), $status));

        if (!$this->isAllowedPaymentStatus($status)) {
            throw new \InvalidArgumentException('Status: "' . $status . '" is not a valid order payment status');
        }

        if (!$this->isValidPaymentStatusChange($order->getPaymentStatus(), $status)) {
            throw new \InvalidArgumentException('Order can not go from status: "' . $order->getPaymentStatus() . '" to: "' . $status . '" is not a valid order payment status');
        }

        $oldStatus = $order->getPaymentStatus();
        $order->setPaymentStatus($status);

        if ($status == self::$paymentStatusError) {
            $order->setLastPaymentError($message);
        }

        $this->logPaymentWithoutSave(
            $order,
            'payement status change',
            sprintf('Status changed from "%s" to "%s" with message %s',
                $oldStatus,
                $status,
                $message)
        );
    }

    /**
     * @return array
     */
    public function getAllowedPaymentStatuses()
    {
        return [
            self::$paymentStatusNew,
            self::$paymentStatusWait,
            self::$paymentStatusWaitFunds,
            self::$paymentStatusComplete,
            self::$paymentStatusCanceled,
            self::$paymentStatusError,
        ];
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function isValidPaymentStatusChange($from, $to)
    {
        if (empty($from) && !empty($to)) {
            return true;
        }

        if (empty($to)) {
            return false;
        }

        $flowLine = [
            self::$paymentStatusNew       => 0,
            self::$paymentStatusWait      => 1,
            self::$paymentStatusWaitFunds => 1,
            self::$paymentStatusCanceled  => 1,
            self::$paymentStatusComplete  => 2,
            self::$paymentStatusError     => 2,
        ];

        if (!isset($flowLine[$from]) || !isset($flowLine[$to])) {
            return false;
        }

        if ($flowLine[$from] <= $flowLine[$to]) {
            return true;
        }

        return false;
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function isValidOrderStatusChange($from, $to)
    {
        $flowLine = [
            self::$status_preorder           => 0,
            self::$status_unapproved         => 0,
            self::$status_new                => 1,
            self::$status_accepted           => 2,
            self::$status_delayed            => 3,
            self::$status_forwarded          => 3,
            self::$status_finished           => 4,
            self::$status_assiged            => 5,
            self::$status_failed             => 5,
            self::$status_partialy_completed => 6,
            self::$status_completed          => 6,
            self::$status_canceled           => 6,
            self::$status_canceled_produced  => 6,
        ];

        if (empty($from) && !empty($to)) {
            return true;
        }

        if (empty($to)) {
            return false;
        }

        if (!isset($flowLine[$from]) || !isset($flowLine[$to])) {
            return false;
        }

        if ($from == $to) {
            return false;
        }

        if ($flowLine[$from] <= $flowLine[$to]) {
            return true;
        }

        return false;
    }

    public function isValidOrderStatusChangeWhenCompleted($from, $to)
    {
        $fromCompleted = $from == self::$status_completed;
        $toFailed = $to == self::$status_failed;
        $toCancelled = $to == self::$status_canceled;

//        if ($fromCompleted && ($toFailed || $toCancelled)) {
        if ($fromCompleted && ($toFailed)) {
            return true;
        }

        return false;
    }

    /**
     * @param string|null $status
     *
     * @return bool
     */
    public function isAllowedPaymentStatus($status)
    {
        if (in_array($status, $this->getAllowedPaymentStatuses())) {
            return true;
        }

        return false;
    }

    /**
     * @param Order $order
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function generateOrderHash($order)
    {
        if (empty($order) || !($order instanceof Order)) {
            throw new \InvalidArgumentException('Sorry, no order given, or this is not an order. I feel like in Sochi');
        }

        $user = $order->getUser();
        if (empty($user) || (!$user instanceof User)) {
            $userString = 'anonymous_' . mt_rand(0, 50);
        } else {
            $userString = $user->getId();
        }

        $hash = md5(
            $userString . $order->getOrderDate()->getTimestamp() . $order->getAddressId() . microtime()
        );

        return $hash;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isValidDeliveryType($type)
    {
        if (in_array($type, [self::$deliveryDeliver, self::$deliveryPickup])) {
            return true;
        }

        return false;
    }

    /**
     * @param string $type
     *
     * @throws \InvalidArgumentException
     */
    public function setDeliveryType($type)
    {
        if (empty($type)) {
            throw new \InvalidArgumentException('Delivery type must be set! You gave - empty');
        }

        $order = $this->getOrder();

        if (!$this->isValidDeliveryType($type)) {
            throw new \InvalidArgumentException('Delivery type: "' . $type . '" is unknown or not allowed');
        }

        $order->setDeliveryType($type);
    }

    /**
     * @param Order|null|false $order
     * @param string           $event
     * @param string|null      $message
     * @param mixed            $debugData
     */
    public function logOrder($order = null, $event, $message = null, $debugData = null)
    {
        $log = new OrderLog();

        if (empty($order) && !($order instanceof Order)) {
            $order = $this->getOrder();
        }

        $token = $this->container->get('security.context')->getToken();
        if ($token instanceof TokenInterface) {
            $user = $token->getUser();
        } else {
            $user = 'anon.';
        }

        if ($user == 'anon.') {
            $user = null;
        }

        $log->setOrder($order)
            ->setOrderStatus($order->getOrderStatus())
            ->setEvent($event)
            ->setMessage($message)
            ->setUser($user)
        ;

        if (is_array($debugData)) {
            $debugData = var_export($debugData, true);
        } else if (is_object($debugData)) {
            if (method_exists($debugData, '__toArray')) {
                $debugData = 'Class: ' . get_class($debugData) . ' Data: '
                    . var_export($debugData->__toArray(), true);
            } else {
                $debugData = get_class($debugData);
            }
        }
        $log->setDebugData($debugData);

        $this->getEm()->persist($log);
        $this->getEm()->flush();
    }

    /**
     * @param Order|null  $order
     * @param string      $event
     * @param string|null $message
     * @param mixed       $debugData
     */
    public function logPayment($order = null, $event, $message = null, $debugData = null)
    {
        $this->logPaymentWithoutSave($order, $event, $message, $debugData);
        $this->getEm()->flush();
    }

    public function logPaymentWithoutSave($order = null, $event, $message = null, $debugData = null)
    {
        $log = new PaymentLog();

        if (empty($order) && !($order instanceof Order)) {
            $order = $this->getOrder();
        }

        $user = $this->container->get('security.context')->getToken()->getUser();

        if ($user == 'anon.') {
            $user = null;
        }

        $log->setOrder($order)
            ->setPaymentStatus($order->getPaymentStatus())
            ->setEvent($event)
            ->setMessage($message)
            ->setUser($user)
        ;

        if (is_array($debugData)) {
            $debugData = var_export($debugData, true);
        } else if (is_object($debugData)) {
            if (method_exists($debugData, '__toArray')) {
                $debugData = 'Class: ' . get_class($debugData) . ' Data: '
                    . var_export($debugData->__toArray(), true);
            } else {
                $debugData = get_class($debugData);
            }
        }
        $log->setDebugData($debugData);

        $this->getEm()->persist($log);
    }

    /**
     * @param Driver $driver
     *
     * @return array|\Food\OrderBundle\Entity\Order[]
     */
    public function getOrdersForDriver($driver)
    {
        $em = $this->container->get('doctrine')->getManager();
        $orders = $em->getRepository('Food\OrderBundle\Entity\Order')
            ->findBy([
                'driver'       => $driver,
                'order_status' => self::$status_assiged,
            ])
        ;

        if (!$orders) {
            return [];
        }

        return $orders;
    }

    /**
     * Send a message to place about new order
     *
     * @param boolean $isReminder Is this a new order or is this a reminder?
     */
    public function informPlace($isReminder = false)
    {
        $order = $this->getOrder();

        if (in_array(
            $order->getOrderStatus(),
            [OrderService::$status_pre, OrderService::$status_unapproved]
        )) {
            return;
        }

        // Preorder tik navision siunciam i NAV info, o paprastus restoranus informuos cronas
        if ($order->getOrderStatus() == OrderService::$status_preorder && !$order->getPlace()->getNavision()) {
            return;
        }

        // Inform by email about create and if Nav - send it to Nav
        if (!$isReminder) {
            $this->notifyOrderCreate();
        }

        $messagingService = $this->container->get('food.messages');
        $translator = $this->container->get('translator');
        $logger = $this->container->get('logger');
        $miscUtils = $this->container->get('food.app.utils.misc');
        $country = $this->container->getParameter('country');

        $placePoint = $order->getPlacePoint();
        $placePointEmail = $placePoint->getEmail();
        $placePointAltEmail1 = $placePoint->getAltEmail1();
        $placePointAltEmail2 = $placePoint->getAltEmail2();

        $domain = $this->container->getParameter('domain');

        // Inform restourant about new order

        if ($isReminder) {
            $orderConfirmRoute = 'http://' . $domain
                . $this->container->get('router')
                    ->generate('ordermobile', ['hash' => $order->getOrderHash()])
            ;

            $orderSmsTextTranslation = $translator->trans('general.sms.order_reminder', [
                'order_id' => $order->getId()
            ]);
            $orderTextTranslation = $translator->trans('general.email.order_reminder');
        } else {
            // Jei preorder - sms siuncia cronas ir nezino apie esama domena..
            if ($order->getPreorder()) {
                $orderConfirmRoute = 'http://' . $domain
                    . $this->container->get('router')
                        ->generate('ordermobile', ['hash' => $order->getOrderHash()])
                ;
            } else {
                $orderConfirmRoute = $this->container->get('router')
                    ->generate('ordermobile', ['hash' => $order->getOrderHash()], true)
                ;
            }

            $orderSmsTextTranslation = $translator->trans('general.sms.new_order', [
                'order_id' => $order->getId()
            ]);
            $orderTextTranslation = $translator->trans('general.email.new_order');
        }

        $messageText = $orderSmsTextTranslation . ' ' . $orderConfirmRoute;

        // Jei placepoint turi emaila - vadinas siunciam jiems emaila :)
        if (!empty($placePointEmail)) {
            $logger->alert('--- Place asks for email, so we have sent an email about new order to: ' . $placePointEmail);
            $emailMessageText = $messageText;
            $emailMessageText .= "\n" . $orderTextTranslation . ': '
                . $order->getPlacePoint()->getAddress() . ', ' . $order->getPlacePoint()->getCity();
            // Buvo liepta padaryti, kad sms'u eitu tas pats, kas emailu. Pasiliekam, o maza kas
//            $messageText = $translator->trans('general.sms.new_order_in_mail');

            $mailer = $this->container->get('mailer');

            $message = \Swift_Message::newInstance()
                ->setSubject($this->container->getParameter('title') . ': ' . $translator->trans('general.sms.new_order'))
                ->setFrom('info@' . $domain)
            ;

            $message->addTo($placePointEmail);

            if (!empty($placePointAltEmail1)) {
                $message->addCc($placePointAltEmail1);
            }
            if (!empty($placePointAltEmail2)) {
                $message->addCc($placePointAltEmail2);
            }

            $message->setBody($emailMessageText);
            $mailer->send($message);
        }

        $smsSenderNumber = $this->container->getParameter('sms.sender');

        // Siunciam SMS tik tuo atveju, jei neperduodam per Nav'a
        if (!$order->getPlace()->getNavision()) {
            $messagesToSend = [];

            $orderMessageRecipients = [
                $placePoint->getPhone(),
                $placePoint->getAltPhone1(),
                $placePoint->getAltPhone2(),
            ];

            foreach ($orderMessageRecipients as $nr => $phone) {
                // Siunciam sms'a jei jis ne landline
                if (!empty($phone) && $miscUtils->isMobilePhone($phone, $country)) {
                    $logger->alert("Sending message for order #" . $order->getId() . " to be accepted to number: " . $phone . ' with text "' . $messageText . '"');

                    $messagesToSend[] = [
                        'sender'    => $smsSenderNumber,
                        'recipient' => $phone,
                        'text'      => $messageText,
                        'order'     => $order,
                    ];
                } else if ($nr == 0) {
                    // Main phone is not mobile
                    $logger->alert('Main phone number for place point of place ' . $placePoint->getPlace()->getName() . ' is set landline - no message sent');
                }
            }

            //send multiple messages
            $messagingService->addMultipleMessagesToSend($messagesToSend);
        }

        if (!$order->getOrderFromNav()) {
            $messagesToSend = [];
            $dispatcherPhones = $this->container->getParameter('dispatcher_phones');
            // If dispatcher phones are set - send them message about new order
            if (!empty($dispatcherPhones) && is_array($dispatcherPhones)) {
                $dispatcherMessageText = $translator->trans('general.sms.dispatcher_order', [
                    'order_id'   => $order->getId(),
                    'place_name' => $order->getPlaceName(),
                ]);

                foreach ($dispatcherPhones as $phoneNum) {
                    $logger->alert("Sending message to dispatcher about order #" . $order->getId() . " to number: " . $phoneNum . ' with text "' . $dispatcherMessageText . '"');

                    $messagesToSend[] = [
                        'sender'    => $smsSenderNumber,
                        'recipient' => $phoneNum,
                        'text'      => $dispatcherMessageText,
                        'order'     => $order,
                    ];
                }

                $messagingService->addMultipleMessagesToSend($messagesToSend);
            }
        }

        $this->getOrder()->setPlaceInformed(true);
        $this->saveOrder();
    }

    /**
     * Inform dispatchers that unapproved order is waiting and needs attention
     */
    public function informUnapproved()
    {
        $order = $this->getOrder();

        if (empty($order) || !$order instanceof Order) {
            throw new \Exception('No order, dude, can not inform about unapproved');
        }
        $logger = $this->container->get('logger');

        $logger->alert('Informing dispatcher and other personel about unapproved order');

        $translator = $this->container->get('translator');

        $domain = $this->container->getParameter('domain');
        $notifyEmails = $this->container->getParameter('order.notify_emails');
        $cityCoordinators = $this->container->getParameter('order.city_coordinators');
        $dispatchers = $this->container->getParameter('order.accept_notify_emails');

        $userAddress = '';
        $userAddressObject = $order->getAddressId();

        if (!empty($userAddressObject) && is_object($userAddressObject)) {
            $userAddress = $order->getAddressId()->getAddress() . ', ' . $order->getAddressId()->getCity();
        }

        $newOrderText = $translator->trans('general.new_unapproved_order.title');

        $emailMessageText = $newOrderText . ' ' . $order->getPlace()->getName() . "\n"
            . "OrderId: " . $order->getId() . "\n\n"
            . $translator->trans('general.new_order.selected_place_point') . ": " . $order->getPlacePoint()->getAddress() . ', ' . $order->getPlacePoint()->getCity() . "\n"
            . $translator->trans('general.new_order.place_point_phone') . ":" . $order->getPlacePoint()->getPhone() . "\n"
            . "\n"
            . $translator->trans('general.new_order.client_name') . ": " . $order->getUser()->getFirstname() . ' ' . $order->getUser()->getLastname() . "\n"
            . $translator->trans('general.new_order.client_address') . ": " . $userAddress . "\n"
            . $translator->trans('general.new_order.client_phone') . ": " . $order->getOrderExtra()->getPhone() . "\n"
            . $translator->trans('general.new_order.client_email') . ": " . $order->getOrderExtra()->getEmail() . "\n"
            . "\n"
            . $translator->trans('general.new_order.delivery_type') . ": " . $order->getDeliveryType() . "\n"
            . $translator->trans('general.new_order.payment_type') . ": " . $order->getPaymentMethod() . "\n"
            . $translator->trans('general.new_order.payment_status') . ": " . $order->getPaymentStatus() . "\n";

        $mailer = $this->container->get('mailer');

        $message = \Swift_Message::newInstance()
            ->setSubject($newOrderText . ': ' . $order->getPlace()->getName() . ' (#' . $order->getId() . ')')
            ->setFrom('info@' . $domain)
        ;

        if (!empty($cityCoordinators)) {
            if (isset($cityCoordinators[mb_strtolower($order->getPlacePointCity(), 'UTF-8')])) {
                $notifyEmails = array_merge(
                    $notifyEmails,
                    $cityCoordinators[mb_strtolower($order->getPlacePointCity(), 'UTF-8')]
                );
            }
        }

        $notifyEmails = array_merge(
            $notifyEmails,
            $dispatchers
        );

        $this->addEmailsToMessage($message, $notifyEmails);

        $message->setBody($emailMessageText);
        $mailer->send($message);
    }

    /**
     * @return void
     */
    public function informPlaceCancelAction()
    {
        $messagingService = $this->container->get('food.messages');
        $translator = $this->container->get('translator');
        $logger = $this->container->get('logger');
        $miscUtils = $this->container->get('food.app.utils.misc');
        $country = $this->container->getParameter('country');

        $order = $this->getOrder();

        if ($order->getPlaceInformed()) {
            $placePoint = $order->getPlacePoint();
            $placePointEmail = $placePoint->getEmail();
            $placePointAltEmail1 = $placePoint->getAltEmail1();
            $placePointAltEmail2 = $placePoint->getAltEmail2();
            $placePointAltPhone1 = $placePoint->getAltPhone1();
            $placePointAltPhone2 = $placePoint->getAltPhone2();

            $domain = $this->container->getParameter('domain');

            $orderConfirmRoute = $this->container->get('router')
                ->generate('ordermobile', ['hash' => $order->getOrderHash()], true)
            ;

            $orderSmsTextTranslation = $translator->trans('general.sms.canceled_order', ['order_id' => $order->getId()]);
            $orderTextTranslation = $translator->trans('general.email.canceled_order');

            $messageText = $orderSmsTextTranslation . ' ' . $orderConfirmRoute;

            // Jei placepoint turi emaila - vadinas siunciam jiems emaila :)
            if (!empty($placePointEmail)) {
                $logger->alert('--- Place asks for email, so we have sent an email about canceled order to: ' . $placePointEmail);
                $emailMessageText = $messageText;
                $emailMessageText .= "\n" . $orderTextTranslation . ': '
                    . $order->getPlacePoint()->getAddress() . ', ' . $order->getPlacePoint()->getCity();
                $mailer = $this->container->get('mailer');

                $message = \Swift_Message::newInstance()
                    ->setSubject($this->container->getParameter('title') . ': ' . $translator->trans('general.sms.canceled_order', ['order_id' => $order->getId()]))
                    ->setFrom('info@' . $domain)
                ;

                $message->addTo($placePointEmail);

                if (!empty($placePointAltEmail1)) {
                    $message->addCc($placePointAltEmail1);
                }
                if (!empty($placePointAltEmail2)) {
                    $message->addCc($placePointAltEmail2);
                }

                $message->setBody($emailMessageText);
                $mailer->send($message);
            }

            if (!$order->getPlace()->getNavision()) {
                // Siunciam sms'a
                $logger->alert("Sending message for order to be accepted to number: " . $placePoint->getPhone() . ' with text "' . $messageText . '"');
                $smsSenderNumber = $this->container->getParameter('sms.sender');

                $messagesToSend = [
                    [
                        'sender'    => $smsSenderNumber,
                        'recipient' => $placePoint->getPhone(),
                        'text'      => $messageText,
                        'order'     => $order,
                    ]
                ];

                if (!empty($placePointAltPhone1) && $miscUtils->isMobilePhone($placePointAltPhone1, $country)) {
                    $messagesToSend[] = [
                        'sender'    => $smsSenderNumber,
                        'recipient' => $placePointAltPhone1,
                        'text'      => $messageText,
                        'order'     => $order,
                    ];
                }
                if (!empty($placePointAltPhone2) && $miscUtils->isMobilePhone($placePointAltPhone2, $country)) {
                    $messagesToSend[] = [
                        'sender'    => $smsSenderNumber,
                        'recipient' => $placePointAltPhone2,
                        'text'      => $messageText,
                        'order'     => $order,
                    ];
                }

                //send multiple messages
                $messagingService->addMultipleMessagesToSend($messagesToSend);
            }
        }
    }

    /**
     * Inform admins when paid order was canceled by place - maby we should refund, or maby not
     */
    public function informPaidOrderCanceled()
    {
        $order = $this->getOrder();

        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('No order set. Cant check if it is canceled or what..');
        }

        // Order is post-paid - skip it
        if (in_array($order->getPaymentMethod(), ['local', 'local.card'])) {
            return;
        }

        $translator = $this->container->get('translator');

        $domain = $this->container->getParameter('domain');
        $notifyEmails = $this->container->getParameter('order.notify_emails');
        $cityCoordinators = $this->container->getParameter('order.city_coordinators');

        $emailSubject = $translator->trans('general.canceled_order.title');
        $emailMessageText = $emailSubject . "\n\n"
            . "OrderId: " . $order->getId() . "\n\n"
            . $translator->trans('general.new_order.selected_place_point') . ": " . $order->getPlacePoint()->getAddress() . ', ' . $order->getPlacePoint()->getCity() . "\n"
            . $translator->trans('general.new_order.place_point_phone') . ":" . $order->getPlacePoint()->getPhone() . "\n"
            . "\n"
            . $translator->trans('general.new_order.client_name') . ": " . $order->getOrderExtra()->getFirstname() . ' ' . $order->getOrderExtra()->getLastname() . "\n"
            . $translator->trans('general.new_order.client_phone') . ": " . $order->getOrderExtra()->getPhone() . "\n"
            . "\n"
            . $translator->trans('general.new_order.delivery_type') . ": " . $order->getDeliveryType() . "\n"
            . $translator->trans('general.new_order.payment_type') . ": " . $order->getPaymentMethod() . "\n"
            . $translator->trans('general.new_order.payment_status') . ": " . $order->getPaymentStatus() . "\n";

        $emailMessageText .= "\n"
            . $translator->trans('general.new_order.admin_link') . ": "
            . 'http://' . $domain . $this->container->get('router')
                ->generate('order_support_mobile', ['hash' => $order->getOrderHash()], false)
            . "\n";

        $mailer = $this->container->get('mailer');

        $message = \Swift_Message::newInstance()
            ->setSubject($emailSubject . ': ' . $order->getPlace()->getName() . ' (#' . $order->getId() . ')')
            ->setFrom('info@' . $domain)
        ;

        if (!empty($cityCoordinators)) {
            if (isset($cityCoordinators[mb_strtolower($order->getPlacePointCity(), 'UTF-8')])) {
                $notifyEmails = array_merge(
                    $notifyEmails,
                    $cityCoordinators[mb_strtolower($order->getPlacePointCity(), 'UTF-8')]
                );
            }
        }

        $this->addEmailsToMessage($message, $notifyEmails);

        $message->setBody($emailMessageText);
        $mailer->send($message);
    }

    /**
     * @param \Swift_Mime_SimpleMessage $message
     * @param array                     $emails
     */
    public function addEmailsToMessage(\Swift_Mime_SimpleMessage $message, $emails)
    {
        $mainEmailSet = false;
        foreach ($emails as $email) {
            if (!$mainEmailSet) {
                $mainEmailSet = true;
                $message->addTo($email);
            } else {
                $message->addCc($email);
            }
        }
    }

    /**
     * For debuging purpose only!
     */
    public function notifyOrderCreate()
    {
        $order = $this->getOrder();

        if ($order->getPlace()->getNavision()) {
            $nav = $this->container->get('food.nav');
            $orderRenew = $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order')->find($order->getId());


            $query = "SELECT * FROM order_details WHERE order_id=" . $order->getId();
            $stmt = $this->container->get('doctrine')->getConnection()->prepare($query);
            $stmt->execute();
            $details = $stmt->fetchAll();
            foreach ($details as $det) {
                $orderRenew->addDetail(
                    $this->container->get('doctrine')->getRepository('FoodOrderBundle:OrderDetails')->find($det['id'])
                );
            }

            $this->logOrder($order, 'NAV_put_order');
            $nav->putTheOrderToTheNAV($orderRenew);

            $this->container->get('doctrine')->getManager()->refresh($orderRenew);

            sleep(1);
            $this->logOrder($order, 'NAV_update_prices');
            $returner = $nav->updatePricesNAV($orderRenew);
            sleep(1);
            $this->logOrder($order, 'NAV_update_prices_return', 'returner', $returner->return_value);
            if ($returner->return_value == "TRUE") {
                $this->logOrder($order, 'NAV_process_order');
                $returner = $nav->processOrderNAV($orderRenew);
                if ($returner->return_value == "TRUE") {

                } else {
                    // Problems processing order in nav
                    $order = $this->getEm()->getRepository('FoodOrderBundle:Order')->find($order->getId());
                    $this->getEm()->refresh($order);
                    $this->logStatusChange($order, self::$status_nav_problems, 'cili_nav_process');
                    $order->setOrderStatus(self::$status_nav_problems);
                    $this->getEm()->persist($order);
                    $this->getEm()->flush();
                }
            } else {
                // Problems updating price
                $order = $this->getEm()->getRepository('FoodOrderBundle:Order')->find($order->getId());
                $this->getEm()->refresh($order);
                $this->logStatusChange($order, self::$status_nav_problems, 'cili_nav_update_price');
                $order->setOrderStatus(self::$status_nav_problems);
                $this->getEm()->persist($order);
                $this->getEm()->flush();
            }
        }

        $translator = $this->container->get('translator');

        $domain = $this->container->getParameter('domain');
        $notifyEmails = $this->container->getParameter('order.notify_emails');
        $cityCoordinators = $this->container->getParameter('order.city_coordinators');

        $userAddress = '';
        $userAddressObject = $order->getAddressId();

        if (!empty($userAddressObject) && is_object($userAddressObject)) {
            $userAddress = $order->getAddressId()->getAddress() . ', ' . $order->getAddressId()->getCity();
        }

        $newOrderText = $translator->trans('general.new_order.title');

        $emailMessageText = $newOrderText . ' ' . $order->getPlace()->getName() . "\n"
            . "OrderId: " . $order->getId() . "\n\n"
            . $translator->trans('general.new_order.selected_place_point') . ": " . $order->getPlacePoint()->getAddress() . ', ' . $order->getPlacePoint()->getCity() . "\n"
            . $translator->trans('general.new_order.place_point_phone') . ":" . $order->getPlacePoint()->getPhone() . "\n"
            . "\n"
            . $translator->trans('general.new_order.client_name') . ": " . $order->getUser()->getFirstname() . ' ' . $order->getUser()->getLastname() . "\n"
            . $translator->trans('general.new_order.client_address') . ": " . $userAddress . "\n"
            . $translator->trans('general.new_order.client_phone') . ": " . $order->getOrderExtra()->getPhone() . "\n"
            . $translator->trans('general.new_order.client_email') . ": " . $order->getOrderExtra()->getEmail() . "\n"
            . "\n"
            . $translator->trans('general.new_order.delivery_type') . ": " . $order->getDeliveryType() . "\n"
            . $translator->trans('general.new_order.payment_type') . ": " . $order->getPaymentMethod() . "\n"
            . $translator->trans('general.new_order.payment_status') . ": " . $order->getPaymentStatus() . "\n";

        $emailMessageText .= "\n"
            . $translator->trans('general.new_order.restaurant_link') . ": " . $this->container->get('router')
                ->generate('ordermobile', ['hash' => $order->getOrderHash()], true)
            . "\n";
        $emailMessageText .= "\n"
            . $translator->trans('general.new_order.admin_link') . ": " . $this->container->get('router')
                ->generate('order_support_mobile', ['hash' => $order->getOrderHash()], true)
            . "\n";

        $mailer = $this->container->get('mailer');

        $message = \Swift_Message::newInstance()
            ->setSubject($newOrderText . ': ' . $order->getPlace()->getName() . ' (#' . $order->getId() . ')')
            ->setFrom('info@' . $domain)
        ;

        if (!empty($cityCoordinators)) {
            if (isset($cityCoordinators[mb_strtolower($order->getPlacePointCity(), 'UTF-8')])) {
                $notifyEmails = array_merge(
                    $notifyEmails,
                    $cityCoordinators[mb_strtolower($order->getPlacePointCity(), 'UTF-8')]
                );
            }
        }

        // Turn on only if debug needed
//        if ($order->getPlace()->getNavision()) {
//            $notifyEmails = array_merge(
//                $notifyEmails,
//                $this->container->getParameter('admin.emails')
//            );
//        }

        $this->addEmailsToMessage($message, $notifyEmails);

        $message->setBody($emailMessageText);
        $mailer->send($message);
    }

    /**
     * For debuging purpose only!
     */
    public function notifyOrderAccept()
    {
        $order = $this->getOrder();

        if ($order->getDeliveryType() == 'pickup') {
            // no email for dispatcher if uster picks up by himself
            return;
        }

        if ($order->getPlacePointSelfDelivery() == true) {
            // if place delivers by themselves - why bother dispatcher
            return;
        }

        $translator = $this->container->get('translator');

        $domain = $this->container->getParameter('domain');
        $notifyEmails = $this->container->getParameter('order.accept_notify_emails');

        $userAddress = '';
        $userAddressObject = $order->getAddressId();

        if (!empty($userAddressObject) && is_object($userAddressObject)) {
            $userAddress = $order->getAddressId()->getAddress() . ', ' . $order->getAddressId()->getCity();
        }

        $driverUrl = $this->container->get('router')
            ->generate('drivermobile', ['hash' => $order->getOrderHash()], true)
        ;

        $newOrderText = $translator->trans('general.new_order.title');

        $emailMessageText = $newOrderText . ' ' . $order->getPlace()->getName() . "\n"
            . "OrderId: " . $order->getId() . "\n\n"
            . $translator->trans('general.new_order.selected_place_point') . ": " . $order->getPlacePoint()->getAddress() . ', ' . $order->getPlacePoint()->getCity() . "\n"
            . $translator->trans('general.new_order.place_point_phone') . ":" . $order->getPlacePoint()->getPhone() . "\n"
            . "\n"
            . $translator->trans('general.new_order.client_name') . ": " . $order->getUser()->getFirstname() . ' ' . $order->getUser()->getLastname() . "\n"
            . $translator->trans('general.new_order.client_address') . ": " . $userAddress . "\n"
            . $translator->trans('general.new_order.client_phone') . ": " . $order->getOrderExtra()->getPhone() . "\n"
            . $translator->trans('general.new_order.client_email') . ": " . $order->getOrderExtra()->getEmail() . "\n"
            . "\n"
            . $translator->trans('general.new_order.delivery_type') . ": " . $order->getDeliveryType() . "\n"
            . $translator->trans('general.new_order.payment_type') . ": " . $order->getPaymentMethod() . "\n"
            . $translator->trans('general.new_order.payment_status') . ": " . $order->getPaymentStatus() . "\n"
            . "\n"
            . $translator->trans('general.new_order.driver_link') . ": " . $driverUrl;

        $mailer = $this->container->get('mailer');

        $message = \Swift_Message::newInstance()
            ->setSubject($newOrderText . ': ' . $order->getPlace()->getName())
            ->setFrom('info@' . $domain)
        ;

        $this->addEmailsToMessage($message, $notifyEmails);

        $message->setBody($emailMessageText);
        $mailer->send($message);
    }

    /**
     * @param Order       $order
     * @param string      $newStatus
     * @param null|string $source
     * @param null|string $message
     */
    public function logStatusChange($order = null, $newStatus, $source = null, $message = null)
    {
        $log = new OrderStatusLog();
        $log->setOrder($order)
            ->setEventDate(new \DateTime('now'))
            ->setOldStatus($order->getOrderStatus())
            ->setNewStatus($newStatus)
            ->setSource($source)
            ->setMessage($message)
        ;

        $this->getEm()->persist($log);
        $this->getEm()->flush();
    }

    public function sentToDriver($order)
    {

    }

    public function setAutoAssignedDriver($driver)
    {
        $order = $this->getOrder();
        if ($order->getOrderStatus() == self::$status_accepted) {
            $this->getOrder()->setDriver($driver);
            $order->setDriverAutoAssigned(true);
            $this->statusAssigned('API', 'auto_assigned', true);
            $this->saveOrder();
        } else {
            throw new \Exception('Driver already set');
        }
    }

    /**
     * @param Order  $order
     * @param string $event
     */
    public function logDeliveryEvent($order = null, $event)
    {
        try {
            $sinceLast = 0;
            // TODO paskutinio evento laika paimam ir paskaiciuojam diffa sekundziu tikslumu - uzsakaugom prie logo legvesnei matkei
            switch ($event) {
                case 'order_accepted':
                    $sinceLast = date("U") - $order->getOrderDate()->getTimestamp();
                    break;

                case 'order_delayed':
                case 'order_finished':
                    $logData = $this->getDeliveryLogActionEntry($order, 'order_accepted');

                    if ($logData instanceof OrderDeliveryLog) {
                        $sinceLast = date("U") - $logData->getEventDate()->getTimestamp();
                    } else {
                        $sinceLast = $order->getOrderDate()->getTimestamp();
                    }
                    break;


                case 'order_assigned':
                    $logData = $this->getDeliveryLogActionEntry($order, 'order_finished');

                    if (!$logData || !$logData instanceof OrderDeliveryLog) {
                        $logData = $this->getDeliveryLogActionEntry($order, 'order_accepted');
                    }

                    if ($logData instanceof OrderDeliveryLog) {
                        $sinceLast = date("U") - $logData->getEventDate()->getTimestamp();
                    } else {
                        $sinceLast = $order->getOrderDate()->getTimestamp();
                    }
                    break;

                case 'order_pickedup':
                    $logData = $this->getDeliveryLogActionEntry($order, 'order_assigned');

                    if ($logData instanceof OrderDeliveryLog) {
                        $sinceLast = date("U") - $logData->getEventDate()->getTimestamp();
                    } else {
                        $sinceLast = $order->getOrderDate()->getTimestamp();
                    }
                    break;

                case 'order_completed':
                    $logData = $this->getDeliveryLogActionEntry($order, 'order_assigned');

                    if (!$logData || !$logData instanceof OrderDeliveryLog) {
                        $logData = $this->getDeliveryLogActionEntry($order, 'order_accepted');
                    }

                    if ($logData instanceof OrderDeliveryLog) {
                        $sinceLast = date("U") - $logData->getEventDate()->getTimestamp();
                    } else {
                        $sinceLast = $order->getOrderDate()->getTimestamp();
                    }
                    break;

                case 'order_canceled':
                    $logData = $this->getDeliveryLogActionEntry($order, 'order_accepted');

                    if ($logData instanceof OrderDeliveryLog) {
                        $sinceLast = date("U") - $logData->getEventDate()->getTimestamp();
                    } else {
                        $sinceLast = $order->getOrderDate()->getTimestamp();
                    }
                    break;

                case 'order_canceled_produced':
                    $logData = $this->getDeliveryLogActionEntry($order, 'order_accepted');

                    if ($logData instanceof OrderDeliveryLog) {
                        $sinceLast = date("U") - $logData->getEventDate()->getTimestamp();
                    } else {
                        $sinceLast = $order->getOrderDate()->getTimestamp();
                    }
                    break;
            }

            $log = new OrderDeliveryLog();
            $log->setOrder($order)
                ->setEventDate(new \DateTime('now'))
                ->setEvent($event)
                ->setSinceLast($sinceLast)
            ;

            $this->getEm()->persist($log);
            $this->getEm()->flush();
        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error happened: ' . $e->getMessage());
        }
    }

    /**
     * @param Order  $order
     * @param string $event
     *
     * @return OrderDeliveryLog
     */
    public function getDeliveryLogActionEntry($order, $event)
    {
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('Not an order given. Can not retriev delivery data');
        }
        if (empty($event)) {
            throw new \InvalidArgumentException('No event given - can not retrieve delivery data');
        }

        $repo = $this->container->get('doctrine')->getRepository('FoodOrderBundle:OrderDeliveryLog');

        return $repo->findOneBy([
            'order' => $order,
            'event' => $event
        ]);
    }

    /**
     * @param Order       $order
     * @param string      $source
     * @param null|string $params
     */
    public function logMailSent($order, $source, $template, $params = null)
    {
        $log = new OrderMailLog();
        $log->setOrder($order)
            ->setEventDate(new \DateTime('now'))
            ->setSource($source)
            ->setTemplate($template)
            ->setParams(var_export($params, true))
        ;

        $this->getEm()->persist($log);
        $this->getEm()->flush();
    }

    /**
     * Returns all available order statuses
     *
     * @return array
     */
    public static function getOrderStatuses()
    {
        return [
            self::$status_preorder,
            self::$status_unapproved,
            self::$status_new,
            self::$status_accepted,
            self::$status_delayed,
            self::$status_forwarded,
            self::$status_finished,
            self::$status_assiged,
            self::$status_completed,
            self::$status_partialy_completed,
            self::$status_canceled,
            self::$status_canceled_produced,
        ];
    }

    /**
     * Returns all available payment statuses
     *
     * @return array
     */
    public static function getPaymentStatuses()
    {
        return [
            self::$paymentStatusNew,
            self::$paymentStatusWait,
            self::$paymentStatusWaitFunds,
            self::$paymentStatusCanceled,
            self::$paymentStatusComplete,
            self::$paymentStatusError,
        ];
    }


    /**
     * @param PlacePoint $placePoint
     * @param array      $errors
     *
     * @todo fix laiku poslinkiai
     */
    private function workTimeErrors(PlacePoint $placePoint, &$errors, $dateTime = null)
    {
        if ($dateTime) {
            $ts = strtotime($dateTime);
        } else {
            $ts = time();
        }

        $wd = date('w', $ts);
        if ($wd == 0) $wd = 7;
        $hour = date('H', $ts);
        $minute = date('i', $ts);

        $todayError = $openError = $closeError = true;
        if (!$this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:Place')->isPlacePointWorks($placePoint, $ts)) {

            foreach ($placePoint->getWorkTimes() as $workTime) {
                if ($workTime->getWeekDay() == $wd) {
                    $todayError = false;
                    if ($workTime->getStartHour() < $hour || $workTime->getStartHour() == $hour && $workTime->getStartMin() < $minute) {
                        $openError = false;
                    }
                    if ($workTime->getStartHour() > $hour || $workTime->getStartHour() == $hour && $workTime->getStartMin() > $minute) {
                        $closeError = false;
                    }
                }
            }
            if ($todayError) {
                $errors[] = "order.form.errors.today_no_work";
            } elseif ($openError) {
                $errors[] = "order.form.errors.isnt_open";
            } elseif ($closeError) {
                $errors[] = "order.form.errors.is_already_close";
            } else {
                $errors[] = "order.form.errors.is_currently_close";
            }
        }
    }

    /**
     * @param PlacePoint $placePoint
     *
     * @return mixed|string
     */
    public function workTimeErrorsReturn(PlacePoint $placePoint)
    {
        $errors = [];
        $this->workTimeErrors($placePoint, $errors);
        if (!empty($errors)) {
            return end($errors);
        }

        return "";
    }

    /**
     * @param Place $place
     *
     * @return bool
     */
    public function isTodayNoOneWantsToWork(Place $place)
    {
        $returner = true;
        foreach ($place->getPoints() as $point) {
            if ($point->getActive()) {
                if ($this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->isPlacePointWorks($point)) {
                    $returner = false;
                    break;
                }
            }
        }

        return $returner;
    }

    /**
     * @param Place $place
     *
     * @return bool
     */
    public function isTodayWorkDayForAll(Place $place)
    {
        $returner = false;
        $works = 0;
        $total = 0;
        foreach ($place->getPoints() as $point) {
            if ($point->getActive()) {
                $total++;
                if ($this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->isPlacePointWorks($point)) {
                    $works++;
                }
            }
        }
        if ($total == $works) {
            $returner = true;
        }

        return $returner;
    }

    /**
     * @param Place $place
     *
     * @return string
     */
    public function notWorkingPlacesPoints(Place $place)
    {
        $returner = '<div>';
        foreach ($place->getPoints() as $point) {
            if ($point->getActive()) {
                $returner .= $point->getAddress() . " ";
                if ($this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->isPlacePointWorks($point)) {
                    $returner .= '<span class="work-green">' . $this->getTodayWork($point, false) . "</span>";
                } else {
                    $returner .= '<span class="work-red">' . $this->getTodayWork($point, false) . "</span> " . $this->container->get('translator')->trans($this->workTimeErrorsReturn($point));
                }
                $returner .= "<br />";
            }
        }
        $returner .= "</div>";

        return $returner;
    }

    /**
     * @param PlacePoint $placePoint
     * @param bool       $showDayNumber
     *
     * @return string
     */
    public function getTodayWork(PlacePoint $placePoint, $showDayNumber = true)
    {
        $placeService = $this->container->get('food.places');
        $locale = $this->container->get('food.dishes.utils.slug')->getLocale();
        $wdays = [
            '1' => 'I',
            '2' => 'II',
            '3' => 'III',
            '4' => 'IV',
            '5' => 'V',
            '6' => 'VI',
            '7' => 'VII',
        ];
        $wd = date('w');
        if ($wd == 0) $wd = 7;
        $workTime = $placePoint->{'getWd' . $wd}();
        $workTime = preg_replace('~\s*-\s*~', '-', $workTime);
        $intervals = explode(' ', $workTime);
        $times = [];
        foreach ($intervals as $interval) {
            if ($workTimes = $placeService->parseIntervalToTimes($interval)) {
                list($startHour, $startMin, $endHour, $endMin) = $workTimes;
                if ('fa' == $locale) {
                    array_unshift($times, sprintf("%02d:%02d-%02d:%02d", $endHour, $endMin, $startHour, $startMin));
                } else {
                    $times[] = sprintf("%02d:%02d-%02d:%02d", $startHour, $startMin, $endHour, $endMin);
                }
            } elseif ('fa' == $locale) {
                array_unshift($times, $interval);
            } else {
                $times[] = $interval;
            }
        }
        $time = implode(' ', $times);
        if ($locale == 'fa') {
            return $time . ($showDayNumber ? " " . $wdays[$wd] : "");
        }

        return ($showDayNumber ? $wdays[$wd] . " " : "") . $time;
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $place
     * @param Request                         $request
     * @param                                 $formHasErrors
     * @param                                 $formErrors
     * @param                                 $takeAway
     * @param null|int                        $placePointId
     * @param Coupon|null                     $coupon
     * @param                                 $isCallcenter
     */
    public function validateDaGiantForm(Place $place, Request $request, &$formHasErrors, &$formErrors, $takeAway, $placePointId = null, $coupon = null, $isCallcenter = false)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $noMinimumCart = ($user instanceof User ? $user->getNoMinimumCart() : false);
        $locationService = $this->container->get('food.location');
        $dishesService = $this->container->get('food.dishes');
        $loggedIn = true;
        $phonePass = false;
        $list = $this->getCartService()->getCartDishes($place);
        $total_cart = $this->getCartService()->getCartTotal($list/*, $place*/);

        $customerEmail = $request->get('customer-email');

        if (!$isCallcenter) {
            if (0 === strlen($customerEmail)) {
                $formErrors[] = 'order.form.errors.customeremail';
            } else {
                $emailConstraint = new EmailConstraint();
                $emailConstraint->message = 'Email invalid';

                $emailErrors = $this->container->get('validator')->validateValue(
                    $customerEmail,
                    $emailConstraint
                )
                ;

                if ($emailErrors->count() > 0) {
                    $formErrors[] = 'order.form.errors.customeremail_invalid';
                }
            }
        }

        // Validate bussines client
        if (!$user instanceof User) {
            $loggedIn = false;
            $user = $this->container->get('fos_user.user_manager')->findUserByEmail($customerEmail);
        }
        if ($user instanceof User) {
            if ($user->getIsBussinesClient()) {
                // Bussines client must be logged in
                if (!$loggedIn) {
                    $formErrors[] = 'order.form.errors.bussines_client_not_loggedin';
                } elseif ($user->getRequiredDivision()) {
                    // Bussines client must enter correct division code
                    $givenDivisionCode = $request->get('company_division_code', '');
                    if (!empty($givenDivisionCode)) {
                        $correctDivisionCodes = $user->getDivisionCodes();
                        $codeCorrect = false;

                        foreach ($correctDivisionCodes as $divisionCode) {
                            if ($divisionCode == $givenDivisionCode) {
                                $codeCorrect = true;
                                break;
                            }
                        }

                        if (!$codeCorrect) {
                            $formErrors[] = 'order.form.errors.division_code_incorrect';
                        }
                    } else {
                        $formErrors[] = 'order.form.errors.empty_division_code';
                    }
                }
                if (!$takeAway && !$place->getSelfDelivery()) {
                    $discountSize = $this->container->get('food.user')->getDiscount($user);
                    $total_cart -= $this->getCartService()->getTotalDiscount($this->getCartService()->getCartDishes($place), $discountSize);
                }
            }
        }

        $paymentType = $request->get('payment-type');
        if (0 === strlen($paymentType)) {
            $formErrors[] = 'order.form.errors.payment_type';
        } elseif ($paymentType == 'postpaid' && (!($user instanceof User) || $user->getIsBussinesClient() && !$user->getAllowDelayPayment())) {
            $formErrors[] = 'order.form.errors.payment_type';
        }

        if ($couponCode = $request->get('coupon_code', false)) {
            $coupon = $this->getCouponByCode($couponCode);
            if (empty($coupon) || !$coupon instanceof Coupon) {
                $formErrors[] = 'general.coupon.not_active';
            } elseif (!$this->validateCouponForPlace($coupon, $place)
                || $coupon->getOnlyNav() && !$place->getNavision()
                || $coupon->getNoSelfDelivery() && $place->getSelfDelivery()
            ) {
                $formErrors[] = 'general.coupon.wrong_place_simple';
            } elseif (!$coupon->isAllowedForWeb()) {
                $formErrors[] = 'general.coupon.only_api';
            } elseif (!$takeAway && !$coupon->isAllowedForDelivery()) {
                $formErrors[] = 'general.coupon.only_pickup';
            } elseif ($takeAway && !$coupon->isAllowedForPickup()) {
                $formErrors[] = 'general.coupon.only_delivery';
            } elseif (!empty($user) && $user instanceof User && $user->getIsBussinesClient() && $coupon->getB2b() == Coupon::B2B_NO) {
                $formErrors[] = 'general.coupon.not_for_business';
            } elseif ($coupon->getB2b() == Coupon::B2B_YES
                && (empty($user) || !empty($user) && $user instanceof User && !$user->getIsBussinesClient())
            ) {
                $formErrors[] = 'general.coupon.only_for_business';
            } elseif ($coupon->getSingleUsePerPerson() && !empty($user) && $user instanceof User && $this->isCouponUsed($coupon, $user)) {
                $formErrors[] = 'general.coupon.not_active';
            } elseif ($coupon->getOnlinePaymentsOnly() && !$this->isOnlinePayment($paymentType)) {
                $formErrors[] = 'general.coupon.online_payments_only';
            } else {
                $discountSize = $coupon->getDiscount();
                if (!empty($discountSize)) {
                    $total_cart -= $this->getCartService()->getTotalDiscount($this->getCartService()->getCartDishes($place), $discountSize);
                } elseif (!$coupon->getFullOrderCovers()) {
                    $total_cart -= $coupon->getDiscountSum();
                }
            }

            if ($coupon->getEnableValidateDate()) {
                $now = date('Y-m-d H:i:s');
                if ($coupon->getValidFrom()->format('Y-m-d H:i:s') > $now) {
                    $formErrors[] = 'general.coupon.coupon_too_early';
                }

                if ($coupon->getValidTo()->format('Y-m-d H:i:s') < $now) {
                    $formErrors[] = 'general.coupon.coupon_expired';
                }
            }

            if ($coupon->getValidHourlyFrom() && $coupon->getValidHourlyFrom() > new \DateTime()) {
                $formErrors[] = 'general.coupon.coupon_too_early';
            }
            if ($coupon->getValidHourlyTo() && $coupon->getValidHourlyTo() < new \DateTime()) {
                $formErrors[] = 'general.coupon.coupon_expired';
            }
        }

        if ($coupon && $coupon->getIgnoreCartPrice()) {
            $noMinimumCart = true;
        }

        if (!$takeAway) {
            foreach ($list as $itm) {
                if (!$this->isOrderableByTime($itm->getDishId())) {
                    $formErrors[] = [
                        'message' => 'order.form.errors.dont_make_item',
                        'text'    => $itm->getDishId()->getName()
                    ];
                }
            }

            $placePointMap = $this->container->get('session')->get('point_data');

            $locationData = $locationService->getLocationFromSession();

            if (empty($locationData) && $user instanceof User) {
                $locationData = $locationService->setLocationFromUser($user);
                //@mail("karolis.m@foodout.lt", "order.form.errors.customeraddr2" . date("Y-m-d H:i:s"), print_r($locationData, true) . print_r($user, true) . print_r($placePointMap, true) . print_r($_POST, true) . print_r($_GET, true), "FROM: info@foodout.lt");
            }
            // TODO Trying to catch fatal when searching for PlacePoint
            if (!empty($locationData['address_orig'])) {
                if (empty($placePointMap[$place->getId()])) {
                    $this->container->get('logger')->alert('Trying to find PlacePoint without ID in OrderService - validateDaGiantForm fix part 1');
                    // Mapping not found, lets try to remap
                    $placePointId = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->getPlacePointNear($place->getId(), $locationData);
                    $placePointMap[$place->getId()] = $placePointId;
                    $this->container->get('session')->set('point_data', $placePointMap);
                }
            } else {
                //@mail("karolis.m@foodout.lt", "order.form.errors.customeraddr1" . date("Y-m-d H:i:s"), print_r($locationData, true) . print_r($user->getUsername(), true) . print_r($placePointMap, true) . print_r($_POST, true) . print_r($_GET, true), "FROM: info@foodout.lt");
                $formErrors[] = 'order.form.errors.customeraddr';
            }

            if (isset($placePointMap[$place->getId()])) {
                /**
                 * @todo Possible problems in the future here :)
                 */
                $pointRecord = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointMap[$place->getId()]);
                $cartMinimum = $this->getCartService()->getMinimumCart(
                    $place,
                    $locationService->getLocationFromSession(),
                    $pointRecord
                )
                ;

                if ($total_cart < $cartMinimum && $noMinimumCart == false) {
                    $formErrors[] = 'order.form.errors.cartlessthanminimum';
                }
            }
        } elseif ($place->getMinimalOnSelfDel()) {
            foreach ($list as $itm) {
                if (!$this->isOrderableByTime($itm->getDishId())) {
                    $formErrors[] = [
                        'message' => 'order.form.errors.dont_make_item',
                        'text'    => $itm->getDishId()->getName()
                    ];
                }
            }

            if ($total_cart < $place->getCartMinimum() && $noMinimumCart == false) {
                $formErrors[] = 'order.form.errors.cartlessthanminimum_on_pickup';
            }
        }

        foreach ($this->getCartService()->getCartDishes($place) as $item) {
            $dish = $item->getDishId();
            if (!$dishesService->isDishAvailable($dish)) {
                $formErrors[] = [
                    'message' => 'dishes.no_production',
                    'text'    => $dish->getName()
                ];
            }
        }

        $pointRecord = null;
        if (empty($placePointId)) {
            $placePointMap = $this->container->get('session')->get('point_data');
            if (!empty($placePointMap[$place->getId()])) {
                $pointRecord = $this->getEm()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointMap[$place->getId()]);
                if ($pointRecord) {
                    $isWork = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->isPlacePointWorks($pointRecord);
                    $locationData = $locationService->getLocationFromSession();
                    if (!$isWork) {
                        $placePointId = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->getPlacePointNear($place->getId(), $locationData);
                        $placePointMap[$place->getId()] = $placePointId;
                        $this->container->get('session')->set('point_data', $placePointMap);
                        if (empty($placePointId)) {
                            $formErrors[] = 'order.form.errors.no_restaurant_to_deliver';
                        } else {
                            $pointRecord = $this->getEm()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointId);
                        }
                    } else {
                        // Double check the place point for corrup detections
                        $pointForPlace = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->getPlacePointNear($place->getId(), $locationData);
                        if (!$pointForPlace) {
                            $this->container->get('logger')->warning('--- Not found near place point ---');
                            $this->container->get('logger')->warning('Place id: ' . $place->getId());
                            $this->container->get('logger')->warning('Location data: ' . var_export($locationData, true));

                            // no working placepoint for this restourant
                            $formErrors[] = 'order.form.errors.wrong_point_for_address';
                        } else {
                            $pointRecord = $this->getEm()->getRepository('FoodDishesBundle:PlacePoint')->find($pointForPlace);
                        }
                    }
                }
            } else {
                $formErrors[] = 'cart.checkout.place_point_not_in_radius';
            }
        } else {
            $pointRecord = $this->getEm()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointId);
        }

        // Test if correct dates passed to pre order
        $preOrder = $request->get('pre-order');
        if ($preOrder == 'it-is') {
            $orderDate = $request->get('pre_order_date') . ' ' . $request->get('pre_order_time');

            if ($orderDate < date("Y-m-d H:i", strtotime("-10 minute"))) {
                $formErrors[] = 'order.form.errors.back_in_time_preorder';
            }

            if ($orderDate > date("Y-m-d 00:00", strtotime("+4 day"))) {
                $formErrors[] = 'order.form.errors.back_in_feature_preorder';
            }

            if ($orderDate != date("Y-m-d H:i", strtotime($orderDate))) {
                $formErrors[] = 'order.form.errors.not_a_date';
            } elseif (!is_null($pointRecord)) {
                $this->workTimeErrors($pointRecord, $formErrors, $orderDate);
            }
        } elseif (!is_null($pointRecord)) {
            $this->workTimeErrors($pointRecord, $formErrors);
        }

        // Basket max items limits
        $basketDrinkLimit = $place->getBasketLimitDrinks();
        $basketFoodLimit = $place->getBasketLimitFood();
        if (!empty($basketFoodLimit) && $basketFoodLimit > 0) {
            $foods = $this->getCartService()->getCartDishes($place);
            $foodDishCount = 0;

            foreach ($foods as $dish) {
                $foodCat = $dish->getDishId()->getCategories();
                if (!$foodCat[0]->getDrinks()) {
                    $foodDishCount = $foodDishCount + (1 * $dish->getQuantity());
                }

                if ($foodDishCount > $basketFoodLimit) {
                    $formErrors[] = [
                        'message' => 'order.form.errors.dishLimit',
                        'text'    => $basketFoodLimit
                    ];
                    break;
                }
            }
        }

        if (!empty($basketDrinkLimit) && $basketDrinkLimit > 0) {
            $foods = $this->getCartService()->getCartDishes($place);
            $foodDishCount = 0;

            foreach ($foods as $dish) {
                $foodCat = $dish->getDishId()->getCategories();
                if ($foodCat[0]->getDrinks()) {
                    $foodDishCount = $foodDishCount + (1 * $dish->getQuantity());
                }

                if ($foodDishCount > $basketDrinkLimit) {
                    $formErrors[] = [
                        'message' => 'order.form.errors.drinkLimit',
                        'text'    => $basketFoodLimit
                    ];
                    break;
                }
            }
        }

        $phone = $request->get('customer-phone');

        if (0 === strlen($request->get('customer-firstname'))) {
            $formErrors[] = 'order.form.errors.customerfirstname';
        }

        // search for alco inside the basket
        $foods = $this->getCartService()->getCartDishes($place);
        $require_lastname = $this->getCartService()->isAlcoholInCart($foods);
        if ($require_lastname) {
            if (0 === strlen($request->get('customer-lastname'))) {
                $formErrors[] = 'order.form.errors.customerlastname';
            }
        }

        if (0 === strlen($phone)) {
            $formErrors[] = 'order.form.errors.customerphone';
        }

        if (0 === strlen($request->get('customer-comment'))) {
            // $formErrors[] = 'order.form.errors.customercomment';
            // UX improvement. Dejom skersa ant commentaro.
        }

        // Validate das phone number :)
        if (0 != strlen($phone)) {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            $country = strtoupper($this->container->getParameter('country'));

            try {
                $numberProto = $phoneUtil->parse($phone, $country);
            } catch (\libphonenumber\NumberParseException $e) {
                // no need for exception
            }

            if (isset($numberProto)) {
                $numberType = $phoneUtil->getNumberType($numberProto);
                $isValid = $phoneUtil->isValidNumber($numberProto);
            } else {
                $isValid = false;
            }

            if (!$isValid) {
                $formErrors[] = 'order.form.errors.customerphone_format';
            } else if ($isValid && !in_array($numberType, [\libphonenumber\PhoneNumberType::MOBILE, \libphonenumber\PhoneNumberType::FIXED_LINE_OR_MOBILE])) {
                $formErrors[] = 'order.form.errors.customerphone_not_mobile';
            } else {
                $phonePass = true;
            }
        }

        // Company field validation
        if ($request->get('company') == 'on') {
            $companyName = $request->get('company_name');
            $companyCode = $request->get('company_code');
            $companyAddress = $request->get('company_address');

            if (empty($companyName)) {
                $formErrors[] = 'order.form.errors.empty_company';
            }
            if (empty($companyCode)) {
                $formErrors[] = 'order.form.errors.empty_company_code';
            }
            if (empty($companyAddress)) {
                $formErrors[] = 'order.form.errors.empty_company_address';
            }
        }

        if ($request->get('cart_rules') != 'on') {
            $formErrors[] = 'order.form.errors.cart_rules';
        }

        if ($phonePass && $place->getNavision()) {
            $data = $this->container->get('food.nav')->validateCartInNav(
                $request->get('customer-phone'),
                $pointRecord,
                date("Y.m.d"),
                date("H:i:s"),
                (!$takeAway ? self::$deliveryDeliver : self::$deliveryPickup),
                $this->container->get('food.cart')->getCartDishes($place)
            )
            ;
            if (!$data['valid']) {
                $formHasErrors = true;
                if ($data['errcode']['code'] == "2" || $data['errcode']['code'] == "3") {
                    $formErrors[] = [
                        'message' => 'order.form.errors.problems_with_dish',
                        'text'    => $data['errcode']['problem_dish']
                    ];
                } elseif ($data['errcode']['code'] == 8) {
                    $formErrors[] = 'order.form.errors.nav_restaurant_no_work';
                } elseif ($data['errcode']['code'] == 6) {
                    $formErrors[] = 'order.form.errors.nav_restaurant_no_setted';
                } elseif ($data['errcode']['code'] == 255) {
                    $formHasErrors = true;
                    // $formErrors[] = 'order.form.errors.nav_empty_cart';
                }
            }
        }


        if (!empty($formErrors)) {
            $formHasErrors = true;
        }
    }

    /**
     * @param int $orderId
     */
    public function generateCsvById($orderId)
    {
        $order = $this->getOrderById($orderId);

        if ($order) {
            $this->generateCsv($order);
        }
    }

    /**
     * @param Order $order
     */
    public function generateCsv(Order $order)
    {
        $orderDetails = [];
        $foodTotalLine = 0;
        $drinksTotalLine = 0;
        $alcoholTotalLine = 0;
        foreach ($order->getDetails() as $detail) {
            //$cats = $detail->getDishId()->getCategories();

            //$cats = $this->get
            $query = "SELECT foodcategory_id FROM `food_category_dish_map` WHERE dish_id = " . $detail->getDishId()->getId();
            $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($query);
            $stmt->execute();
            $map = $stmt->fetchAll();
            $cat = null;
            if (!empty($map)) {
                $cat = $this->getEm()->getRepository('FoodDishesBundle:FoodCategory')->find($map[0]['foodcategory_id']);
            }

            if (!empty($cat)) {
                $isDrink = $cat->getDrinks();
                $isAlcohol = $cat->getAlcohol();
                if ($isAlcohol) {
                    $alcoholTotalLine += $detail->getPrice() * $detail->getQuantity();
                } elseif ($isDrink) {
                    $drinksTotalLine += $detail->getPrice() * $detail->getQuantity();
                } else {
                    $foodTotalLine += $detail->getPrice() * $detail->getQuantity();
                    foreach ($detail->getOptions() as $dtOption) {
                        $foodTotalLine += $dtOption->getPrice() * $dtOption->getQuantity();
                    }
                }
            } else {
                $isDrink = false;
                $isAlcohol = false;
            }
        }
        $driver = $order->getDriver();
        $driverRow = "#";
        if (!empty($driver)) {
            $driverRow = $driver->getName();
        }
        $address = $order->getAddressId();
        $addRow = "#";
        if (!empty($address)) {
            $addRow = $address->getAddress();
        }

        if ($foodTotalLine > 0) {
            $orderDetails[] = [
                $order->getId(),
                $order->getOrderDate()->format("Y-m-d H:i:s"),
                $order->getPlaceName(),
                $order->getPlacePointAddress(),
                $driverRow,
                self::$deliveryTrans[$order->getDeliveryType()],
                $addRow,
                $order->getPaymentMethod(),
                "MAISTAS",
                str_replace(".", ",", $foodTotalLine),
                $order->getVat()
            ];
        }
        if ($drinksTotalLine > 0) {
            $orderDetails[] = [
                $order->getId(),
                $order->getOrderDate()->format("Y-m-d H:i:s"),
                $order->getPlaceName(),
                $order->getPlacePointAddress(),
                $driverRow,
                self::$deliveryTrans[$order->getDeliveryType()],
                $addRow,
                $order->getPaymentMethod(),
                "GERIMAI",
                str_replace(".", ",", $drinksTotalLine),
                $order->getVat()
            ];
        }

        if ($alcoholTotalLine > 0) {
            $orderDetails[] = [
                $order->getId(),
                $order->getOrderDate()->format("Y-m-d H:i:s"),
                $order->getPlaceName(),
                $order->getPlacePointAddress(),
                $driverRow,
                self::$deliveryTrans[$order->getDeliveryType()],
                $addRow,
                $order->getPaymentMethod(),
                "ALKOHOLIS",
                str_replace(".", ",", $alcoholTotalLine),
                $order->getVat()
            ];
        }

        if ($order->getDeliveryType() == self::$deliveryDeliver) {
            $orderDetails[] = [
                $order->getId(),
                $order->getOrderDate()->format("Y-m-d H:i:s"),
                $order->getPlaceName(),
                $order->getPlacePointAddress(),
                $driverRow,
                self::$deliveryTrans[$order->getDeliveryType()],
                $addRow,
                $order->getPaymentMethod(),
                "PRISTATYMAS",
                str_replace(".", ",", $order->getPlace()->getDeliveryPrice()),
                $order->getVat()
            ];
        }
        foreach ($orderDetails as &$ordDet) {
            foreach ($ordDet as &$someDet) {
                $someDet = str_replace(";", "_", $someDet);
                $someDet = str_replace('"', "_", $someDet);
                $someDet = str_replace("'", "_", $someDet);
            }
            $ordDet = implode(";", $ordDet);
            $ordDet = $this->creepyFixer($ordDet);
        }
        $upp = realpath($this->container->get('kernel')->getRootDir() . '/../web/uploads');
        $uppDir = $upp . "/csv";
        $findex = $upp . "/csv/list.txt";
        if (!realpath($uppDir)) {
            mkdir($uppDir, 757);
        }
        $fname = "f_" . $order->getId() . ".csv";
        $fres = fopen($uppDir . "/" . $fname, "w+");
        fputs($fres, implode("\r\n", $orderDetails));
        fclose($fres);
        $fresIndex = fopen($findex, "a+");
        fputs($fresIndex, $fname . "\r\n");
        fclose($fresIndex);
    }

    /**
     * @param string $source
     *
     * @return string mixed
     */
    public function creepyFixer($source)
    {
        $s1 = ['ą', 'č', 'ę', 'ė', 'į', 'š', 'ų', 'ū', 'ž'];
        $s2 = ['Ą', 'Č', 'Ę', 'Ė', 'Į', 'Š', 'Ų', 'Ū', 'Ž'];
        $d1 = ['a', 'c', 'e', 'e', 'i', 's', 'u', 'u', 'z'];
        $d2 = ['A', 'C', 'E', 'E', 'I', 'S', 'U', 'U', 'Z'];
        foreach ($s1 as $k => $ss) {
            $source = str_replace($s1[$k], $d1[$k], $source);
            $source = str_replace($s2[$k], $d2[$k], $source);
        }

        return $source;
    }

    /**
     * Save with delay info...
     */
    public function saveDelay()
    {
        $duration = $this->getOrder()->getDelayDuration();
        $oTime = $this->getOrder()->getDeliveryTime();
        $now = new \DateTime("now");

        $oTimeClone = clone $oTime;

        $oTimeClone->add(new \DateInterval('P0DT0H' . $duration . 'M0S'));

        $diffInMinutes = ceil(($oTimeClone->getTimestamp() - $oTime->getTimestamp()) / 60 / 10) * 10;

        $deliverIn = ceil(($oTimeClone->getTimestamp() - $now->getTimestamp()) / 60 / 10) * 10;

        $this->getOrder()->setDeliveryTime($oTimeClone);
        $this->saveOrder();
//        var_dump($diffInMinutes);

        // Lets inform the user, that the order was delayed :(
        $orderExtra = $this->getOrder()->getOrderExtra();
        $userPhone = $orderExtra->getPhone();
        $userEmail = $orderExtra->getEmail();

        $translator = $this->container->get('translator');
        $domain = $this->container->getParameter('domain');

        if ($this->getOrder()->getDeliveryType() == 'pickup') {
            $translation = 'general.sms.user_order_delayed_pickup';
        } else {
            $translation = 'general.sms.user_order_delayed';
        }

        $messageText = $translator->trans(
            $translation,
            [
                'order_id'         => $this->getOrder()->getId(),
                'delay_time'       => $diffInMinutes,
                'delivery_min'     => $deliverIn,
                // TODO rodome nebe restorano, o dispeceriu telefona
                'restourant_phone' => $this->container->getParameter('dispatcher_contact_phone'),
//                'restourant_phone' => $this->getOrder()->getPlacePoint()->getPhone(),
            ]
        );

        if (!empty($userPhone)) {
            $messagingService = $this->container->get('food.messages');

            $message = $messagingService->createMessage(
                $this->container->getParameter('sms.sender'),
                $userPhone,
                $messageText,
                $this->getOrder()
            );
            $messagingService->saveMessage($message);
        }
        // And an email
        $mailer = $this->container->get('mailer');

        $message = \Swift_Message::newInstance()
            ->setSubject($this->container->getParameter('title') . ': ' . $translator->trans('general.email.user_delayed_subject'))
            ->setFrom('info@' . $domain)
        ;

        $message->addTo($userEmail);
        $message->setBody($messageText);
        $mailer->send($message);

    }

    /**
     * Get finished and ongoing user orders
     *
     * @param User $user
     *
     * @return array|\Food\OrderBundle\Entity\Order[]
     * @throws \InvalidArgumentException
     */
    public function getUserOrders(User $user, $onlyFinished = false)
    {
        if (!($user instanceof User)) {
            throw new \InvalidArgumentException('Not a user is given, sorry..');
        }

        $orderStatuses = [
            self::$status_accepted,
            self::$status_assiged,
            self::$status_delayed,
            self::$status_finished,
            self::$status_completed,
        ];

        if ($onlyFinished) {
            $orderStatuses = [self::$status_completed, self::$status_partialy_completed];
        }

        $em = $this->container->get('doctrine')->getManager();
        $orders = $em->getRepository('Food\OrderBundle\Entity\Order')
            ->findBy(
                [
                    'user'         => $user,
                    'order_status' => $orderStatuses
                ],
                [
                    'order_date' => 'DESC',
                ]
            )
        ;

        return $orders;
    }

    /**
     * @param string $code
     *
     * @return Coupon|null
     */
    public function getCouponByCode($code)
    {
        $em = $this->container->get('doctrine')->getManager();
        /**
         * @var ObjectManager $em
         */
        $coupon = $em->getRepository('Food\OrderBundle\Entity\Coupon')
            ->findOneBy([
                'code'   => $code,
                'active' => 1,
            ])
        ;

        return $coupon;
    }

    /**
     * @param Coupon $coupon
     *
     * @throws \Exception
     */
    public function saveCoupon($coupon)
    {
        if (empty($coupon) || $coupon == null) {
            throw new \Exception("No coupon - no saving");
        } else {
            $coupon->setEditedAt(new \DateTime("now"));
            $this->getEm()->persist($coupon);
            $this->getEm()->flush();
        }
    }

    /**
     * If coupon is for single use - deactivate it after purchase
     *
     * @throws \Exception
     */
    public function deactivateCoupon()
    {
        $order = $this->getOrder();
        if (!$order instanceof Order) {
            throw new \Exception('Cannot deactivate coupon if no order is given');
        }

        $coupon = $order->getCoupon();
        if ($coupon && $coupon instanceof Coupon && $coupon->getSingleUse()) {
            $coupon->setActive(false);
            $this->saveCoupon($coupon);
        }

        if ($coupon && $coupon instanceof Coupon && $coupon->getSingleUsePerPerson()) {
            $couponUser = new CouponUser();
            $couponUser->setCoupon($coupon)
                ->setUser($this->getOrder()->getUser())
                ->setUsedAt(new \Datetime)
            ;

            $this->getEm()->persist($couponUser);
            $this->getEm()->flush();
        }
    }

    /**
     * @param Dish $dish
     *
     * @return bool
     */
    public function isOrderableByTime(Dish $dish)
    {
        $timeFrom = $dish->getTimeFrom();
        $timeTo = $dish->getTimeTo();
        if (empty($timeFrom) && empty($timeTo)) {
            return true;
        } else {
            if (!empty($timeFrom) && !empty($timeTo)) {
                if (date("H:i") >= $timeFrom && date("H:i") <= $timeTo) {
                    return true;
                } else {
                    return false;
                }
            } elseif (!empty($timeFrom)) {
                if (date("H:i") >= $timeFrom) {
                    return true;
                } else {
                    return false;
                }
            } else {
                // !empty($timeTo);
                if (date("H:i") <= $timeTo) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * @param Order $order
     */
    public function createDiscountCode(Order $order)
    {
        $this->codeGenerator($order);

        $this->_freeDeliveryDiscount($order);
    }

    /**
     * @param string $timeToDelivery
     *
     * @return array
     */
    public function getOrdersToBeLate($timeToDelivery)
    {
        $date = new \DateTime("-" . $timeToDelivery . " minute");

        return $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order')->getOrdersToBeLate($date);
    }

    /**
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getBetaCode()
    {
        // disabling
        return '';

        $repo = $this->container->get('doctrine')->getRepository('FoodOrderBundle:BetaCoupon');
        $em = $this->container->get('doctrine')->getEntityManager();

        $query = "
          SELECT
            bc.id,
            bc.coupon_code
          FROM beta_coupons bc
          ORDER BY bc.id ASC
          LIMIT 1
        ";

        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $code = $result[0];

        $codeEntity = $repo->find($code['id']);

        $theCode = $codeEntity->getCode();

        $em->remove($codeEntity);
        $em->flush();

        return $theCode;
    }

    /**
     * Counts duration from start to delivery
     *
     * @param Order $order
     *
     * @return int
     */
    public function getDuration(Order $order)
    {
        $miscService = $this->container->get('food.app.utils.misc');
        if ($order->getPlacePoint()) {
            $timeShift = $miscService->parseTimeToMinutes($order->getPlacePoint()->getDeliveryTime());
        }

        if (empty($timeShift) || $timeShift <= 0) {
            $timeShift = 60;
        }

        return $timeShift;
    }

    /**
     * @return array
     */
    public function getForgottenOrders()
    {
        $repo = $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order');

        $result = $repo->getFutureUnacceptedOrders();

        $orders = [];

        foreach ($result as $key => $row) {
            $order = $this->getOrderById($row['id']);
            if ($this->isForgotten($order)) {
                $orders[] = $order;
            }
        }

        return $orders;
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function isForgotten(Order $order)
    {
        // order begin time
        $date = clone $order->getDeliveryTime();
        $date->modify('-' . $this->getDuration($order) . ' minutes');
        $nowDate = new \DateTime();

        // Rules if order is forgotten
        // 1. order is not self delivery & passed 15-30 min from beginning
        // 2. order is self delivery & passed 10 minutes from beginning and remind 5 times every 5 minutes
        if (!$order->getPlacePointSelfDelivery()) {
            $from = clone $date;
            $from->modify('+15 minutes');
            $to = clone $date;
            $to->modify('+30 minutes');
            if ($order->getReminded() < $order->getOrderDate() && $from <= $nowDate && $nowDate <= $to) {
                return true;
            }
        } else {
            $to = clone $date;
            $to->modify('+35 minutes');
            // if no reminded, then 10 - <25 minutes
            if ($order->getReminded() < $order->getOrderDate()) {
                $from = clone $date;
                $from->modify('+10 minutes');
                if ($from <= $nowDate && $nowDate < $to) {
                    return true;
                }
                // if reminded then 5 minutes later than last remind - <25 minutes
            } else {
                $needToRemindTime = clone $order->getReminded();
                $needToRemindTime->modify('+5 minutes');
                if ($needToRemindTime <= $nowDate && $nowDate < $to) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Order $order
     *
     * @return \DateTime
     */
    public function getMakingTime(Order $order)
    {
        $makingTime = clone $order->getDeliveryTime();

        return $makingTime->modify('-30 minutes');
    }

    /**
     * @param Order $order
     *
     * @return boolean
     */
    public function codeGenerator(Order $order)
    {
        $proceed = false;
        /**
         * @var CouponGenerator[] $generators
         */
        $generators = $this->container->get('doctrine')->getRepository('FoodOrderBundle:CouponGenerator')->findBy(['active' => 1]);
        foreach ($generators as $generator) {
            if ($generator && ($order->getTotal() - $order->getDeliveryPrice() >= $generator->getCartAmount())) {
                $nowTime = new \DateTime('NOW');
                if ($generator->getGenerateFrom() <= $nowTime && $generator->getGenerateTo() >= $nowTime) {
                    $proceed = true;
                    $places = $generator->getPlaces();
                    if (!empty($places) && sizeof($places)) {
                        $proceed = false;
                        foreach ($places as $place) {
                            if ($place->getId() == $order->getPlace()->getId()) {
                                $proceed = true;
                            }
                        }
                    }
                    if ($generator->getNoSelfDelivery()) {
                        if ($order->getPlace()->getSelfDelivery()) {
                            $proceed = false;
                        }
                    }
                    if ($proceed) {
                        $theCode = $generator->getCode();
                        if ($generator->getRandomize()) {
                            $randomStuff = ["niam", "niamniam", "skanu", "foodout"];
                            $theCode = strtoupper($randomStuff[array_rand($randomStuff)]) . $order->getId();
                        }
                        $newCode = new Coupon;
                        $newCode->setActive(true)
                            ->setCode($theCode)
                            ->setName($generator->getName() . " - #" . $order->getId())
                            ->setDiscount($generator->getDiscount())
                            ->setDiscountSum($generator->getDiscountSum())
                            ->setOnlyNav($generator->getOnlyNav())
                            ->setType($generator->getType())
                            ->setMethod($generator->getMethod())
                            ->setEnableValidateDate(true)
                            ->setFreeDelivery($generator->getFreeDelivery())
                            ->setSingleUse($generator->getSingleUse())
                            ->setSingleUsePerPerson($generator->getSingleUsePerPerson())
                            ->setOnlinePaymentsOnly($generator->getOnlinePaymentsOnly())
                            ->setValidFrom($generator->getValidFrom())
                            ->setValidTo($generator->getValidTo())
                            ->setValidHourlyFrom($generator->getValidHourlyFrom())
                            ->setValidHourlyTo($generator->getValidHourlyTo())
                            ->setIgnoreCartPrice($generator->getIgnoreCartPrice())
                            ->setIncludeDelivery($generator->getIncludeDelivery())
                            ->setB2b($generator->getB2b())
                            ->setCreatedAt(new \DateTime('NOW'))
                        ;

                        $this->container->get('food.mailer')
                            ->setVariable('code', $theCode)
                            ->setRecipient($order->getOrderExtra()->getEmail())
                            ->setId($generator->getTemplateCode())
                            ->send()
                        ;

                        $this->logMailSent(
                            $order,
                            'create_discount_code',
                            $generator->getTemplateCode(),
                            ['code' => $theCode]
                        );

                        $this->container->get('doctrine')->getManager()->persist($newCode);
                        $this->container->get('doctrine')->getManager()->flush();
                        break;
                    }
                }
            }
        }

        return $proceed;
    }

    /**
     * @private
     *
     * @param $order
     */
    private function _freeDeliveryDiscount(Order $order)
    {
        if (!$order->getIsCorporateClient()) {
            $start = date('Y-m-01 00:00:00');
            $query = 'SELECT count(*)
                      FROM `orders`
                      LEFT JOIN `coupons` ON `orders`.`coupon` = `coupons`.`id`
                      WHERE `order_date` > \'' . $start . '\'
                        AND `orders`.`user_id` = ' . $order->getUser()->getId() . '
                        AND `delivery_price` > 0
                        AND `orders`.`delivery_type` = \'deliver\'
                        AND `order_status` = \'' . static::$status_completed . '\'
                        AND (`coupons`.`id` IS NULL OR `coupons`.`free_delivery` = 0)
            ';

            $stmt = $this->container->get('doctrine')->getConnection()->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchColumn();
            if ($result > 0 && $result % 3 == 0) {
                $templateId = $this->container->getParameter('mailer_send_free_delivery_discount');
                $theCode = "CM" . strrev($order->getId()) . ($order->getId() % 10);
                $newCode = new Coupon;
                $newCode->setActive(true)
                    ->setCode($theCode)
                    ->setName("CM - #" . $order->getId())
                    ->setDiscount(0)
                    ->setDiscountSum(0)
                    ->setOnlyNav(0)
                    ->setType(Coupon::TYPE_BOTH)
                    ->setMethod(Coupon::METHOD_DELIVERY)
                    ->setFreeDelivery(1)
                    ->setSingleUse(1)
                    ->setNoSelfDelivery(1)
                    ->setEnableValidateDate(true)
                    ->setValidFrom(new \DateTime())
                    ->setValidTo(new \DateTime('+2 week'))
                    ->setCreatedAt(new \DateTime())
                ;

                $this->container->get('food.mailer')
                    ->setVariable('combo_code', $theCode)
                    ->setRecipient($order->getOrderExtra()->getEmail())
                    ->setId($templateId)
                    ->send()
                ;

                $this->logMailSent(
                    $order,
                    'create_free_delivery_discount_code',
                    $templateId,
                    ['code' => $theCode]
                );

                $this->container->get('doctrine')->getManager()->persist($newCode);
                $this->container->get('doctrine')->getManager()->flush();

            }
        }
    }

    /**
     * @param Coupon $coupon
     * @param Place  $place
     *
     * @return bool
     */
    public function validateCouponForPlace(Coupon $coupon, Place $place)
    {
        $couponPlaces = $coupon->getPlaces();
        if (count($couponPlaces)) {
            foreach ($couponPlaces as $couponPlace) {
                if ($couponPlace->getId() == $place->getId()) {
                    return true;
                }
            }
        } else {
            return true;
        }

        return false;
    }

    /**
     * @param $paymentType
     *
     * @return bool
     */
    public function isOnlinePayment($paymentType)
    {
        return in_array($paymentType, $this->onlinePayments);
    }

    /**
     * @param Coupon $coupon
     * @param User   $user
     *
     * @return bool
     */
    public function isCouponUsed(Coupon $coupon, User $user)
    {
        return (boolean)$this->getEm()->getRepository('FoodOrderBundle:CouponUser')->findOneBy([
            'coupon' => $coupon,
            'user'   => $user
        ])
            ;
    }

    public function informAdminAboutCancelation()
    {
        $cancelEmails = $this->container->getParameter('order.cancel_notify_emails');
        if (is_array($cancelEmails)) {
            $mailer = $this->container->get('mailer');
            $user = $this->container->get('security.context')->getToken()->getUser();
            $domain = $this->container->getParameter('domain');
            $order = $this->getOrder();

            $message = \Swift_Message::newInstance()
                ->setSubject($this->container->getParameter('title') . ': #' . $order->getId() . ' order cancel')
                ->setFrom('info@' . $domain)
                ->setContentType('text/html')
            ;

            $messageTxt = 'Order ID: ' . $order->getId() . "<br />";
            $messageTxt .= 'Order status: ' . $order->getOrderStatus() . "<br />";
            $messageTxt .= 'Order cancel reason: ' . $order->getOrderExtra()->getCancelReason() . "<br />";
            $messageTxt .= 'Order cancel reason comment: ' . $order->getOrderExtra()->getCancelReasonComment() . "<br />";
            $messageTxt .= 'User: ' . $user->getFirstname();

            $this->addEmailsToMessage($message, $cancelEmails);

            $message->setBody($messageTxt);
            $mailer->send($message);
        }
    }

    public function sendOrderPickedMessage()
    {
        if (!$this->getOrder() instanceof Order) {
            throw new \InvalidArgumentException('No order is set');
        }

        $recipient = $this->getOrder()->getOrderExtra()->getPhone();

        // SMS siunciam tik tuo atveju jei orderis ne is callcentro
        if ($this->getOrder()->getOrderFromNav() == false) {
            if (!empty($recipient)) {
                $smsService = $this->container->get('food.messages');

                $sender = $this->container->getParameter('sms.sender');

                $text = $this->container->get('translator')
                    ->trans(
                        'general.sms.client.driver_picked_up',
                        [],
                        null,
                        $this->getOrder()->getLocale()
                    )
                ;

                $message = $smsService->createMessage($sender, $recipient, $text, $this->getOrder());
                $smsService->saveMessage($message);
            }
        }
    }

    /**
     * Send Message To User About Successfully Created Order
     */
    public function sendOrderCreatedMessage()
    {
        if (!$this->getOrder() instanceof Order) {
            throw new \InvalidArgumentException('No order is set');
        }

        $sendMessage = true;
        if (in_array($this->getLocale(), array())) {
            $sendMessage = false;
        }

        if ($sendMessage) {
            $recipient = $this->getOrder()->getOrderExtra()->getPhone();

            // SMS siunciam tik tuo atveju jei orderis ne is callcentro
            if ($this->getOrder()->getOrderFromNav() == false) {
                if (!empty($recipient)) {
                    $smsService = $this->container->get('food.messages');
                    $sender = $this->container->getParameter('sms.sender');

                    $text = $this->container->get('translator')
                        ->trans(
                            'general.sms.client.order_created',
                            [
                                'order_id' => $this->getOrder()->getId(),
                            ],
                            null,
                            $this->getOrder()->getLocale()
                        );

                    $message = $smsService->createMessage($sender, $recipient, $text, $this->getOrder());
                    $smsService->saveMessage($message);
                }
            }
        }
    }

    /**
     * @param Order $order
     *
     * @return string
     */
    public function getPickedUpTime($order)
    {
        $deliveryLogRepo = $this->container->get('doctrine')->getRepository('FoodOrderBundle:OrderDeliveryLog');

        $pickedUpEntry = $deliveryLogRepo->findOneBy([
            'order' => $order,
            'event' => 'order_pickedup'
        ]);

        if (!empty($pickedUpEntry) && $pickedUpEntry instanceof OrderDeliveryLog) {
            return $pickedUpEntry->getEventDate()->format('Y-m-d H:i');
        } else {
            return '';
        }
    }

    /**
     * @return bool
     */
    public function getAllowToInform()
    {
        $response = false;

        $order = $this->getOrder();
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('No order is set');
        }

        if ($order->getPlace()->getNavision()
            || ($order->getPlace()->getAutoInform()
                && !$order->getPlaceInformed()
                && !$order->getPreorder()
                && $this->isAllowToInformOnZaval())
        ) {
            $response = true;
        }

        return $response;
    }

    /**
     * @return bool
     */
    public function isAllowToInformOnZaval()
    {
        $response = true;
        $miscService = $this->container->get('food.app.utils.misc');
        if ($miscService->getParam('zaval_on')) {
            $order = $this->getOrder();
            if (!$order instanceof Order) {
                throw new \InvalidArgumentException('No order is set');
            }

            if (stripos($miscService->getParam('zaval_cities'), $order->getPlacePointCity()) !== false
                && !$order->getPlacePointSelfDelivery()
                && $order->getDeliveryType() != self::$deliveryPickup
            ) {
                $response = false;
            }
        }

        return $response;
    }

    public function updateDriver()
    {
        $order = $this->getOrder();
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('No order is set');
        }

        if ($this->container->getParameter('driver.send_to_external')
            && $order->getDeliveryType() == 'deliver'
            && $order->getPlacePointSelfDelivery() == false) {
            $logisticsCityFilter = $this->container->getParameter('driver.city_filter');
            if (empty($logisticsCityFilter) || in_array($order->getPlacePointCity(), $logisticsCityFilter)) {
                $this->container->get('food.order')->logOrder($order, 'schedule_driver_api_send', 'Order scheduled to send to driver');

                $om = $this->container->get('doctrine')->getManager();
                $orderToLogistics = new OrderToDriver();

                $orderToLogistics->setOrder($order)
                    ->setDateAdded(new \DateTime("now"));

                $om->persist($orderToLogistics);
                $om->flush();
            }
        }
    }
}
