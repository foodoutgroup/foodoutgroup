<?php

namespace Food\OrderBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use Food\CartBundle\Service\CartService;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderDetails;
use Food\OrderBundle\Entity\OrderDetailsOptions;
use Food\OrderBundle\Entity\OrderLog;
use Food\OrderBundle\Entity\OrderStatusLog;
use Food\OrderBundle\Entity\PaymentLog;
use Food\UserBundle\Entity\User;
use Food\UserBundle\Entity\UserAddress;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

class OrderService extends ContainerAware
{
    private $localBiller = null;

    private $payseraBiller = null;

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

    // TODO o gal sita mapa i configa? What do You think?
    private $paymentSystemByMethod = array(
        'local' => 'food.local_biller',
        'local.card' => 'food.local_biller',
        'paysera' => 'food.paysera_biller'
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
     */
    public function setEm($em)
    {
        $this->em = $em;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
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
     * @return $this
     */
    public function statusFailed($source=null, $statusMessage=null)
    {
        $this->chageOrderStatus(self::$status_failed, $source, $statusMessage);
        return $this;
    }

    /**
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

                $text = $this->container->get('translator')
                    ->trans(
                        $translation,
                        array(
                            'restourant_name' => $this->getOrder()->getPlaceName(),
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

    private function _notifyOnAccepted()
    {
        $ml = $this->container->get('food.mailer');

        $userName = "";
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
        $ordersText.= "<ul>";
        foreach ($this->getOrder()->getDetails() as $ord) {
            $ordersText.="<li>".$ord->getDishName()." (".$ord->getQuantity()." vnt.)";
            $options = $ord->getOptions();
            if (sizeof($options) > 0) {
                /*
                $ordersText.="<ul>";
                foreach ($options as $opt) {
                    $ordersText.="<li>".$opt->getDishOptionName()."</li>";
                }
                $ordersText.="</ul>";
                */

                $ordersText.=" (".$this->container->get('translator')->trans('email.dishes.options').": ";
                foreach ($options as $k=>$opt) {
                    if ($k !=0) {
                        $ordersText.=", ";
                    }
                    $ordersText.=$opt->getDishOptionName();
                }
                $ordersText.=")";

            }
            $ordersText.="</li>";
        }
        $ordersText.= "</ul>";

        $variables = array(
            'username' => $userName,
            'maisto_gamintojas' => $this->getOrder()->getPlace()->getName(),
            'maisto_ruosejas' => $this->getOrder()->getPlacePoint()->getAddress(),
            'uzsakymas' => $ordersText,
            'adresas' => ($this->getOrder()->getDeliveryType() != self::$deliveryPickup ? $this->getOrder()->getAddressId()->getAddress().", ".$this->getOrder()->getAddressId()->getCity() : "--"),
            'pristatymo_data' => $this->getOrder()->getDeliveryTime()->format('Y-m-d H:i:s')
        );

