<?php

namespace Food\OrderBundle\Service;

use Application\Sonata\UserBundle\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Food\CartBundle\Service\CartService;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderDetails;
use Food\OrderBundle\Entity\OrderDetailsOptions;
use Food\OrderBundle\Entity\OrderLog;
use Food\OrderBundle\Entity\OrderStatusLog;
use Food\OrderBundle\Entity\PaymentLog;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Security\Acl\Exception\Exception;

class OrderService extends ContainerAware
{
    private $localBiller = null;

    private $payseraBiller = null;

    // TODO statusu paaiskinimai
    /**
     * Naujas uzsakymas. Dar neperduotas restoranui
     * @var string
     * TODO PN aptarti flow su Juozu
     */
    public static $status_new = "new";
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
     * @param int $place
     * @return Order
     */
    public function createOrder($place)
    {

        $placeRecord = $this->getEm()->getRepository('FoodDishesBundle:Place')->find($place);
        $placePointMap = $this->container->get('session')->get('point_data');
        $pointRecord = $this->getEm()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointMap[$place]);

        $this->order = new Order();
        $user = $this->container->get('security.context')->getToken()->getUser();
        if ($user == 'anon.') {
            $user = null;
        }
        $this->order->setPlace($placeRecord);
        $this->order->setPlaceName($placeRecord->getName());

        $this->order->setPlacePoint($pointRecord);
        $this->order->setPlacePointAddress($pointRecord->getAddress());

        $this->order->setUser($user);
        $this->order->setOrderDate(new \DateTime("now"));
        $this->order->setVat($this->container->getParameter('vat'));
        $this->order->setOrderHash(
            $this->generateOrderHash($this->order)
        );

        return $this->getOrder();
    }

    /**
     * @param string $status
     * @param string|null $message
     */
    public function chageOrderStatus($status, $message=null)
    {
        // Let's log the shit out of it
        $this->logStatusChange($this->getOrder(), $status, $message);

        // Inform poor user, that his order was accepted
        if ($this->getOrder()->getOrderStatus() == self::$status_new
            && $status == self::$status_accepted) {

            $recipient = $this->getOrder()->getUser()->getPhone();

            if (!empty($recipient)) {
                $smsService = $this->container->get('food.messages');

                $sender = $this->container->getParameter('sms.sender');
                $text = $this->container->get('translator')
                    ->trans('general.sms.user.order_accepted', array(), null, $this->getOrder()->getLocale());

                $message = $smsService->createMessage($sender, $recipient, $text);
                $smsService->saveMessage($message);
            }
        }

        $this->getOrder()->setOrderStatus($status);
    }

    /**
     * @return $this
     */
    public function statusNew()
    {
        $this->chageOrderStatus(self::$status_new);
        return $this;
    }

    /**
     * @return $this
     */
    public function statusAccepted()
    {
        $this->chageOrderStatus(self::$status_accepted);
        return $this;
    }

    /**
     * @return $this
     */
    public function statusForwarded()
    {
        $this->chageOrderStatus(self::$status_forwarded);
        return $this;
    }

    /**
     * @return $this
     */
    public function statusCompleted()
    {
        $this->chageOrderStatus(self::$status_completed);
        return $this;
    }

    /**
     * @return $this
     */
    public function statusFinished()
    {
        $this->chageOrderStatus(self::$status_finished);
        return $this;
    }

    /**
     * @return $this
     */
    public function statusCanceled()
    {
        $this->chageOrderStatus(self::$status_canceled);
        return $this;
    }

    /**
     * @return $this
     */
    public function statusDelayed()
    {
        $this->chageOrderStatus(self::$status_delayed);
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
     * @param int $place
     * @param string $locale
     */
    public function createOrderFromCart($place, $locale='lt')
    {
        $this->createOrder($place);
        $this->getOrder()->setLocale($locale);
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

        $sumTotal+= $this->getOrder()->getPlace()->getDeliveryPrice();
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
     * @param string $city
     * @return array
     */
    public function getOrdersUnassigned($city)
    {
        $em = $this->container->get('doctrine')->getManager();
        $orders = $em->getRepository('Food\OrderBundle\Entity\Order')
            ->findBy(
                array(
                    'order_status' =>  array(self::$status_accepted, self::$status_delayed),
                    'place_point_city' => $city,
                    'deliveryType' => self::$deliveryDeliver,
                ),
                array('order_date' => 'ASC')
            );

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
        $em = $this->container->get('doctrine')->getManager();
        $orders = $em->getRepository('Food\OrderBundle\Entity\Order')
            ->findBy(
                array(
                    'order_status' =>  self::$status_assiged,
                    'place_point_city' => $city,
                    'deliveryType' => self::$deliveryDeliver,
                ),
                array('order_date' => 'ASC')
            );

        if (!$orders) {
            return array();
        }

        return $orders;
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
     * @param Order $order
     * @param string $newStatus
     * @param null|string $message
     */
    public function logStatusChange($order, $newStatus, $message=null)
    {
        $log = new OrderStatusLog();
        $log->setOrder($order)
            ->setEventDate(new \DateTime('now'))
            ->setOldStatus($order->getOrderStatus())
            ->setNewStatus($newStatus)
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
}