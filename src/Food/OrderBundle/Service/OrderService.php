<?php

namespace Food\OrderBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Food\CartBundle\Service\CartService;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\Coupon;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderDetails;
use Food\OrderBundle\Entity\OrderDetailsOptions;
use Food\OrderBundle\Entity\OrderLog;
use Food\OrderBundle\Entity\OrderStatusLog;
use Food\OrderBundle\Entity\PaymentLog;
use Food\UserBundle\Entity\User;
use Food\UserBundle\Entity\UserAddress;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;

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

    public static $status_nav_problems = "nav_problems";

    // TODO o gal sita mapa i configa? What do You think?
    private $paymentSystemByMethod = array(
        'local' => 'food.local_biller',
        'local.card' => 'food.local_biller',
        'paysera' => 'food.paysera_biller',
        'swedbank-gateway' => 'food.swedbank_gateway_biller',
        'swedbank-credit-card-gateway' => 'food.swedbank_credit_card_gateway_biller',
        'seb-banklink' => 'food.seb_banklink_biller',
        'nordea-banklink' => 'food.nordea_banklink_biller'
    );

    public static $deliveryTrans = array(
        'deliver' => 'PRISTATYMAS',
        'pickup'  => 'ATSIEMIMAS'
    );

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

    /**
     * @param int $placeId
     * @param PlacePoint $placePoint
     * @return Order
     */
    public function createOrder($placeId, $placePoint=null)
    {
        $placeRecord = $this->getEm()->getRepository('FoodDishesBundle:Place')->find($placeId);
        if (empty($placePoint)) {
            $placePointMap = $this->container->get('session')->get('point_data');
            $pointRecord = $this->getEm()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointMap[$placeId]);
        } else {
            $pointRecord = $placePoint;
        }

        $this->order = new Order();
        $user = $this->container->get('security.context')->getToken()->getUser();
        if ($user == 'anon.') {
            $user = null;
        }
        $this->order->setPlace($placeRecord);
        $this->order->setPlaceName($placeRecord->getName());
        $this->order->setPlacePointSelfDelivery($placeRecord->getSelfDelivery());

        $this->order->setPlacePoint($pointRecord);
        $this->order->setPlacePointCity($pointRecord->getCity());
        $this->order->setPlacePointAddress($pointRecord->getAddress());

        $deliveryTime = new \DateTime("now");
        $deliveryTime->modify("+60 minutes");

        $this->order->setUser($user);
        $this->order->setOrderDate(new \DateTime("now"));
        $this->order->setDeliveryTime($deliveryTime);
        $this->order->setVat($this->container->getParameter('vat'));
        $this->order->setOrderHash(
            $this->generateOrderHash($this->order)
        );

        // Log user IP address
        $this->order->setUserIp($this->container->get('request')->getClientIp());

        return $this->getOrder();
    }

    /**
     * @param string $status
     * @param string|null $source
     * @param string|null $message
     */
    protected function chageOrderStatus($status, $source=null, $message=null)
    {
        // Let's log the shit out of it
        $this->logStatusChange($this->getOrder(), $status, $source, $message);

        $this->getOrder()->setOrderStatus($status);
    }

    /**
     * @param string|null $source
     * @param string|null $statusMessage
     * @return $this
     */
    public function statusNew($source=null, $statusMessage=null)
    {
        $this->chageOrderStatus(self::$status_new, $source, $statusMessage);
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
    public function statusFailed($source=null, $statusMessage=null)
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
    public function statusAccepted($source=null, $statusMessage=null)
    {
        // Inform poor user, that his order was accepted
        if ($this->getOrder()->getOrderStatus() == self::$status_new) {
            $recipient = $this->getOrder()->getUser()->getPhone();

            if (!empty($recipient)) {
                $smsService = $this->container->get('food.messages');

                $sender = $this->container->getParameter('sms.sender');

                $translation = 'general.sms.user.order_accepted';
                if ($this->getOrder()->getDeliveryType() == 'pickup') {
                    $translation = 'general.sms.user.order_accepted_pickup';
                }

                $placeName = $this->container->get('food.app.utils.language')
                    ->removeChars('lt', $this->getOrder()->getPlaceName(), false, false);
                $placeName = ucfirst($placeName);

                $text = $this->container->get('translator')
                    ->trans(
                        $translation,
                        array(
                            'restourant_name' => $placeName,
                            'delivery_time' => $this->getOrder()->getPlace()->getDeliveryTime(),
                            'restourant_phone' => $this->getOrder()->getPlacePoint()->getPhone()
                        ),
                        null,
                        $this->getOrder()->getLocale()
                    );

                $message = $smsService->createMessage($sender, $recipient, $text);
                $smsService->saveMessage($message);
            }
            $this->getOrder()->setAcceptTime(new \DateTime("now"));
            $dt = new \DateTime('now');
            $dt->add(new \DateInterval('P0DT1H0M0S'));
            $this->getOrder()->setDeliveryTime($dt);
            $this->saveOrder();
            $this->chageOrderStatus(self::$status_accepted, $source, $statusMessage);
            $this->_notifyOnAccepted();

            // Notify Dispatchers
            $this->notifyOrderAccept();
            // Kitais atvejais tik keiciam statusa, nes gal taip reikia
        } else {
            $this->chageOrderStatus(self::$status_accepted, $source, $statusMessage);
        }

        return $this;
    }

    /**
     * Inform client, that restourant accepted their order
     */
    private function _notifyOnAccepted()
    {
        $ml = $this->container->get('food.mailer');

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
        $invoice = array();
        foreach ($this->getOrder()->getDetails() as $ord) {
//            $ordersText.="<li>".$ord->getDishName()." (".$ord->getQuantity()." vnt.)";
            $options = $ord->getOptions();
            $invoice[] = array(
                'itm_name' => $ord->getDishName(),
                'itm_amount' => $ord->getQuantity(),
                'itm_price' => $ord->getPrice(),
                'itm_sum' => $ord->getPrice() * $ord->getQuantity(),
            );
            if (sizeof($options) > 0) {
                /*$ordersText.="<ul>";
                foreach ($options as $opt) {
                    $ordersText.="<li>".$opt->getDishOptionName()."</li>";
                }
                $ordersText.="</ul>";


                $ordersText.=" (".$this->container->get('translator')->trans('email.dishes.options').": ";*/
                foreach ($options as $k=>$opt) {
                    /*if ($k !=0) {
                        $ordersText.=", ";
                    }
                    $ordersText.=$opt->getDishOptionName();*/
                    $invoice[] = array(
                        'itm_name' => "  - ".$opt->getDishOptionName(),
                        'itm_amount' => $ord->getQuantity(),
                        'itm_price' => $opt->getPrice(),
                        'itm_sum' => $opt->getPrice() * $ord->getQuantity(),
                    );
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
            'adresas' => ($this->getOrder()->getDeliveryType() != self::$deliveryPickup ? $this->getOrder()->getAddressId()->getAddress().", ".$this->getOrder()->getAddressId()->getCity() : "--"),
            'pristatymo_data' => $this->getOrder()->getDeliveryTime()->format('Y-m-d H:i:s')
        );

*/

        $variables = array(
            'maisto_gamintojas' => $this->getOrder()->getPlace()->getName(),
            'maisto_ruosejas' => $this->getOrder()->getPlacePoint()->getAddress(),
            'uzsakymas' => $this->getOrder()->getId(),
            'adresas' => ($this->getOrder()->getDeliveryType() != self::$deliveryPickup ? $this->getOrder()->getAddressId()->getAddress().", ".$this->getOrder()->getAddressId()->getCity() : "--"),
            'pristatymo_data' => $this->getOrder()->getDeliveryTime()->format('Y-m-d H:i:s'),
            'total_sum' => $this->getOrder()->getTotal(),
            'total_delivery' => ($this->getOrder()->getDeliveryType() == self::$deliveryDeliver ? $this->getOrder()->getPlace()->getDeliveryPrice() : 0),
            'total_card' => ($this->getOrder()->getDeliveryType() == self::$deliveryDeliver ?  ($this->getOrder()->getTotal() - $this->getOrder()->getPlace()->getDeliveryPrice()) :  $this->getOrder()->getTotal()),
            'invoice' => $invoice
        );


//        $ml->setVariables( $variables )->setRecipient($this->getOrder()->getUser()->getEmail(), $this->getOrder()->getUser()->getEmail())->setId( 30009269  )->send();
        $ml->setVariables( $variables )->setRecipient($this->getOrder()->getUser()->getEmail(), $this->getOrder()->getUser()->getEmail())->setId( 30010811 )->send();
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusAssigned($source=null, $statusMessage=null)
    {
        // Inform poor user, that his order was accepted
        $driver = $this->getOrder()->getDriver();
        if ($driver->getType() == 'local') {
            $messagingService = $this->container->get('food.messages');
            $logger = $this->container->get('logger');

            // Inform driver about new order that was assigned to him
            $orderConfirmRoute = $this->container->get('router')
                ->generate('drivermobile', array('hash' => $this->getOrder()->getOrderHash()), true);

            $messageText = $this->container->get('translator')->trans('general.sms.driver_assigned_order')
                .$orderConfirmRoute;

            $logger->alert("Sending message for driver about assigned order to number: ".$driver->getPhone().' with text "'.$messageText.'"');

            $message = $messagingService->createMessage(
                $this->container->getParameter('sms.sender'),
                $driver->getPhone(),
                $messageText
            );
            $messagingService->saveMessage($message);
        }

        $this->chageOrderStatus(self::$status_assiged, $source, $statusMessage);

        return $this;
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusForwarded($source=null, $statusMessage=null)
    {
        $this->chageOrderStatus(self::$status_forwarded, $source, $statusMessage);
        return $this;
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusCompleted($source=null, $statusMessage=null)
    {
        $this->chageOrderStatus(self::$status_completed, $source, $statusMessage);

        // Form accounting data if it is not formed already
        // TODO uzkomentuota, nes nenaudojame, o dabar meta: Error: Class 'Food\OrderBundle\Service\AccountingService' not found in app\cache\dev\appDevDebugProjectContainer.php line 1211
//        $order = $this->getOrder();
//        $accountingService = $this->container->get('food.accounting');
//        $accounting = $order->getAccounting();

        // if not generated yet - do it!
        if (empty($accounting)) {
            // TODO kolkas stabdome. Pirmam testavimui reikia susitvarkyti su SMS'ais ir mobile vairuotojo aplinka, vaztarasciu
//            $accounting = $accountingService->generateAccounting($this->getOrder());

            // TODO upload accounting
        }
        $ml = $this->container->get('food.mailer');

        $variables = array(
            'maisto_gamintojas' => $this->getOrder()->getPlace()->getName(),
            'miestas' => $this->getOrder()->getPlacePoint()->getCity(),
            'maisto_review_url' => 'http://www.foodout.lt/lt/'.$this->container->get('food.dishes.utils.slug')->getSlugByItem(
                    $this->getOrder()->getPlace()->getId(),
                    'place'
            ).'/#detailed-restaurant-review'
        );

       $ml->setVariables( $variables )->setRecipient($this->getOrder()->getUser()->getEmail(), $this->getOrder()->getUser()->getEmail())->setId( 30009271 )->send();

        return $this;
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusFinished($source=null, $statusMessage=null)
    {
        $this->chageOrderStatus(self::$status_finished, $source, $statusMessage);
        return $this;
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusCanceled($source=null, $statusMessage=null)
    {
        // If restourant (or Foodout) has canceled order - send informational SMS message to user
        $userPhone = $this->getOrder()->getUser()->getPhone();
        if (!empty($userPhone)) {
            $messagingService = $this->container->get('food.messages');

            if ($this->getOrder()->getPaymentMethod() == 'local') {
                $messageText = $this->container->get('translator')->trans('general.sms.client.restourant_cancel');
            // If banklink payment and it is complete - inform that we will return money, because restourant sux!
            } elseif ($this->getOrder()->getPaymentStatus() == 'complete') {
                $messageText = $this->container->get('translator')->trans('general.sms.client.restourant_cancel_banklink');
            }

            if (!empty($messageText)) {
                $message = $messagingService->createMessage(
                    $this->container->getParameter('sms.sender'),
                    $userPhone,
                    $messageText
                );
                $messagingService->saveMessage($message);
            }
        }

        $this->chageOrderStatus(self::$status_canceled, $source, $statusMessage);
        return $this;
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusDelayed($source=null, $statusMessage=null)
    {
        $this->chageOrderStatus(self::$status_delayed, $source, $statusMessage);
        return $this;
    }

    /**
     * @return Order
     * @throws \Exception
     */
    public function getOrder()
    {
        if (empty($this->order))
        {
            throw new \Exception("Dude - no order here :)");
        }
        return $this->order;
    }

    /**
     * @param Order $order
     * @throws \InvalidArgumentException
     */
    public function setOrder($order)
    {
        if (empty($order)) {
            throw new \InvalidArgumentException("An empty variable is not allowed on our company!");
        }
        if (!($order instanceof Order))
        {
            throw new \InvalidArgumentException("This is not an order, You gave me!");
        }

        $this->order = $order;
    }

    /**
     * @param \Food\UserBundle\Entity\User $user
     * @param string $city
     * @param string $address
     * @param string $lat
     * @param string $lon
     *
     * @return UserAddress
     */
    public function createAddressMagic($user, $city, $address, $lat, $lon)
    {
        $userAddress = $this->getEm()
            ->getRepository('Food\UserBundle\Entity\UserAddress')
            ->findOneBy(array(
                'user' => $user,
                'city' => $city,
                'address' => $address,
        ));

        if (!$userAddress) {
            $userAddress = new UserAddress();
            $userAddress->setUser($user)
                ->setCity($city)
                ->setAddress($address)
                ->setLat($lat)
                ->setLon($lon);

            $this->getEm()->persist($userAddress);
            $this->getEm()->flush();
        }

        return $userAddress;
    }

    /**
     * @param int $place
     * @param string $locale
     * @param \Food\UserBundle\Entity\User $user
     * @param PlacePoint $placePoint - placePoint, jei atsiima pats
     * @param bool $selfDelivery - ar klientas atsiims pats?
     * @param Coupon|null $coupon
     */
    public function createOrderFromCart($place, $locale='lt', $user, PlacePoint $placePoint=null, $selfDelivery = false, $coupon = null)
    {
        $this->createOrder($place, $placePoint);
        $this->getOrder()->setDeliveryType(
            ($selfDelivery ? 'pickup' : 'deliver')
        );
        $this->getOrder()->setLocale($locale);
        $this->getOrder()->setUser($user);
        $this->saveOrder();
        $sumTotal = 0;

        $placeObject = $this->container->get('food.places')->getPlace($place);

        foreach ($this->getCartService()->getCartDishes($placeObject) as $cartDish) {
            $options = $this->getCartService()->getCartDishOptions($cartDish);
            $dish = new OrderDetails();
            $dish->setDishId($cartDish->getDishId())
                ->setOrderId($this->getOrder())
                ->setQuantity($cartDish->getQuantity())
                ->setDishSizeCode($cartDish->getDishSizeId()->getCode())
                ->setPrice($cartDish->getDishSizeId()->getPrice())
                ->setDishName($cartDish->getDishId()->getName())
                ->setDishUnitId($cartDish->getDishSizeId()->getUnit()->getId())
                ->setDishUnitName($cartDish->getDishSizeId()->getUnit()->getName())
                ->setDishSizeCode($cartDish->getDishSizeId()->getCode());
            ;
            $this->getEm()->persist($dish);
            $this->getEm()->flush();

            $sumTotal += $cartDish->getQuantity() * $cartDish->getDishSizeId()->getPrice();

            foreach ($options as $opt) {
                $orderOpt = new OrderDetailsOptions();
                $orderOpt->setDishOptionId($opt->getDishOptionId())
                    ->setDishOptionCode($opt->getDishOptionId()->getCode())
                    ->setDishOptionName($opt->getDishOptionId()->getName())
                    ->setPrice($opt->getDishOptionId()->getPrice())
                    ->setDishId($cartDish->getDishId())
                    ->setOrderId($this->getOrder())
                    ->setQuantity($cartDish->getQuantity()) // @todo Kolkas paveldimas. Veliau taps valdomas kiekvienam topingui atskirai
                    ->setOrderDetail($dish);
                $this->getEm()->persist($orderOpt);
                $this->getEm()->flush();

                $sumTotal += $cartDish->getQuantity() * $opt->getDishOptionId()->getPrice();
            }
        }

        // Pritaikom nuolaida
        if (!empty($coupon) && $coupon instanceof Coupon) {
            $order = $this->getOrder();
            $order->setCoupon($coupon)
                ->setCouponCode($coupon->getCode());

            $discountSize = $coupon->getDiscount();
            $discountSum = ($sumTotal * $discountSize) / 100;
            $sumTotal = $sumTotal - $discountSum;

            $order->setDiscountSize($discountSize)
                ->setDiscountSum($discountSum);
        }

        if(!$selfDelivery) {
            $sumTotal+= $this->getOrder()->getPlace()->getDeliveryPrice();
        }
        $this->getOrder()->setTotal($sumTotal);
        $this->saveOrder();

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
        $order = $em->getRepository('Food\OrderBundle\Entity\Order')->findBy(array('order_hash' => $hash), null, 1);

        if (!$order) {
            return false;
        }

        if (count($order) > 1) {
            throw new \Exception('More then one order found. How the hell? Hash: '.$hash);
        }

        // TODO negrazu, bet laikina :(
        $this->order = $order[0];

        return $this->order;
    }

    /**
     * @param null $localBiller
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
     * @param null $payseraBiller
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
     * @return BillingInterface
     */
    public function getBillingInterface($type = 'local')
    {
        switch($type) {
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
     * @param int|null $orderId [optional] Order ID if should be loading a new one
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

        $this->logPayment($order, 'billing start', 'Billing started with method: '.$billingType, $order);

        return $redirectUrl;
    }

    /**
     * @param string $method
     * @throws \InvalidArgumentException
     */
    public function setPaymentMethod($method)
    {
        $order = $this->getOrder();

        if (!$this->isAvailablePaymentMethod($method)) {
            throw new \InvalidArgumentException('Payment method: '.$method.' is unknown to our system or not available');
        }

        $oldMethod = $order->getPaymentMethod();
        $order->setPaymentMethod($method);

        $this->logPayment($order, 'payement method change', sprintf('Method changed from "%s" to "%s"', $oldMethod, $method));
    }

    /**
     * @param string $method
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
            throw new \InvalidArgumentException('Sorry, no map for method "'.$method.'"');
        }
        return $this->container->get($class);
    }

    /**
     * @param string $status Payment status
     * @param string|null $message [optional] Error message
     * @throws \InvalidArgumentException
     */
    public function setPaymentStatus($status, $message=null)
    {
        $order = $this->getOrder();

        if (!$this->isAllowedPaymentStatus($status)) {
            throw new \InvalidArgumentException('Status: "'.$status.'" is not a valid order payment status');
        }

        if (!$this->isValidPaymentStatusChange($order->getPaymentStatus(), $status)) {
            throw new \InvalidArgumentException('Order can not go from status: "'.$order->getPaymentStatus().'" to: "'.$status.'" is not a valid order payment status');
        }

        $oldStatus = $order->getPaymentStatus();
        $order->setPaymentStatus($status);

        if ($status == self::$paymentStatusError) {
            $order->setLastPaymentError($message);
        }

        $this->logPayment(
            $order,
            'payement status change',
            sprintf('Status changed from "%s" to "%s" with message %s', $oldStatus, $status, $message)
        );

        $this->saveOrder();
    }

    /**
     * @return array
     */
    public function getAllowedPaymentStatuses()
    {
        return array(
            self::$paymentStatusNew,
            self::$paymentStatusWait,
            self::$paymentStatusWaitFunds,
            self::$paymentStatusComplete,
            self::$paymentStatusCanceled,
            self::$paymentStatusError,
        );
    }

    /**
     * @param string $from
     * @param string $to
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

        $flowLine = array(
            self::$paymentStatusNew => 0,
            self::$paymentStatusWait => 1,
            self::$paymentStatusWaitFunds => 1,
            self::$paymentStatusCanceled => 1,
            self::$paymentStatusComplete => 2,
            self::$paymentStatusError => 2,
        );

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
     * @return bool
     */
    public function isValidOrderStatusChange($from, $to)
    {
        $flowLine = array(
            self::$status_new => 0,
            self::$status_accepted => 1,
            self::$status_delayed => 2,
            self::$status_forwarded => 2,
            self::$status_finished => 3,
            self::$status_assiged => 4,
            self::$status_completed => 5,
            self::$status_canceled => 5,
        );

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

    /**
     * @param string|null $status
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
            $userString = 'anonymous_'.mt_rand(0,50);
        } else {
            $userString = $user->getId();
        }

        $hash = md5(
            $userString.$order->getOrderDate()->getTimestamp().$order->getAddressId()
        );

        return $hash;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function isValidDeliveryType($type)
    {
        if (in_array($type, array(self::$deliveryDeliver, self::$deliveryPickup))) {
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
            throw new \InvalidArgumentException('Delivery type: "'.$type.'" is unknown or not allowed');
        }

        $order->setDeliveryType($type);
    }

    /**
     * @param Order|null|false $order
     * @param string $event
     * @param string|null $message
     * @param mixed $debugData
     */
    public function logOrder($order=null, $event, $message=null, $debugData=null)
    {
        $log = new OrderLog();

        if (empty($order) && !($order instanceof Order)) {
            $order = $this->getOrder();
        }

        $user = $this->container->get('security.context')->getToken()->getUser();

        if ($user == 'anon.') {
            $user = null;
        }

        $log->setOrder($order)
            ->setOrderStatus($order->getOrderStatus())
            ->setEvent($event)
            ->setMessage($message)
            ->setUser($user);

        if (is_array($debugData)) {
            $debugData = var_export($debugData, true);
        } else if (is_object($debugData)) {
            if (method_exists($debugData, '__toArray')) {
                $debugData = 'Class: '.get_class($debugData).' Data: '
                    .var_export($debugData->__toArray(), true);
            } else {
                $debugData = get_class($debugData);
            }
        }
        $log->setDebugData($debugData);

        $this->getEm()->persist($log);
        $this->getEm()->flush();
    }

    /**
     * @param Order|null $order
     * @param string $event
     * @param string|null $message
     * @param mixed $debugData
     */
    public function logPayment($order=null, $event, $message=null, $debugData=null)
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
            ->setUser($user);

        if (is_array($debugData)) {
            $debugData = var_export($debugData, true);
        } else if (is_object($debugData)) {
            if (method_exists($debugData, '__toArray')) {
                $debugData = 'Class: '.get_class($debugData).' Data: '
                    .var_export($debugData->__toArray(), true);
            } else {
                $debugData = get_class($debugData);
            }
        }
        $log->setDebugData($debugData);

        $this->getEm()->persist($log);
        $this->getEm()->flush();
    }

    public function getOrdersForDriver($driver)
    {
        $em = $this->container->get('doctrine')->getManager();
        $orders = $em->getRepository('Food\OrderBundle\Entity\Order')
            ->findBy(array(
                'driver' => $driver,
                'order_status' => self::$status_assiged,
            ));

        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * Send a message to place about new order
     *
     * @param boolean $isReminder Is this a new order or is this a reminder?
     */
    public function informPlace($isReminder=false)
    {
        if (!$isReminder) {
            $this->notifyOrderCreate();
        }

        $messagingService = $this->container->get('food.messages');
        $translator = $this->container->get('translator');
        $logger = $this->container->get('logger');
        $miscUtils = $this->container->get('food.app.utils.misc');
        $country = $this->container->getParameter('country');

        $order = $this->getOrder();
        $placePoint = $order->getPlacePoint();
        $placePointEmail = $placePoint->getEmail();
        $placePointAltEmail1 = $placePoint->getAltEmail1();
        $placePointAltEmail2 = $placePoint->getAltEmail2();
        $placePointAltPhone1 = $placePoint->getAltPhone1();
        $placePointAltPhone2 = $placePoint->getAltPhone2();

        $domain = $this->container->getParameter('domain');

        // Inform restourant about new order

        if ($isReminder) {
            $orderConfirmRoute = 'http://'.$domain
                .$this->container->get('router')
                    ->generate('ordermobile', array('hash' => $order->getOrderHash()));

            $orderSmsTextTranslation = $translator->trans('general.sms.order_reminder');
            $orderTextTranslation = $translator->trans('general.email.order_reminder');
        } else {
            $orderConfirmRoute = $this->container->get('router')
                ->generate('ordermobile', array('hash' => $order->getOrderHash()), true);

            $orderSmsTextTranslation = $translator->trans('general.sms.new_order');
            $orderTextTranslation = $translator->trans('general.email.new_order');
        }

        $messageText = $orderSmsTextTranslation
            .$orderConfirmRoute;

        // Jei placepoint turi emaila - vadinas siunciam jiems emaila :)
        if (!empty($placePointEmail)) {
            $logger->alert('--- Place asks for email, so we have sent an email about new order to: '.$placePointEmail);
            $emailMessageText = $messageText;
            $emailMessageText .= "\n" . $orderTextTranslation . ': '
                . $order->getPlacePoint()->getAddress() . ', ' . $order->getPlacePoint()->getCity();
            // Buvo liepta padaryti, kad sms'u eitu tas pats, kas emailu. Pasiliekam, o maza kas
//            $messageText = $translator->trans('general.sms.new_order_in_mail');

            $mailer = $this->container->get('mailer');

            $message = \Swift_Message::newInstance()
                ->setSubject($this->container->getParameter('title').': '.$translator->trans('general.sms.new_order'))
                ->setFrom('info@'.$domain)
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

        // Siunciam SMS tik tuo atveju, jei neperduodam per Nav'a
        if (!$order->getPlace()->getNavision()) {
            // Siunciam sms'a
            $logger->alert("Sending message for order to be accepted to number: ".$placePoint->getPhone().' with text "'.$messageText.'"');
            $smsSenderNumber = $this->container->getParameter('sms.sender');

            // I pagrindini nr siunciam net jei landline, kad gautume errorus jei ka..
            $messagesToSend = array(
                array(
                    'sender' => $smsSenderNumber,
                    'recipient' => $placePoint->getPhone(),
                    'text' => $messageText
                )
            );

            // Informuojame papildomais numeriais (del visa ko)
            if (!empty($placePointAltPhone1) && $miscUtils->isMobilePhone($placePointAltPhone1, $country)) {
                $logger->alert("Sending additional message for order to be accepted to number: ".$placePointAltPhone1.' with text "'.$messageText.'"');

                $messagesToSend[] = array(
                    'sender' => $smsSenderNumber,
                    'recipient' => $placePointAltPhone1,
                    'text' => $messageText
                );
            }
            if (!empty($placePointAltPhone2) && $miscUtils->isMobilePhone($placePointAltPhone2, $country)) {
                $logger->alert("Sending additional message for order to be accepted to number: ".$placePointAltPhone2.' with text "'.$messageText.'"');

                $messagesToSend[] = array(
                    'sender' => $smsSenderNumber,
                    'recipient' => $placePointAltPhone2,
                    'text' => $messageText
                );
            }

            //send multiple messages
            $messagingService->addMultipleMessagesToSend($messagesToSend);
        }
    }

    /**
     * For debuging purpose only!
     */
    public function notifyOrderCreate() {
        $order = $this->getOrder();

        if ($order->getPlace()->getNavision()) {
            $nav = $this->container->get('food.nav');
            $orderRenew = $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order')->find($order->getId());



            $query = "SELECT * FROM order_details WHERE order_id=".$order->getId();
            $stmt = $this->container->get('doctrine')->getConnection()->prepare($query);
            $stmt->execute();
            $details = $stmt->fetchAll();
            foreach ($details as $det) {
                $orderRenew->addDetail(
                    $this->container->get('doctrine')->getRepository('FoodOrderBundle:OrderDetails')->find($det['id'])
                );
            }

            $nav->putTheOrderToTheNAV($orderRenew);
            sleep(1);
            $returner = $nav->updatePricesNAV($orderRenew);
            sleep(1);

            if($returner->return_value == "TRUE") {
                $returner = $nav->processOrderNAV($orderRenew);
                if($returner->return_value == "TRUE") {

                } else {
                    $order->setOrderStatus(self::$status_nav_problems);
                    $this->getEm()->merge($order);
                    $this->getEm()->flush();
                }
            } else {
                $order->setOrderStatus(self::$status_nav_problems);
                $this->getEm()->merge($order);
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
            $userAddress = $order->getAddressId()->getAddress().', '.$order->getAddressId()->getCity();
        }

        $newOrderText = $translator->trans('general.new_order.title');

        $emailMessageText = $newOrderText.' '.$order->getPlace()->getName()."\n"
            ."OrderId: " . $order->getId()."\n\n"
            .$translator->trans('general.new_order.selected_place_point').": ".$order->getPlacePoint()->getAddress().', '.$order->getPlacePoint()->getCity()."\n"
            .$translator->trans('general.new_order.place_point_phone').":".$order->getPlacePoint()->getPhone()."\n"
            ."\n"
            .$translator->trans('general.new_order.client_name').": ".$order->getUser()->getFirstname().' '.$order->getUser()->getLastname()."\n"
            .$translator->trans('general.new_order.client_address').": ".$userAddress."\n"
            .$translator->trans('general.new_order.client_phone').": ".$order->getUser()->getPhone()."\n"
            .$translator->trans('general.new_order.client_email').": ".$order->getUser()->getEmail()."\n"
            ."\n"
            .$translator->trans('general.new_order.delivery_type').": ".$order->getDeliveryType()."\n"
            .$translator->trans('general.new_order.payment_type').": ".$order->getPaymentMethod()."\n"
            .$translator->trans('general.new_order.payment_status').": ".$order->getPaymentStatus()."\n"
        ;

        $emailMessageText .= "\n"
            .$translator->trans('general.new_order.restaurant_link').": ".$this->container->get('router')
                ->generate('ordermobile', array('hash' => $order->getOrderHash()), true)
            ."\n";
        $emailMessageText .= "\n"
            .$translator->trans('general.new_order.admin_link').": ".$this->container->get('router')
                ->generate('order_support_mobile', array('hash' => $order->getOrderHash()), true)
            ."\n";

        $mailer = $this->container->get('mailer');

        $message = \Swift_Message::newInstance()
            ->setSubject($newOrderText.': '.$order->getPlace()->getName().' (#'.$order->getId().')')
            ->setFrom('info@'.$domain)
        ;

        $mainEmailSet = false;

        foreach ($notifyEmails as $email) {
            if (!$mainEmailSet) {
                $mainEmailSet = true;
                $message->addTo($email);
            } else {
                $message->addCc($email);
            }
        }

        if (!empty($cityCoordinators)) {
            if (isset($cityCoordinators[mb_strtolower($order->getPlacePointCity(), 'UTF-8')])) {
                foreach($cityCoordinators[mb_strtolower($order->getPlacePointCity(), 'UTF-8')] as $coordinatorEmail) {
                    $message->addCc($coordinatorEmail);
                }
            }
        }

        $message->setBody($emailMessageText);
        $mailer->send($message);
    }

    /**
     * For debuging purpose only!
     */
    public function notifyOrderAccept() {
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
            $userAddress = $order->getAddressId()->getAddress().', '.$order->getAddressId()->getCity();
        }

        $driverUrl = $this->container->get('router')
                ->generate('drivermobile', array('hash' => $order->getOrderHash()), true);

        $newOrderText = $translator->trans('general.new_order.title');

        $emailMessageText = $newOrderText.' '.$order->getPlace()->getName()."\n"
            ."OrderId: " . $order->getId()."\n\n"
            .$translator->trans('general.new_order.selected_place_point').": ".$order->getPlacePoint()->getAddress().', '.$order->getPlacePoint()->getCity()."\n"
            .$translator->trans('general.new_order.place_point_phone').":".$order->getPlacePoint()->getPhone()."\n"
            ."\n"
            .$translator->trans('general.new_order.client_name').": ".$order->getUser()->getFirstname().' '.$order->getUser()->getLastname()."\n"
            .$translator->trans('general.new_order.client_address').": ".$userAddress."\n"
            .$translator->trans('general.new_order.client_phone').": ".$order->getUser()->getPhone()."\n"
            .$translator->trans('general.new_order.client_email').": ".$order->getUser()->getEmail()."\n"
            ."\n"
            .$translator->trans('general.new_order.delivery_type').": ".$order->getDeliveryType()."\n"
            .$translator->trans('general.new_order.payment_type').": ".$order->getPaymentMethod()."\n"
            .$translator->trans('general.new_order.payment_status').": ".$order->getPaymentStatus()."\n"
            ."\n"
            .$translator->trans('general.new_order.driver_link').": ".$driverUrl
        ;

        $mailer = $this->container->get('mailer');

        $message = \Swift_Message::newInstance()
            ->setSubject($newOrderText.': '.$order->getPlace()->getName())
            ->setFrom('info@'.$domain)
        ;

        $mainEmailSet = false;

        foreach ($notifyEmails as $email) {
            if (!$mainEmailSet) {
                $mainEmailSet = true;
                $message->addTo($email);
            } else {
                $message->addCc($email);
            }
        }

        $message->setBody($emailMessageText);
        $mailer->send($message);
    }

    /**
     * @param Order $order
     * @param string $newStatus
     * @param null|string $source
     * @param null|string $message
     */
    public function logStatusChange($order, $newStatus, $source=null, $message=null)
    {
        $log = new OrderStatusLog();
        $log->setOrder($order)
            ->setEventDate(new \DateTime('now'))
            ->setOldStatus($order->getOrderStatus())
            ->setNewStatus($newStatus)
            ->setSource($source)
            ->setMessage($message);

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
        return array
        (
            self::$status_new,
            self::$status_accepted,
            self::$status_delayed,
            self::$status_forwarded,
            self::$status_finished,
            self::$status_assiged,
            self::$status_completed,
            self::$status_canceled,
        );
    }

    /**
     * Returns all available payment statuses
     *
     * @return array
     */
    public static function getPaymentStatuses()
    {
        return array
        (
            self::$paymentStatusNew,
            self::$paymentStatusWait,
            self::$paymentStatusWaitFunds,
            self::$paymentStatusCanceled,
            self::$paymentStatusComplete,
            self::$paymentStatusError,
        );
    }


    /**
     * @param PlacePoint $placePoint
     * @param array $errors
     * @todo fix laiku poslinkiai
     */
    private  function workTimeErrors(PlacePoint $placePoint, &$errors)
    {
        $wd = date('w');
        if ($wd == 0) $wd = 7;
        $timeFr = $placePoint->{'getWd'.$wd.'Start'}();
        $timeTo = $placePoint->{'getWd'.$wd.'End'}();

        if(!strpos($timeFr, ':')|| !strpos($timeTo, ':')) {
            $errors[] = "order.form.errors.today_no_work";
        } else {
            $timeFrTs = strtotime($timeFr);
            $timeToFs = strtotime($timeTo);
            if ($timeToFs < $timeFrTs) {
                $timeToFs+= 60 * 60 * 24;
            }
            if ($timeFrTs > date('U')) {
                $errors[] = "order.form.errors.isnt_open";
            } elseif ($timeToFs < date('U')) {
                $errors[] = "order.form.errors.is_already_close";
            }
        }
    }

    /**
     * @param PlacePoint $placePoint
     * @return mixed|string
     */
    public function workTimeErrorsReturn(PlacePoint $placePoint)
    {
        $errors = array();
        $this->workTimeErrors($placePoint, $errors);
        if (!empty($errors)) {
            return end($errors);
        }
        return "";
    }

    /**
     * @param Place $place
     * @return bool
     */
    public function isTodayNoOneWantsToWork(Place $place)
    {
        $returner = true;
        foreach ($place->getPoints() as $point) {
            if ($point->getActive()) {
                if ($this->isTodayWork($point)) {
                    $returner = false;
                }
            }
        }
        return $returner;
    }

    /**
     * @param Place $place
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
                if ($this->isTodayWork($point)) {
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
     * @return string
     */
    public function notWorkingPlacesPoints(Place $place)
    {
        $returner = '<div>';
        foreach ($place->getPoints() as $point) {
            if ($point->getActive()) {
                $returner.= $point->getAddress()." ";
                if ($this->isTodayWork($point)) {
                    $returner.= '<span class="work-green">'.$this->getTodayWork($point, false)."</span>";
                } else {
                    $returner.= '<span class="work-red">'.$this->getTodayWork($point, false)."</span> ". $this->container->get('translator')->trans($this->workTimeErrorsReturn($point));
                }
                $returner.="<br />";
            }
        }
        $returner.="</div>";
        return $returner;
    }

    /**
     * @param PlacePoint $placePoint
     * @return bool
     */
    public function isTodayWork(PlacePoint $placePoint)
    {
        $wd = date('w');
        if ($wd == 0) $wd = 7;
        $timeFr = $placePoint->{'getWd'.$wd.'Start'}();
        $timeTo = $placePoint->{'getWd'.$wd.'End'}();

        if(!strpos($timeFr, ':')|| !strpos($timeTo, ':')) {
            return false;
        } else {
            $timeFrTs = strtotime($timeFr);
            $timeToFs = strtotime($timeTo);
            if ($timeToFs < $timeFrTs) {
                $timeToFs+= 60 * 60 * 24;
            }
            if ($timeFrTs > date('U')) {
                return false;
            } elseif ($timeToFs < date('U')) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param PlacePoint $placePoint
     * @param bool $showDayNumber
     * @return string
     */
    public function getTodayWork(PlacePoint $placePoint, $showDayNumber = true)
    {
        $wdays = array(
            '1' =>'I',
            '2' =>'II',
            '3' =>'III',
            '4' =>'IV',
            '5' =>'V',
            '6' =>'VI',
            '7' =>'VII',
        );
        $wd = date('w');
        if ($wd == 0) $wd = 7;
        $timeFr = $placePoint->{'getWd'.$wd.'Start'}();
        $timeTo = $placePoint->{'getWd'.$wd.'End'}();
        return ($showDayNumber ? $wdays[$wd]." " : ""). $timeFr." - ".$timeTo;
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $place
     * @param Request $request
     * @param $formHasErrors
     * @param $formErrors
     * @param $takeAway
     * @param null|int $placePointId
     * @param Coupon|null $coupon
     */
    public function validateDaGiantForm(Place $place, Request $request, &$formHasErrors, &$formErrors, $takeAway, $placePointId = null, $coupon = null)
    {
        if (!$takeAway) {
            $list = $this->getCartService()->getCartDishes($place);
            $total_cart = $this->getCartService()->getCartTotal($list/*, $place*/);
            if ($total_cart < $place->getCartMinimum()) {
                $formErrors[] = 'order.form.errors.cartlessthanminimum';
            }

            $addrData = $this->container->get('food.googlegis')->getLocationFromSession();
            if (empty($addrData['address_orig'])) {
                $formErrors[] = 'order.form.errors.customeraddr';
            }
        } elseif ($place->getMinimalOnSelfDel()) {
            $list = $this->getCartService()->getCartDishes($place);
            $total_cart = $this->getCartService()->getCartTotal($list/*, $place*/);
            if ($total_cart < $place->getCartMinimum()) {
                $formErrors[] = 'order.form.errors.cartlessthanminimum_on_pickup';
            }
        }

        $pointRecord = null;

        if (empty($placePointId)) {
            $placePointMap = $this->container->get('session')->get('point_data');
            if (!empty($placePointMap[$place->getId()])) {
                $pointRecord = $this->getEm()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointMap[$place->getId()]);
            } else {
                $formErrors[] = 'order.form.errors.customeraddr';
            }
        } else {
            $pointRecord = $this->getEm()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointId);
        }
        if ($pointRecord != null) {
            $this->workTimeErrors($pointRecord, $formErrors);
        }

        $phone = $request->get('customer-phone');

        if (0 === strlen($request->get('customer-firstname'))) {
            $formErrors[] = 'order.form.errors.customerfirstname';
        }

        if (0 === strlen($phone)) {
            $formErrors[] = 'order.form.errors.customerphone';
        }

        if (0 === strlen($request->get('customer-comment'))) {
            $formErrors[] = 'order.form.errors.customercomment';
        }

        $customerEmail = $request->get('customer-email');
        if (0 === strlen($customerEmail)) {
            $formErrors[] = 'order.form.errors.customeremail';
        } else {
            $emailConstraint = new EmailConstraint();
            $emailConstraint->message = 'Email invalid';

            $emailErrors = $this->container->get('validator')->validateValue(
                $customerEmail,
                $emailConstraint
            );

            if ($emailErrors->count() > 0) {
                $formErrors[] = 'order.form.errors.customeremail_invalid';
            }
        }

        if (0 === strlen($request->get('payment-type'))) {
            $formErrors[] = 'order.form.errors.payment_type';
        }

        if (!empty($coupon) && $coupon instanceof Coupon) {
            if ($coupon->getActive() == false) {
                $formErrors[] = 'general.coupon.not_active';
            } else if ($coupon->getPlace() && $coupon->getPlace()->getId() != $place->getId()) {
                $formErrors[] = 'general.coupon.wrong_place_simple';
            }
        }

        // Validate das phone number :)
        if (0 != strlen($phone)) {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            $country = $this->container->getParameter('country');

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
            } else if ($isValid && !in_array($numberType, array(\libphonenumber\PhoneNumberType::MOBILE, \libphonenumber\PhoneNumberType::FIXED_LINE_OR_MOBILE))) {
                $formErrors[] = 'order.form.errors.customerphone_not_mobile';
            }
        }

        if ($request->get('cart_rules') != 'on') {
            $formErrors[] = 'order.form.errors.cart_rules';
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
        $orderDetails = array();
        $foodTotalLine = 0;
        $drinksTotalLine = 0;
        $alcoholTotalLine = 0;
        foreach ($order->getDetails() as $detail)
        {
            //$cats = $detail->getDishId()->getCategories();

            //$cats = $this->get
            $query = "SELECT foodcategory_id FROM `food_category_dish_map` WHERE dish_id = ".$detail->getDishId()->getId();
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
            $orderDetails[] = array(
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
            );
        }
        if ($drinksTotalLine > 0) {
            $orderDetails[] = array(
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
            );
        }

        if ($alcoholTotalLine > 0) {
            $orderDetails[] = array(
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
            );
        }

        if($order->getDeliveryType() == self::$deliveryDeliver) {
            $orderDetails[] = array(
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
            );
        }
        foreach ($orderDetails as &$ordDet) {
            foreach ($ordDet as &$someDet) {
                $someDet = str_replace(";","_", $someDet);
                $someDet = str_replace('"',"_", $someDet);
                $someDet = str_replace("'","_", $someDet);
            }
            $ordDet = implode(";", $ordDet);
            $ordDet = $this->creepyFixer($ordDet);
        }
        $upp = realpath($this->container->get('kernel')->getRootDir() . '/../web/uploads');
        $uppDir = $upp."/csv";
        $findex = $upp."/csv/list.txt";
        if (!realpath($uppDir)) {
            mkdir($uppDir, 757);
        }
        $fname = "f_".$order->getId().".csv";
        $fres = fopen($uppDir."/".$fname, "w+");
        fputs($fres, implode("\r\n", $orderDetails));
        fclose($fres);
        $fresIndex = fopen($findex,"a+");
        fputs($fresIndex, $fname."\r\n");
        fclose($fresIndex);
    }

    /**
     * @param string $source
     * @return string mixed
     */
    public function creepyFixer($source)
    {
        $s1 = array('ą','č','ę','ė','į','š','ų','ū','ž');
        $s2 = array('Ą','Č','Ę','Ė','Į','Š','Ų','Ū','Ž');
        $d1 = array('a','c','e','e','i','s','u','u','z');
        $d2 = array('A','C','E','E','I','S','U','U','Z');
        foreach($s1 as $k=>$ss) {
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

        $oTimeClone->add(new \DateInterval('P0DT0H'.$duration.'M0S'));

        $diffInMinutes = ceil(($oTimeClone->getTimestamp() - $oTime->getTimestamp()) / 60/10) * 10;

        $deliverIn = ceil(($oTimeClone->getTimestamp() - $now->getTimestamp()) / 60/10) * 10;

        $this->getOrder()->setDeliveryTime($oTimeClone);
        $this->saveOrder();
//        var_dump($diffInMinutes);

        // Lets inform the user, that the order was delayed :(
        $user = $this->getOrder()->getUser();
        $userPhone = $user->getPhone();
        $userEmail = $user->getEmail();

        $translator = $this->container->get('translator');
        $domain = $this->container->getParameter('domain');

        $translation = 'general.sms.user_order_delayed';
        if ($this->getOrder()->getDeliveryType() == 'pickup') {
            $translation = 'general.sms.user_order_delayed_pickup';
        }

        $messageText = $translator->trans(
            $translation,
            array(
                'delay_time' => $diffInMinutes,
                'delivery_min' => $deliverIn,
                'restourant_phone' => $this->getOrder()->getPlacePoint()->getPhone(),
            )
        );

        if (!empty($userPhone)) {
            $messagingService = $this->container->get('food.messages');

            $message = $messagingService->createMessage(
                $this->container->getParameter('sms.sender'),
                $userPhone,
                $messageText
            );
            $messagingService->saveMessage($message);
        }
        // And an email
        $mailer = $this->container->get('mailer');

        $message = \Swift_Message::newInstance()
            ->setSubject($this->container->getParameter('title').': '.$translator->trans('general.email.user_delayed_subject'))
            ->setFrom('info@'.$domain)
        ;

        $message->addTo($userEmail);
        $message->setBody($messageText);
        $mailer->send($message);

    }

    /**
     * Get finished and ongoing user orders
     *
     * @param User $user
     * @return array|\Food\OrderBundle\Entity\Order[]
     * @throws \InvalidArgumentException
     */
    public function getUserOrders(User $user)
    {
        if (!($user instanceof User)) {
            throw new \InvalidArgumentException('Not a user is given, sorry..');
        }

        $em = $this->container->get('doctrine')->getManager();
        $orders = $em->getRepository('Food\OrderBundle\Entity\Order')
            ->findBy(
                array(
                    'user' => $user,
                    'order_status' => array(
                        self::$status_accepted,
                        self::$status_assiged,
                        self::$status_delayed,
                        self::$status_finished,
                        self::$status_completed,
                    )
                ),
                array(
                    'order_date' => 'DESC',
                )
            );

        return $orders;
    }

    /**
     * @param string $code
     * @return Coupon|null
     */
    public function getCouponByCode($code)
    {
        $em = $this->container->get('doctrine')->getManager();
        /**
         * @var ObjectManager $em
         */
        $coupon = $em->getRepository('Food\OrderBundle\Entity\Coupon')
            ->findOneBy(array(
                'code' => $code,
                'active' => 1,
            ));

        return $coupon;
    }

    /**
     * @param Coupon $coupon
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
    }
}