        $ml->setVariables( $variables )->setRecipient( $this->getOrder()->getUser()->getEmail(), $this->getOrder()->getUser()->getEmail())->setId( 30009269 )->send();

    }

    /**
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
                .': '.$orderConfirmRoute;

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
     * @return $this
     */
    public function statusForwarded($source=null, $statusMessage=null)
    {
        $this->chageOrderStatus(self::$status_forwarded, $source, $statusMessage);
        return $this;
    }

    /**
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

        return $this;
    }

    /**
     * @return $this
     */
    public function statusFinished($source=null, $statusMessage=null)
    {
        $this->chageOrderStatus(self::$status_finished, $source, $statusMessage);
        return $this;
    }

    /**
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
     */
    public function createOrderFromCart($place, $locale='lt', $user, $placePoint=null, $selfDelivery = false)
    {
        $this->createOrder($place, $placePoint);
        $this->getOrder()->setDeliveryType(
            ($selfDelivery ? 'pickup' : 'deliver')
        );
        $this->getOrder()->setLocale($locale);
        $this->getOrder()->setUser($user);
        $this->saveOrder();
        $sumTotal = 0;

        foreach ($this->getCartService()->getCartDishes($place) as $cartDish) {
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
        if(!$selfDelivery) {
            $sumTotal+= $this->getOrder()->getPlace()->getDeliveryPrice();
        }
        $this->getOrder()->setTotal($sumTotal);
        $this->saveOrder();

    }

    public function saveOrder()
    {
        if (empty($this->order) || $this->order == null) {
            throw new Exception("Yah whatever... seivinam orderi neturedami jo ?:)");
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
     * @return null
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
     * @return null
     */
    public function getPayseraBiller()
    {
        if (empty($this->payseraBiller)) {
            $this->payseraBiller = new PaySera();
        }
        return $this->payseraBiller;
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
                break;

            case 'paysera':
            default:
                return $this->getPayseraBiller();
                break;
        }
    }

    /**
     * @param int|null $orderId [optional] Order ID if should be loading a new one
     * @param string|null $billingType [optional] Billing type if should use another then saved in order
     *
     * @return string
     */
    public function billOrder($orderId = null, $billingType = null)
    {
        if (empty($orderId)) {
            $order = $this->getOrder();
        } else {
            $order = $this->getOrderById($orderId);
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
            self::$paymentStatusComplete => 1,
            self::$paymentStatusCanceled => 1,
            self::$paymentStatusError => 1,
        );

        if ($flowLine[$from] <= $flowLine[$to]) {
            return true;
        }

        return false;
    }

    /**
     * @param $status
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
            $userString = 'anonymous_'.mt_rand(0,10);
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
     * @param Order|null $order
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

    /**
     * @return array
     */
    public function getYesterdayOrdersGrouped()
    {
        $dateFrom = new \DateTime(date("Y-m-d 00:00:00", strtotime('-1 day')));
        $dateTo = new \DateTime(date("Y-m-d 23:59:59", strtotime('-1 day')));

        $filter = array(
            'order_status' =>  array(self::$status_completed),
            'order_date_between' => array('from' => $dateFrom, 'to' => $dateTo),
        );

        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array(
                'pickup' => array(),
                'self_delivered' => array(),
                'our_deliver' => array(),
                'total' => 0,
            );
        }

        $ordersGrouped = array(
            'pickup' => array(),
            'self_delivered' => array(),
            'our_deliver' => array(),
            'total' => count($orders),
        );

        foreach ($orders as $order) {
            if ($order->getDeliveryType() == 'pickup') {
                $ordersGrouped['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $ordersGrouped['self_delivered'][] = $order;
            } else {
                $ordersGrouped['our_deliver'][] = $order;
            }
        }

        return $ordersGrouped;
    }

    /**
     * @param string $city
     * @return array
     */
    public function getOrdersUnassigned($city)
    {
        $date = new \DateTime();
        $date->modify("-2 minutes");

        $filter = array(
            'order_status' =>  array(self::$status_accepted, self::$status_delayed, self::$status_finished),
            'place_point_city' => $city,
            'deliveryType' => self::$deliveryDeliver,
            'order_date_more' => $date,
            'paymentStatus' => OrderService::$paymentStatusComplete,
        );

        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @param string $city
     * @return array
     */
    public function getOrdersUnconfirmed($city)
    {
        $filter = array(
            'order_status' =>  array(self::$status_new),
            'place_point_city' => $city,
            'deliveryType' => self::$deliveryDeliver,
            'paymentStatus' => OrderService::$paymentStatusComplete,
        );

        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @param string $city
     * @return array
     */
    public function getOrdersAssigned($city)
    {
        $filter = array(
            'order_status' =>  self::$status_assiged,
            'place_point_city' => $city,
            'deliveryType' => self::$deliveryDeliver,
            'paymentStatus' => OrderService::$paymentStatusComplete,
        );

        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @param array $filter
     * @param string $type Type of result expected. Available: ['list', 'single']
     * @throws \InvalidArgumentException
     * @return array|\Food\OrderBundle\Entity\Order[]
     */
    protected function getOrdersByFilter($filter, $type = 'list')
    {
        if (!in_array($type, array('list', 'single'))) {
            throw new \InvalidArgumentException('Unknown query type, dude');
        }

        $em = $this->container->get('doctrine')->getManager();

        if ($type == 'list') {
            $qb = $em->getRepository('Food\OrderBundle\Entity\Order')->createQueryBuilder('o');

            /**
             * @var QueryBuilder $qb
             */
            $qb->where('1 = 1');

            foreach ($filter as $filterName => $filterValue) {
                switch($filterName) {
                    case 'order_date_more':
                        $qb->andWhere('o.order_date < :'.$filterName);
                        break;

                    case 'order_date_between':
                        $qb->andWhere('o.order_date BETWEEN :order_date_between_from AND :order_date_between_to');
                        unset($filter['order_date_between']);
                        $filter['order_date_between_from'] = $filterValue['from'];
                        $filter['order_date_between_to'] = $filterValue['to'];
                        break;

                    case 'order_status':
                        $qb->andWhere('o.'.$filterName.' IN (:'.$filterName.')');
                        break;

                    default:
                        $qb->andWhere('o.'.$filterName.' = :'.$filterName);
                        break;
                }
            }

            $qb->setParameters($filter)
                ->orderBy('o.order_date', 'ASC');

            $orders = $qb->getQuery()
                ->getResult();
        } else {
            $orders = $em->getRepository('Food\OrderBundle\Entity\Order')
                ->findBy(
                    $filter,
                    array('order_date' => 'DESC'),
                    1
                );
        }

        return $orders;
    }

    /**
     * @param string $city
     * @param int $id
     * @return bool
     */
    public function hasNewUnassignedOrder($city, $id)
    {
        $filter = array(
            'order_status' =>  array(self::$status_accepted, self::$status_delayed, self::$status_finished),
            'place_point_city' => $city,
            'deliveryType' => self::$deliveryDeliver,
        );
        $order = $this->getOrdersByFilter($filter, 'single');

        if (!$order) {
            return false;
        }

        $order = $order[0];

        if ($order->getId() > $id) {
            return true;
        }
        return false;
    }

    /**
     * @param string $city
     * @param int $id
     * @return bool
     */
    public function hasNewUnconfirmedOrder($city, $id)
    {
        $filter = array(
            'order_status' =>  array(self::$status_new),
            'place_point_city' => $city,
            'deliveryType' => self::$deliveryDeliver,
        );
        $order = $this->getOrdersByFilter($filter, 'single');

        if (!$order) {
            return false;
        }

        $order = $order[0];

        if ($order->getId() > $id) {
            return true;
        }
        return false;
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
     */
    public function informPlace()
    {
        // @TODO remove after beta! testing puspose only. Inform developers about new order - NOW!
        $this->notifyOrderCreate();

        $messagingService = $this->container->get('food.messages');
        $translator = $this->container->get('translator');
        $logger = $this->container->get('logger');

        $order = $this->getOrder();
        $placePoint = $order->getPlacePoint();
        $placePointEmail = $placePoint->getEmail();
        $placePointAltEmail1 = $placePoint->getAltEmail1();
        $placePointAltEmail2 = $placePoint->getAltEmail2();
        $placePointAltPhone1 = $placePoint->getAltPhone1();
        $placePointAltPhone2 = $placePoint->getAltPhone2();

        $domain = $this->container->getParameter('domain');

        // Inform restourant about new order
        $orderConfirmRoute = $this->container->get('router')
            ->generate('ordermobile', array('hash' => $order->getOrderHash()), true);

        $messageText = $translator->trans('general.sms.new_order')
            .$orderConfirmRoute;

        // Jei placepoint turi emaila - vadinas siunciam jiems emaila :)
        if (!empty($placePointEmail)) {
            $logger->alert('--- Place asks for email, so we have sent an email about new order to: '.$placePointEmail);
            $emailMessageText = $messageText;
            $emailMessageText .= "\n" . $translator->trans('general.email.new_order') . ': '
                . $order->getPlacePoint()->getAddress() . ', ' . $order->getPlacePoint()->getCity();
            // Buvo liepta padaryti, kad sms'u eitu tas pats, kas emailu. Pasiliekam, o maza kas
//            $messageText = $translator->trans('general.sms.new_order_in_mail');

            $mailer = $this->container->get('mailer');

            $message = \Swift_Message::newInstance()
                ->setSubject($this->container->getParameter('title').': '.$translator->trans('general.email.new_order'))
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

        // Siunciam sms'a
        $logger->alert("Sending message for order to be accepted to number: ".$placePoint->getPhone().' with text "'.$messageText.'"');

        $message = $messagingService->createMessage(
            $this->container->getParameter('sms.sender'),
            $placePoint->getPhone(),
            $messageText
        );
        $messagingService->saveMessage($message);

        // Informuojame papildomais numeriais (del visa ko)
        if (!empty($placePointAltPhone1)) {
            $logger->alert("Sending additional message for order to be accepted to number: ".$placePointAltPhone1.' with text "'.$messageText.'"');

            $message = $messagingService->createMessage(
                $this->container->getParameter('sms.sender'),
                $placePointAltPhone1,
                $messageText
            );
            $messagingService->saveMessage($message);
        }
        if (!empty($placePointAltPhone2)) {
            $logger->alert("Sending additional message for order to be accepted to number: ".$placePointAltPhone2.' with text "'.$messageText.'"');

            $message = $messagingService->createMessage(
                $this->container->getParameter('sms.sender'),
                $placePointAltPhone2,
                $messageText
            );
            $messagingService->saveMessage($message);
        }
    }

    /**
     * For debuging purpose only!
     */
    public function notifyOrderCreate() {
        $order = $this->getOrder();

        $domain = $this->container->getParameter('domain');
        $notifyEmails = $this->container->getParameter('order.notify_emails');
        $cityCoordinators = $this->container->getParameter('order.city_coordinators');

        $userAddress = '';
        $userAddressObject = $order->getAddressId();

        if (!empty($userAddressObject) && is_object($userAddressObject)) {
            $userAddress = $order->getAddressId()->getAddress().', '.$order->getAddressId()->getCity();
        }

        $emailMessageText = 'Gautas naujas uzsakymas restoranui '.$order->getPlace()->getName()."\n"
            ."OrderId: " . $order->getId()."\n\n"
            ."Parinktas gamybos taskas adresu: ".$order->getPlacePoint()->getAddress().', '.$order->getPlacePoint()->getCity()."\n"
            ."\n"
            ."Uzsakovo vardas: ".$order->getUser()->getFirstname().' '.$order->getUser()->getLastname()."\n"
            ."Uzsakovo adresas: ".$userAddress."\n"
            ."Uzsakovo telefonas: ".$order->getUser()->getPhone()."\n"
            ."Uzsakovo el.pastas: ".$order->getUser()->getEmail()."\n"
            ."\n"
            ."Pristatymo tipas: ".$order->getDeliveryType()."\n"
            ."Apmokejimo tipas: ".$order->getPaymentMethod()."\n"
            ."Apmokejimo bukle: ".$order->getPaymentStatus()."\n"
        ;

        $emailMessageText .= "\n"
            ."Restoranui issiusta nuoroda: ".$this->container->get('router')
                ->generate('ordermobile', array('hash' => $order->getOrderHash()), true)
            ."\n";

        $mailer = $this->container->get('mailer');

        $message = \Swift_Message::newInstance()
            ->setSubject('Naujas uzsakymas restoranui: '.$order->getPlace()->getName())
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

        $domain = $this->container->getParameter('domain');
        $notifyEmails = $this->container->getParameter('order.accept_notify_emails');

        $userAddress = '';
        $userAddressObject = $order->getAddressId();

        if (!empty($userAddressObject) && is_object($userAddressObject)) {
            $userAddress = $order->getAddressId()->getAddress().', '.$order->getAddressId()->getCity();
        }

        $driverUrl = $this->container->get('router')
                ->generate('drivermobile', array('hash' => $order->getOrderHash()), true);

        $emailMessageText = 'Gautas naujas uzsakymas restoranui '.$order->getPlace()->getName()."\n"
            ."OrderId: " . $order->getId()."\n\n"
            ."Parinktas gamybos taskas adresu: ".$order->getPlacePoint()->getAddress().', '.$order->getPlacePoint()->getCity()."\n"
            ."\n"
            ."Uzsakovo vardas: ".$order->getUser()->getFirstname().' '.$order->getUser()->getLastname()."\n"
            ."Uzsakovo adresas: ".$userAddress."\n"
            ."Uzsakovo telefonas: ".$order->getUser()->getPhone()."\n"
            ."Uzsakovo el.pastas: ".$order->getUser()->getEmail()."\n"
            ."\n"
            ."Pristatymo tipas: ".$order->getDeliveryType()."\n"
            ."Apmokejimo tipas: ".$order->getPaymentMethod()."\n"
            ."Apmokejimo bukle: ".$order->getPaymentStatus()."\n"
            ."\n"
            ."Vairuotojas gaus nuoroda: ".$driverUrl
        ;

        $mailer = $this->container->get('mailer');

        $message = \Swift_Message::newInstance()
            ->setSubject('Naujas uzsakymas restoranui: '.$order->getPlace()->getName())
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
            self::$status_assiged,
            self::$status_forwarded,
            self::$status_completed,
            self::$status_finished,
            self::$status_canceled,
        );
    }


    /**
     * @param PlacePoint $placePoint
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

    public function workTimeErrorsReturn(PlacePoint $placePoint)
    {
        $errors = array();
        $this->workTimeErrors($placePoint, $errors);
        if (!empty($errors)) {
            return end($errors);
        }
        return "";
    }

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
     * @param Place $placeId
     * @param Request $request
     * @param $formHasErrors
     * @param $formErrors
     * @param $takeAway
     */
    public function validateDaGiantForm(Place $place, Request $request, &$formHasErrors, &$formErrors, $takeAway, $placePointId = null)
    {
        if (!$takeAway) {
            $list = $this->getCartService()->getCartDishes($place);
            $total_cart = $this->getCartService()->getCartTotal($list, $place);
            if ($total_cart < $place->getCartMinimum()) {
                $formErrors[] = 'order.form.errors.cartlessthanminimum';
            }

            $addrData = $this->container->get('food.googlegis')->getLocationFromSession();
            if (empty($addrData['address_orig'])) {
                $formErrors[] = 'order.form.errors.customeraddr';
            }
        } elseif ($place->getMinimalOnSelfDel()) {
            $list = $this->getCartService()->getCartDishes($place);
            $total_cart = $this->getCartService()->getCartTotal($list, $place);
            if ($total_cart < $place->getCartMinimum()) {
                $formErrors[] = 'order.form.errors.cartlessthanminimum_on_pickup';
            }
        }

        $pointRecord = null;

        if (empty($placePoint)) {
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

        if (0 === strlen($request->get('customer-email'))) {
            $formErrors[] = 'order.form.errors.customeremail';
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

        if (!empty($formErrors)) {
            $formHasErrors = true;
        }
    }


    public function generateCsvById($orderId)
    {
        $order = $this->getOrderById($orderId);
        $this->generateCsv($order);
    }

    public function generateCsv(Order $order)
    {
        $orderDetails = array();
        $foodTotalLine = 0;
        $drinksTotalLine = 0;
        $alcoholTotalLine = 0;
        foreach ($order->getDetails() as $detail)
        {
            $cats = $detail->getDishId()->getCategories();
            if (!empty($cats)) {
                $isDrink = $cats[0]->getDrinks();
                $isAlcohol = $cats[0]->getAlcohol();
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
                $foodTotalLine,
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
                $drinksTotalLine,
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
                $alcoholTotalLine,
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
                $order->getPlace()->getDeliveryPrice(),
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
}
