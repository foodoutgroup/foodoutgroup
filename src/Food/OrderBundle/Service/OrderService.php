<?php

namespace Food\OrderBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\OptimisticLockException;
use Food\AppBundle\Entity\Driver;
use Food\CartBundle\Service\CartService;
use Food\DishesBundle\Entity\Dish;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\Coupon;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderDeliveryLog;
use Food\OrderBundle\Entity\OrderDetails;
use Food\OrderBundle\Entity\OrderDetailsOptions;
use Food\OrderBundle\Entity\OrderExtra;
use Food\OrderBundle\Entity\OrderLog;
use Food\OrderBundle\Entity\OrderMailLog;
use Food\OrderBundle\Entity\OrderStatusLog;
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
        'pickup' => 'ATSIEMIMAS'
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

    public function getEventDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     * @param int $placeId
     * @param PlacePoint $placePoint
     * @param boolean $fromConsole
     * @param string|null $orderDate
     * @return Order
     */
    public function createOrder($placeId, $placePoint=null, $fromConsole=false, $orderDate = null)
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
            $deliveryTime = new \DateTime("now");
            $deliveryTime->modify("+60 minutes");
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
     * @param string $status
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
        // Inform poor user, that his order was accepted
        if (in_array($this->getOrder()->getOrderStatus(), array(self::$status_new, self::$status_preorder))) {
            $recipient = $this->getOrder()->getOrderExtra()->getPhone();

            // SMS siunciam tik tuo atveju jei orderis ne is callcentro
            if ($this->getOrder()->getOrderFromNav() == false) {
                if (!empty($recipient)) {
                    $smsService = $this->container->get('food.messages');

                    $sender = $this->container->getParameter('sms.sender');

                    $translation = 'general.sms.user.order_accepted';
                    // Preorder message differs
                    if ($this->getOrder()->getPreorder()) {
                        $translation = 'general.sms.user.order_accepted_preorder';
                    }

                    if ($this->getOrder()->getDeliveryType() == self::$deliveryPickup) {
                        $translation = 'general.sms.user.order_accepted_pickup';

                        if ($this->getOrder()->getPreorder()) {
                            $translation = 'general.sms.user.order_accepted_pickup_preorder';
                        }
                    }

                    $placeName = $this->container->get('food.app.utils.language')
                        ->removeChars('lt', $this->getOrder()->getPlaceName(), false, false);
                    $placeName = ucfirst($placeName);
                    $place = $this->getOrder()->getPlace();

                    $text = $this->container->get('translator')
                        ->trans(
                            $translation,
                            array(
                                'restourant_name' => $placeName,
                                'delivery_time' => ($this->getOrder()->getDeliveryType() == self::$deliveryDeliver ? $place->getDeliveryTime() : $place->getPickupTime()),
                                'pre_delivery_time' => ($this->getOrder()->getDeliveryTime()->format('m-d H:i')),
//                                'restourant_phone' => $this->getOrder()->getPlacePoint()->getPhone()
                            ),
                            null,
                            $this->getOrder()->getLocale()
                        );

                    $message = $smsService->createMessage($sender, $recipient, $text, $this->getOrder());
                    $smsService->saveMessage($message);
                }
            }

            $this->getOrder()->setAcceptTime(new \DateTime("now"));
            $this->chageOrderStatus(self::$status_accepted, $source, $statusMessage);

            if ($this->getOrder()->getOrderFromNav() == false) {
                if (!$this->getOrder()->getPreorder()) {
                    $miscService = $this->container->get('food.app.utils.misc');

                    $timeShift = $miscService->parseTimeToMinutes($this->getOrder()->getPlacePoint()->getDeliveryTime());

                    if (empty($timeShift) || $timeShift <= 0) {
                        $timeShift = 60;
                    }

                    $dt = new \DateTime('now');
                    $dt->add(new \DateInterval('P0DT0H'.$timeShift.'M0S'));
                    $this->getOrder()->setDeliveryTime($dt);
                }
                $this->saveOrder();
                $this->_notifyOnAccepted();

                // Notify Dispatchers
                $this->notifyOrderAccept();
            }

            // Put for logistics
            $this->container->get('food.logistics')->putOrderForSend($this->getOrder());

            // Kitais atvejais tik keiciam statusa, nes gal taip reikia
        } else {
            $this->chageOrderStatus(self::$status_accepted, $source, $statusMessage);
        }

        $this->logDeliveryEvent($this->getOrder(), 'order_accepted');

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
                foreach ($options as $k => $opt) {
                    /*if ($k !=0) {
                        $ordersText.=", ";
                    }
                    $ordersText.=$opt->getDishOptionName();*/
                    $invoice[] = array(
                        'itm_name' => "  - " . $opt->getDishOptionName(),
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

        // TODO temp Beta.lt code
        $betaCode = '';
        if ($this->container->get('food.app.utils.misc')->getParam('beta_code_on', true) == 'on') {
            $betaCode = $this->getBetaCode();
        }

        $variables = array(
            'maisto_gamintojas' => $this->getOrder()->getPlace()->getName(),
            'maisto_ruosejas' => $this->getOrder()->getPlacePoint()->getAddress(),
            'uzsakymas' => $this->getOrder()->getId(),
            'adresas' => ($this->getOrder()->getDeliveryType() != self::$deliveryPickup ? $this->getOrder()->getAddressId()->getAddress() . ", " . $this->getOrder()->getAddressId()->getCity() : "--"),
            'pristatymo_data' => $this->getOrder()->getPlace()->getDeliveryTime(),
            'total_sum' => $this->getOrder()->getTotal(),
            'total_delivery' => ($this->getOrder()->getDeliveryType() == self::$deliveryDeliver ? $this->getOrder()->getDeliveryPrice() : 0),
            'total_card' => ($this->getOrder()->getDeliveryType() == self::$deliveryDeliver ? ($this->getOrder()->getTotal() - $this->getOrder()->getDeliveryPrice()) : $this->getOrder()->getTotal()),
            'invoice' => $invoice,
            'beta.lt_kodas' => $betaCode,
        );


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
            ->send();

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
    public function statusAssigned($source = null, $statusMessage = null)
    {
        // Inform poor user, that his order was accepted
        $order = $this->getOrder();
        $driver = $order->getDriver();
        if ($driver->getType() == 'local') {
            $messagingService = $this->container->get('food.messages');
            $logger = $this->container->get('logger');

            // Inform driver about new order that was assigned to him
            $orderConfirmRoute = $this->container->get('router')
                ->generate('drivermobile', array('hash' => $order->getOrderHash()), true);

            $restaurant_title = $order->getPlace()->getName();
            $internal_code = $order->getPlacePoint()->getInternalCode();
            if (!empty($internal_code)) {
                $restaurant_title = $restaurant_title . " - " . $internal_code;
            }

            $restaurant_address = $order->getAddressId()->getAddress() . " " . $order->getAddressId()->getCity();
            $curr_locale = $this->container->getParameter('locale');
            $languageUtil = $this->container->get('food.app.utils.language');

            $messageText = $languageUtil->removeChars(
                $curr_locale,
                $this->container->get('translator')->trans(
                    'general.sms.driver_assigned_order',
                    array(
                        'restaurant_title' => $restaurant_title,
                        'restaurant_address' => $restaurant_address,
                        'deliver_time' => $order->getDeliveryTime()->format("H:i")
                    )
                ) . $orderConfirmRoute,
                false
            );

            $max_len = 160;
            $all_message_len = mb_strlen($messageText, 'UTF-8');
            if ($all_message_len > $max_len) {
                $restaurant_title_len = mb_strlen($restaurant_title, 'UTF-8');
                $restaurant_address_len = mb_strlen($restaurant_address, 'UTF-8');
                $too_long_len = ($all_message_len - $max_len);

                if ($restaurant_title_len > 30 && $restaurant_address_len > 30) {
                    $restaurant_title = mb_strimwidth($restaurant_title, 0, ($restaurant_title_len - $too_long_len / 2), '');
                    $restaurant_address = mb_strimwidth($restaurant_address, 0, ($restaurant_address_len - $too_long_len / 2), '');
                } else {
                    if ($restaurant_title_len > $too_long_len) {
                        $restaurant_title = mb_strimwidth($restaurant_title, 0, ($restaurant_title_len - $too_long_len), '');
                    } elseif($restaurant_address_len > $too_long_len) {
                        $restaurant_address = mb_strimwidth($restaurant_address, 0, ($restaurant_address_len - $too_long_len), '');
                    }
                }

                $messageText = $languageUtil->removeChars(
                    $curr_locale,
                    $this->container->get('translator')->trans(
                        'general.sms.driver_assigned_order',
                        array(
                            'restaurant_title' => $restaurant_title,
                            'restaurant_address' => $restaurant_address,
                            'deliver_time' => $order->getOrderDate()->format("H:i")
                        )
                    ) . $orderConfirmRoute,
                    false
                );
            }

            $logger->alert("Sending message for driver about assigned order to number: " . $driver->getPhone() . ' with text "' . $messageText . '"');

            $message = $messagingService->createMessage(
                $this->container->getParameter('sms.sender'),
                $driver->getPhone(),
                $messageText,
                $order
            );
            $messagingService->saveMessage($message);
        }

        $this->logDeliveryEvent($this->getOrder(), 'order_assigned');

        $this->chageOrderStatus(self::$status_assiged, $source, $statusMessage);

        return $this;
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

        $this->createDiscountCode($order);

        if ($this->getOrder()->getOrderFromNav() == false) {
            $this->sendCompletedMail();
        }
        
        // Generuojam SF skaicius tik tada, jei restoranui ijungtas fakturu siuntimas
        if ($order->getPlace()->getSendInvoice()
            && !$order->getPlacePointSelfDelivery()
            && $order->getDeliveryType() == OrderService::$deliveryDeliver
            && !$order->getIsCorporateClient()) {
            $mustDoNavDelete = $this->setInvoiceDataForOrder();

            // Suplanuojam sf siuntima klientui
            $this->container->get('food.invoice')->addInvoiceToSend($order, $mustDoNavDelete);
        }
        
        return $this;
    }

    /**
     * @param null|string $source
     * @param null|string $statusMessage
     *
     * @return $this
     */
    public function statusPartialyCompleted($source=null, $statusMessage=null)
    {
        $this->chageOrderStatus(self::$status_partialy_completed, $source, $statusMessage);

        $this->sendCompletedMail(true);

        // Informuojam buhalterija
        $mailer = $this->container->get('mailer');
        $translator = $this->container->get('translator');
        $domain = $this->container->getParameter('domain');
        $financeEmail = $this->container->getParameter('accounting_email');
        $order = $this->getOrder();

        $message = \Swift_Message::newInstance()
            ->setSubject(
                $this->container->getParameter('title').': '
                .$translator->trans('general.email.partialy_completed')
                .' (#'.$order->getId().')'
            )
            ->setFrom('info@'.$domain)
        ;

        $message->addTo($financeEmail);
        // Issiimti
        $message->addCc('mantas@foodout.lt');

        $driver = $order->getDriver();
        if (!empty($driver)) {
            $driverName = $driver->getName();
        } else {
            $driverName = '';
        }

        $emailBody = $translator->trans('general.email.partialy_completed')."\n\n"
        .'Order ID: '.$order->getId()."\n"
        .'Vairuotojas: '.$driverName;

        $message->setBody($emailBody);
        $mailer->send($message);

        return $this;
    }

    /**
     * Sends client an email after order complete
     * @param boolean $partialy
     * @throws \Exception
     */
    public function sendCompletedMail($partialy = false)
    {
        $ml = $this->container->get('food.mailer');
        $slugUtil = $this->container->get('food.dishes.utils.slug');
        $slugUtil->setLocale($this->getOrder()->getLocale());

        // TODO darant LV - sutvarkyti URL ir sablonu ID
        $variables = array(
            'maisto_gamintojas' => $this->getOrder()->getPlace()->getName(),
            'miestas' => $this->getOrder()->getPlacePoint()->getCity(),
            'maisto_review_url' => 'http://www.foodout.lt/lt/'.$slugUtil->getSlugByItem(
                    $this->getOrder()->getPlace()->getId(),
                    'place'
                ).'/#detailed-restaurant-review'
        );

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
            ->send();

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
                $this->container->get('logger')->error('Error while getting unused SF number: '.$e->getMessage());
            }

            if (empty($sfNumber)) {
                // We failed. lets take a new one
                try {
                    $sfNumber = (int)$miscService->getParam('sf_next_number');
                    $miscService->setParam('sf_next_number', ($sfNumber + 1));
                    $this->logOrder($order, 'sf_number_assign', 'Assigning new SF number: '.$sfNumber);
                } catch (OptimisticLockException $e) {
                    sleep(1);
                    $sfNumber = (int)$miscService->getParam('sf_next_number');
                    $miscService->setParam('sf_next_number', ($sfNumber + 1));
                    $this->logOrder($order, 'sf_number_assign', 'Assigning new SF number: '.$sfNumber);
                }
            } else {
                // Log da shit for debuging purposes
                $this->logOrder($order, 'sf_number_assign', 'Assigning old unused SF number: '.$sfNumber);
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
    public function statusFinished($source=null, $statusMessage=null)
    {
        $this->chageOrderStatus(self::$status_finished, $source, $statusMessage);
        $this->logDeliveryEvent($this->getOrder(), 'order_finished');
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
        // Put for logistics to cancel on their side
        $this->container->get('food.logistics')->putOrderForSend($this->getOrder());

        // Importuotiems is 1822 nesiunciame cancel
        if ($this->getOrder()->getOrderFromNav() == false) {
            $this->informPaidOrderCanceled();
        }

        $this->logDeliveryEvent($this->getOrder(), 'order_canceled');

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

        $this->logDeliveryEvent($this->getOrder(), 'order_delayed');

        // Inform logistics
        $this->container->get('food.logistics')->putOrderForSend($this->getOrder());
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
            $e = new \Exception("Dude - no order here :)");
            // Log this shit, as this happens alot so we need info to debug
            $this->container->get('logger')->error(
                $e->getMessage()."\nTrace: ".$e->getTraceAsString()
            );
            throw $e;
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
     * @param string $comment
     *
     * @return UserAddress
     */
    public function createAddressMagic($user, $city, $address, $lat, $lon, $comment = null)
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
        }

        $userAddress->setUser($user)
            ->setCity($city)
            ->setAddress($address)
            ->setLat($lat)
            ->setLon($lon)
            ->setComment($comment);

        $this->getEm()->persist($userAddress);
        $this->getEm()->flush();

        return $userAddress;
    }

    /**
     * @param int $place
     * @param string $locale
     * @param \Food\UserBundle\Entity\User $user
     * @param PlacePoint $placePoint - placePoint, jei atsiima pats
     * @param bool $selfDelivery - ar klientas atsiims pats?
     * @param Coupon|null $coupon
     * @param array|null $userData
     * @param string|null $orderDate
     */
    public function createOrderFromCart($place, $locale='lt', $user, PlacePoint $placePoint=null, $selfDelivery = false, $coupon = null, $userData = null, $orderDate = null)
    {
        $this->createOrder($place, $placePoint, false, $orderDate);
        $this->getOrder()->setDeliveryType(
            ($selfDelivery ? 'pickup' : 'deliver')
        );
        $this->getOrder()->setLocale($locale);
        $this->getOrder()->setUser($user);

        if (!empty($orderDate)) {
            $this->getOrder()->setOrderStatus(self::$status_preorder)
                ->setPreorder(true);
        }

        $this->saveOrder();


        // save extra order data to separate table
        $orderExtra = new OrderExtra();
        $orderExtra->setOrder($this->getOrder());

        if (!empty($userData)) {
            $orderExtra->setFirstname($userData['firstname'])
                ->setLastname($userData['lastname'])
                ->setPhone($userData['phone'])
                ->setEmail($userData['email']);
        } else {
            $orderExtra->setFirstname($user->getFirstname())
                ->setLastname($user->getLastname())
                ->setPhone($user->getPhone())
                ->setEmail($user->getEmail());
        }

        $this->getOrder()->setOrderExtra($orderExtra);

        $sumTotal = 0;

        $placeObject = $this->container->get('food.places')->getPlace($place);
        $preSum = $this->getCartService()->getCartTotal($this->getCartService()->getCartDishes($placeObject));

        $deliveryPrice = $this->getCartService()->getDeliveryPrice(
            $this->getOrder()->getPlace(),
            $this->container->get('food.googlegis')->getLocationFromSession(),
            $this->getOrder()->getPlacePoint()
        );

        // Pritaikom nuolaida
        $discountPercent = 0;
        $discountSum = 0;

        if (!empty($coupon) && $coupon instanceof Coupon) {
            $order = $this->getOrder();
            $order->setCoupon($coupon)
                ->setCouponCode($coupon->getCode());

            if (!$coupon->getFreeDelivery()) {
                $discountSize = $coupon->getDiscount();
                if (!empty($discountSize)) {
                    $discountSum = $this->getCartService()->getTotalDiscount($this->getCartService()->getCartDishes($placeObject), $discountSize);
                    $discountPercent = $discountSize;
                } else {
                    $discountSum = $coupon->getDiscountSum();
                }
                $order->setDiscountSize($discountSize)
                    ->setDiscountSum($discountSum);
            } else {
                $deliveryPrice = 0;

                if ($order->getDiscountSum() == '')
                {
                    $order->setDiscountSum(0);
                }
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
            if ($origPrice == $price && $discountPercent > 0) {
                $price = round($origPrice * ((100 - $discountPercent)/100), 2);
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
                    ->setQuantity($cartDish->getQuantity()) // @todo Kolkas paveldimas. Veliau taps valdomas kiekvienam topingui atskirai
                    ->setOrderDetail($dish);
                $this->getEm()->persist($orderOpt);
                $this->getEm()->flush();

                $sumTotal += $cartDish->getQuantity() * $opt->getDishOptionId()->getPrice();
            }
        }

        // Nemokamas pristatymas dideliam krepseliui
        $miscService = $this->container->get('food.app.utils.misc');
        $enable_free_delivery_for_big_basket = $miscService->getParam('enable_free_delivery_for_big_basket');
        $free_delivery_price = $miscService->getParam('free_delivery_price');
        $self_delivery = $this->getOrder()->getPlace()->getSelfDelivery();
        $left_sum = 0;
        if ($enable_free_delivery_for_big_basket) {
            // Jeigu musu logistika, tada taikom nemokamo pristatymo logika
            if ($self_delivery) {
                // Kiek liko iki nemokamo pristatymo
                if ($free_delivery_price > $sumTotal) {
                    $left_sum = sprintf('%.2f', $free_delivery_price - $sumTotal);
                }
                // Krepselio suma pasieke nemokamo pristatymo suma
                if ($left_sum == 0) {
                    $deliveryPrice = 0;
                }
            }
        }

        if ($discountOverTotal > 0) {
            $deliveryPrice = $deliveryPrice - $discountOverTotal;
            if ($deliveryPrice < 0) {
                $deliveryPrice = 0;
            }
        }

        if(!$selfDelivery) {
            $sumTotal+= $deliveryPrice;
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
             ->dispatch(NavOrderEvent::MARK_ORDER, $event);
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
                array('navDeliveryOrder' => $id), null, 1
            );

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

    public function setMobileOrder($isMobile = true)
    {
        $order = $this->getOrder();
        $order->setMobile($isMobile);
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
        $this->setPaymentStatusWithoutSave($order, $status, $message);
        $this->saveOrder();
    }

    /**
     * @param Order $order
     * @param string $status
     * @param string $message
     * @throws \InvalidArgumentException
     */
    public function setPaymentStatusWithoutSave($order, $status, $message = null)
    {
        $this->logOrder($order, 'payment_status_change', sprintf('From %s to %s', $order->getPaymentStatus(), $status));

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
            self::$status_preorder => 0,
            self::$status_unapproved => 0,
            self::$status_new => 1,
            self::$status_accepted => 2,
            self::$status_delayed => 3,
            self::$status_forwarded => 3,
            self::$status_finished => 4,
            self::$status_assiged => 5,
            self::$status_failed => 5,
            self::$status_partialy_completed => 6,
            self::$status_completed => 6,
            self::$status_canceled => 6,
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
        $this->logPaymentWithoutSave($order, $event, $message, $debugData);
        $this->getEm()->flush();
    }

    public function logPaymentWithoutSave($order=null, $event, $message=null, $debugData=null)
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
    }

    /**
     * @param Driver $driver
     * @return array|\Food\OrderBundle\Entity\Order[]
     */
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
        $order = $this->getOrder();

        if (
            in_array(
                $order->getOrderStatus(),
                array(OrderService::$status_pre, OrderService::$status_unapproved)
            )
        ) {
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
            $orderConfirmRoute = 'http://'.$domain
                .$this->container->get('router')
                    ->generate('ordermobile', array('hash' => $order->getOrderHash()));

            $orderSmsTextTranslation = $translator->trans('general.sms.order_reminder');
            $orderTextTranslation = $translator->trans('general.email.order_reminder');
        } else {
            // Jei preorder - sms siuncia cronas ir nezino apie esama domena..
            if ($order->getPreorder()) {
                $orderConfirmRoute = 'http://'.$domain
                    .$this->container->get('router')
                    ->generate('ordermobile', array('hash' => $order->getOrderHash()));
            } else {
                $orderConfirmRoute = $this->container->get('router')
                    ->generate('ordermobile', array('hash' => $order->getOrderHash()), true);
            }

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

        $smsSenderNumber = $this->container->getParameter('sms.sender');

        // Siunciam SMS tik tuo atveju, jei neperduodam per Nav'a
        if (!$order->getPlace()->getNavision()) {
            $messagesToSend = array();

            $orderMessageRecipients = array(
                $placePoint->getPhone(),
                $placePoint->getAltPhone1(),
                $placePoint->getAltPhone2(),
            );

            foreach($orderMessageRecipients as $nr => $phone) {
                // Siunciam sms'a jei jis ne landline
                if (!empty($phone) && $miscUtils->isMobilePhone($phone, $country)) {
                    $logger->alert("Sending message for order #".$order->getId()." to be accepted to number: " . $phone . ' with text "' . $messageText . '"');

                    $messagesToSend[] = array(
                        'sender' => $smsSenderNumber,
                        'recipient' => $phone,
                        'text' => $messageText,
                        'order' => $order,
                    );
                } else if ($nr == 0) {
                    // Main phone is not mobile
                    $logger->alert('Main phone number for place point of place '.$placePoint->getPlace()->getName().' is set landline - no message sent');
                }
            }

            //send multiple messages
            $messagingService->addMultipleMessagesToSend($messagesToSend);
        }

        if (!$order->getOrderFromNav()) {
            $messagesToSend = array();
            $dispatcherPhones = $this->container->getParameter('dispatcher_phones');
            // If dispatcher phones are set - send them message about new order
            if (!empty($dispatcherPhones) && is_array($dispatcherPhones)) {
                $dispatcherMessageText = $translator->trans('general.sms.dispatcher_order', array(
                    'order_id' => $order->getId(),
                    'place_name' => $order->getPlaceName(),
                ));

                foreach ($dispatcherPhones as $phoneNum) {
                    $logger->alert("Sending message to dispatcher about order #" . $order->getId() . " to number: " . $phoneNum . ' with text "' . $dispatcherMessageText . '"');

                    $messagesToSend[] = array(
                        'sender' => $smsSenderNumber,
                        'recipient' => $phoneNum,
                        'text' => $dispatcherMessageText,
                        'order' => $order,
                    );
                }

                $messagingService->addMultipleMessagesToSend($messagesToSend);
            }
        }
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
            $userAddress = $order->getAddressId()->getAddress().', '.$order->getAddressId()->getCity();
        }

        $newOrderText = $translator->trans('general.new_unapproved_order.title');

        $emailMessageText = $newOrderText.' '.$order->getPlace()->getName()."\n"
            ."OrderId: " . $order->getId()."\n\n"
            .$translator->trans('general.new_order.selected_place_point').": ".$order->getPlacePoint()->getAddress().', '.$order->getPlacePoint()->getCity()."\n"
            .$translator->trans('general.new_order.place_point_phone').":".$order->getPlacePoint()->getPhone()."\n"
            ."\n"
            .$translator->trans('general.new_order.client_name').": ".$order->getUser()->getFirstname().' '.$order->getUser()->getLastname()."\n"
            .$translator->trans('general.new_order.client_address').": ".$userAddress."\n"
            .$translator->trans('general.new_order.client_phone').": ".$order->getOrderExtra()->getPhone()."\n"
            .$translator->trans('general.new_order.client_email').": ".$order->getOrderExtra()->getEmail()."\n"
            ."\n"
            .$translator->trans('general.new_order.delivery_type').": ".$order->getDeliveryType()."\n"
            .$translator->trans('general.new_order.payment_type').": ".$order->getPaymentMethod()."\n"
            .$translator->trans('general.new_order.payment_status').": ".$order->getPaymentStatus()."\n"
        ;

        $mailer = $this->container->get('mailer');

        $message = \Swift_Message::newInstance()
            ->setSubject($newOrderText.': '.$order->getPlace()->getName().' (#'.$order->getId().')')
            ->setFrom('info@'.$domain)
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
        $placePoint = $order->getPlacePoint();
        $placePointEmail = $placePoint->getEmail();
        $placePointAltEmail1 = $placePoint->getAltEmail1();
        $placePointAltEmail2 = $placePoint->getAltEmail2();
        $placePointAltPhone1 = $placePoint->getAltPhone1();
        $placePointAltPhone2 = $placePoint->getAltPhone2();

        $domain = $this->container->getParameter('domain');

        $orderConfirmRoute = $this->container->get('router')
            ->generate('ordermobile', array('hash' => $order->getOrderHash()), true);

        $orderSmsTextTranslation = $translator->trans('general.sms.canceled_order', array('%order_number%' => $order->getId()));
        $orderTextTranslation = $translator->trans('general.email.canceled_order');

        $messageText = $orderSmsTextTranslation
            .$orderConfirmRoute;

        // Jei placepoint turi emaila - vadinas siunciam jiems emaila :)
        if (!empty($placePointEmail)) {
            $logger->alert('--- Place asks for email, so we have sent an email about canceled order to: '.$placePointEmail);
            $emailMessageText = $messageText;
            $emailMessageText .= "\n" . $orderTextTranslation . ': '
                . $order->getPlacePoint()->getAddress() . ', ' . $order->getPlacePoint()->getCity();
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

        if (!$order->getPlace()->getNavision()) {
            // Siunciam sms'a
            $logger->alert("Sending message for order to be accepted to number: ".$placePoint->getPhone().' with text "'.$messageText.'"');
            $smsSenderNumber = $this->container->getParameter('sms.sender');

            $messagesToSend = array(
                array(
                    'sender' => $smsSenderNumber,
                    'recipient' => $placePoint->getPhone(),
                    'text' => $messageText,
                    'order' => $order,
                )
            );

            if (!empty($placePointAltPhone1) && $miscUtils->isMobilePhone($placePointAltPhone1, $country)) {
                $messagesToSend[] = array(
                    'sender' => $smsSenderNumber,
                    'recipient' => $placePointAltPhone1,
                    'text' => $messageText,
                    'order' => $order,
                );
            }
            if (!empty($placePointAltPhone2) && $miscUtils->isMobilePhone($placePointAltPhone2, $country)) {
                $messagesToSend[] = array(
                    'sender' => $smsSenderNumber,
                    'recipient' => $placePointAltPhone2,
                    'text' => $messageText,
                    'order' => $order,
                );
            }

            //send multiple messages
            $messagingService->addMultipleMessagesToSend($messagesToSend);
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
        if (in_array($order->getPaymentMethod(), array('local', 'local.card'))) {
            return;
        }

        $translator = $this->container->get('translator');

        $domain = $this->container->getParameter('domain');
        $notifyEmails = $this->container->getParameter('order.notify_emails');
        $cityCoordinators = $this->container->getParameter('order.city_coordinators');

        $emailSubject = $translator->trans('general.canceled_order.title');
        $emailMessageText = $emailSubject."\n\n"
            ."OrderId: " . $order->getId()."\n\n"
            .$translator->trans('general.new_order.selected_place_point').": ".$order->getPlacePoint()->getAddress().', '.$order->getPlacePoint()->getCity()."\n"
            .$translator->trans('general.new_order.place_point_phone').":".$order->getPlacePoint()->getPhone()."\n"
            ."\n"
            .$translator->trans('general.new_order.client_name').": ".$order->getOrderExtra()->getFirstname().' '.$order->getOrderExtra()->getLastname()."\n"
            .$translator->trans('general.new_order.client_phone').": ".$order->getOrderExtra()->getPhone()."\n"
            ."\n"
            .$translator->trans('general.new_order.delivery_type').": ".$order->getDeliveryType()."\n"
            .$translator->trans('general.new_order.payment_type').": ".$order->getPaymentMethod()."\n"
            .$translator->trans('general.new_order.payment_status').": ".$order->getPaymentStatus()."\n"
        ;

        $emailMessageText .= "\n"
            .$translator->trans('general.new_order.admin_link').": "
            .'http://'.$domain.$this->container->get('router')
                ->generate('order_support_mobile', array('hash' => $order->getOrderHash()), false)
            ."\n";

        $mailer = $this->container->get('mailer');

        $message = \Swift_Message::newInstance()
            ->setSubject($emailSubject.': '.$order->getPlace()->getName().' (#'.$order->getId().')')
            ->setFrom('info@'.$domain)
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
     * @param array $emails
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

            $this->logOrder($order, 'NAV_put_order');
            $nav->putTheOrderToTheNAV($orderRenew);

            $this->container->get('doctrine')->getManager()->refresh($orderRenew);

            sleep(1);
            $this->logOrder($order, 'NAV_update_prices');
            $returner = $nav->updatePricesNAV($orderRenew);
            sleep(1);
            $this->logOrder($order, 'NAV_update_prices_return', 'returner', $returner->return_value);
            if($returner->return_value == "TRUE") {
                $this->logOrder($order, 'NAV_process_order');
                $returner = $nav->processOrderNAV($orderRenew);
                if($returner->return_value == "TRUE") {

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
            .$translator->trans('general.new_order.client_phone').": ".$order->getOrderExtra()->getPhone()."\n"
            .$translator->trans('general.new_order.client_email').": ".$order->getOrderExtra()->getEmail()."\n"
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
            .$translator->trans('general.new_order.client_phone').": ".$order->getOrderExtra()->getPhone()."\n"
            .$translator->trans('general.new_order.client_email').": ".$order->getOrderExtra()->getEmail()."\n"
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

        $this->addEmailsToMessage($message, $notifyEmails);

        $message->setBody($emailMessageText);
        $mailer->send($message);
    }

    /**
     * @param Order $order
     * @param string $newStatus
     * @param null|string $source
     * @param null|string $message
     */
    public function logStatusChange($order=null, $newStatus, $source=null, $message=null)
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
     * @param Order $order
     * @param string $event
     */
    public function logDeliveryEvent($order=null, $event)
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
            }

            $log = new OrderDeliveryLog();
            $log->setOrder($order)
                ->setEventDate(new \DateTime('now'))
                ->setEvent($event)
                ->setSinceLast($sinceLast);

            $this->getEm()->persist($log);
            $this->getEm()->flush();
        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error happened: '.$e->getMessage());
        }
    }

    /**
     * @param Order $order
     * @param string $event
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

        return $repo->findOneBy(array(
            'order' => $order,
            'event' => $event
        ));
    }

    /**
     * @param Order $order
     * @param string $source
     * @param null|string $params
     */
    public function logMailSent($order, $source, $template, $params=null)
    {
        $log = new OrderMailLog();
        $log->setOrder($order)
            ->setEventDate(new \DateTime('now'))
            ->setSource($source)
            ->setTemplate($template)
            ->setParams(var_export($params, true));

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
        $timeFrTs = str_replace(":", "", $placePoint->{'getWd'.$wd.'Start'}());
        $timeTo = $placePoint->{'getWd'.$wd.'End'}();
        $timeToTs =  str_replace(":", "", $placePoint->{'getWd'.$wd.'EndLong'}());
		$currentTime = date("Hi");
		if (date("H") < 6) {
			$currentTime+=2400;
		}
        if(!strpos($timeFr, ':')|| !strpos($timeTo, ':')) {
            $errors[] = "order.form.errors.today_no_work";
        } else {

            if ($timeFrTs > $currentTime) {
                $errors[] = "order.form.errors.isnt_open";
            } elseif ($timeToTs < $currentTime) {
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
        $frm = $placePoint->{'getWd'.$wd.'Start'}();
        $tot = $placePoint->{'getWd'.$wd.'EndLong'}();
        if (empty($tot)) {
            $tot = $placePoint->{'getWd'.$wd.'End'}();
        }
        $timeFr = str_replace(":", "", $frm);
        $timeTo = str_replace(":", "", $tot);

        $totalH = date("H");
        $totalM = date("i");
        if ($totalH < 6) {
            $totalH = $totalH + 24;
        }
        $total = $totalH."".$totalM;

        if(!strpos($frm, ':')) {
            return false;
        } else {
            if ($timeFr > $total) {
                return false;
            } elseif ($timeTo < $total) {
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
        $phonePass = false;
        if (!$takeAway) {
            $list = $this->getCartService()->getCartDishes($place);
            foreach ($list as $itm) {
                if (!$this->isOrderableByTime($itm->getDishId())) {
                    $formErrors[] = array(
                        'message' => 'order.form.errors.dont_make_item',
                        'text' => $itm->getDishId()->getName()
                    );
                }
            }
            $total_cart = $this->getCartService()->getCartTotal($list/*, $place*/);

            $placePointMap = $this->container->get('session')->get('point_data');

            // TODO Trying to catch fatal when searching for PlacePoint
            if (!isset($placePointMap[$place->getId()]) || empty($placePointMap[$place->getId()])) {
                $this->container->get('logger')->alert('Trying to find PlacePoint without ID in OrderService - validateDaGiantForm fix part 1');
                // Mapping not found, lets try to remap
                $locationData = $this->container->get('food.googlegis')->getLocationFromSession();
                $placePointId = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->getPlacePointNear($place->getId(),$locationData);
                $placePointMap[$place->getId()] = $placePointId;
                $this->container->get('session')->set('point_data', $placePointMap);
            }

            // TODO - if still no PlacePoint info - pick fasterst or cheapest as in earlier solution
            if (!isset($placePointMap[$place->getId()]) || empty($placePointMap[$place->getId()])) {
                $this->container->get('logger')->alert('Trying to find PlacePoint without ID in OrderService - validateDaGiantForm fix part 2');
                $placePointId = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->getCheapestPlacePoint($place->getId(),$locationData);
                $placePointMap[$place->getId()] = $placePointId;
                $this->container->get('session')->set('point_data', $placePointMap);
            }

            /**
             * @todo Possible problems in the future here :)
             */
            $pointRecord = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointMap[$place->getId()]);
            $cartMinimum = $this->getCartService()->getMinimumCart(
                $place,
                $this->container->get('food.googlegis')->getLocationFromSession(),
                $pointRecord
            );

            if ($total_cart < $cartMinimum) {
                $formErrors[] = 'order.form.errors.cartlessthanminimum';
            }

            $addrData = $this->container->get('food.googlegis')->getLocationFromSession();
            if (empty($addrData['address_orig'])) {
                $formErrors[] = 'order.form.errors.customeraddr';
            }
        } elseif ($place->getMinimalOnSelfDel()) {
            $list = $this->getCartService()->getCartDishes($place);
            foreach ($list as $itm) {
                if (!$this->isOrderableByTime($itm->getDishId())) {
                    $formErrors[] = array(
                        'message' => 'order.form.errors.dont_make_item',
                        'text' => $itm->getDishId()->getName()
                    );
                }
            }
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
                if ($pointRecord) {
                    $isWork = $this->isTodayWork($pointRecord);
                    $locationData = $this->container->get('food.googlegis')->getLocationFromSession();
                    if (!$isWork) {
                        $placePointId = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->getPlacePointNear($place->getId(),$locationData);
                        $placePointMap[$place->getId()] = $placePointId;
                        $this->container->get('session')->set('point_data', $placePointMap);
                        if (empty($placePointId)) {
                            $formErrors[] = 'order.form.errors.no_restaurant_to_deliver';
                        } else {
                            $pointRecord = $this->getEm()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointId);
                        }
                    } else {
                        // Double check the place point for corrup detections
                        $pointForPlace = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->getPlacePointNear($place->getId(),$locationData);
                        if (!$pointForPlace) {
                            // no working placepoint for this restourant
                            $formErrors[] = 'order.form.errors.wrong_point_for_address';
                        } else {
                            $pointRecord = $this->getEm()->getRepository('FoodDishesBundle:PlacePoint')->find($pointForPlace);
                        }
                    }
                }
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
            // $formErrors[] = 'order.form.errors.customercomment';
            // UX improvement. Dejom skersa ant commentaro.
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

        // Validate bussines client
        $user = $this->container->get('security.context')->getToken()->getUser();
        $loggedIn = true;

        if (!$user instanceof User) {
            $loggedIn = false;
            $user = $this->container->get('fos_user.user_manager')->findUserByEmail($customerEmail);
        }
        if ($user instanceof User) {
            if ($user->getIsBussinesClient()) {
                // Bussines client must be logged in
                if (!$loggedIn) {
                    $formErrors[] = 'order.form.errors.bussines_client_not_loggedin';
                } else {
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
            } else if ($isValid && !in_array($numberType, array(\libphonenumber\PhoneNumberType::MOBILE, \libphonenumber\PhoneNumberType::FIXED_LINE_OR_MOBILE))) {
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
            );
            if (!$data['valid']) {
                $formHasErrors = true;
                if ($data['errcode']['code'] == "2" || $data['errcode']['code'] == "3") {
                    $formErrors[] = array(
                        'message' => 'order.form.errors.problems_with_dish',
                        'text' => $data['errcode']['problem_dish']
                    );
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
        $orderExtra = $this->getOrder()->getOrderExtra();
        $userPhone = $orderExtra->getPhone();
        $userEmail = $orderExtra->getEmail();

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
                // TODO rodome nebe restorano, o dispeceriu telefona
                'restourant_phone' => $this->container->getParameter('dispatcher_contact_phone'),
//                'restourant_phone' => $this->getOrder()->getPlacePoint()->getPhone(),
            )
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
    public function getUserOrders(User $user, $onlyFinished = false)
    {
        if (!($user instanceof User)) {
            throw new \InvalidArgumentException('Not a user is given, sorry..');
        }

        $orderStatuses = array(
            self::$status_accepted,
            self::$status_assiged,
            self::$status_delayed,
            self::$status_finished,
            self::$status_completed,
        );

        if ($onlyFinished) {
            $orderStatuses = array(self::$status_completed, self::$status_partialy_completed);
        }

        $em = $this->container->get('doctrine')->getManager();
        $orders = $em->getRepository('Food\OrderBundle\Entity\Order')
            ->findBy(
                array(
                    'user' => $user,
                    'order_status' => $orderStatuses
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

    /**
     * @param Dish $dish
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

    public function createDiscountCode(Order $order)
    {
        $generator = $this->container->get('doctrine')->getRepository('FoodOrderBundle:CouponGenerator')->findOneBy(array('active'=>1));
        if ($generator && ($order->getTotal() - $order->getDeliveryPrice() >= $generator->getCartAmount())) {
            $nowTime = new \DateTime('NOW');
            $proceed = true;
            if ($generator->getGenerateFrom() <= $nowTime && $generator->getGenerateTo() >= $nowTime) {
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
                        $randomStuff = array("niam", "niamniam", "skanu", "foodout");
                        $theCode = strtoupper($randomStuff[array_rand($randomStuff)]).$order->getId();
                    }
                    $newCode = new Coupon;
                    $newCode->setActive(true)
                        ->setCode( $theCode )
                        ->setName( $generator->getName()." - #".$order->getId() )
                        ->setDiscount( $generator->getDiscount() )
                        ->setDiscountSum( $generator->getDiscountSum() )
                        ->setOnlyNav( $generator->getOnlyNav() )
                        ->setEnableValidateDate( true )
                        ->setFreeDelivery( $generator->getFreeDelivery() )
                        ->setSingleUse( $generator->getSingleUse() )
                        ->setValidFrom( $generator->getValidFrom() )
                        ->setValidTo( $generator->getValidTo() )
                        ->setCreatedAt(new \DateTime('NOW'));

                    $this->container->get('food.mailer')
                        ->setVariable('code', $theCode )
                        ->setRecipient( $order->getOrderExtra()->getEmail() )
                        ->setId( $generator->getTemplateCode() )
                        ->send();

                    $this->logMailSent(
                        $order,
                        'create_discount_code',
                        $generator->getTemplateCode(),
                        array('code' => $theCode)
                    );

                    $this->container->get('doctrine')->getManager()->persist($newCode);
                    $this->container->get('doctrine')->getManager()->flush();
                }
            }
        }
    }

    /**
     * @param string $timeToDelivery
     *
     * @return array
     */
    public function getOrdersToBeLate($timeToDelivery)
    {
        $date = new \DateTime("-".$timeToDelivery." minute");

        return $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order')->getOrdersToBeLate($date);
    }

    /**
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getBetaCode()
    {
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
        $result =  $stmt->fetchAll();

        $code = $result[0];

        $codeEntity = $repo->find($code['id']);

        $theCode = $codeEntity->getCode();

        $em->remove($codeEntity);
        $em->flush();

        return $theCode;
    }
}
