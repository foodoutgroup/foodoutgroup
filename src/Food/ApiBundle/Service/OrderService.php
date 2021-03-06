<?php

namespace Food\ApiBundle\Service;

use Food\ApiBundle\Common\JsonRequest;
use Food\DishesBundle\Entity\DishSize;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\Coupon;
use Food\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Food\OrderBundle\Service\OrderService as FO;
use Food\ApiBundle\Exceptions\ApiException;


class OrderService extends ContainerAware
{
    /**
     * @param Request $requestOrig
     * @param JsonRequest $request
     *
     * @return array
     * @throws ApiException
     */
    public function getPendingOrders(Request $requestOrig, JsonRequest $request)
    {
        $returner = [];

        $token = $requestOrig->headers->get('X-API-Authorization');
        $this->container->get('food_api.api')->loginByHash($token);
        $security = $this->container->get('security.context');
        $user = $security->getToken()->getUser();

        $q = $this->container->get('doctrine')->getManager()->createQuery("SELECT o from Food\OrderBundle\Entity\Order o where o.user=?1 AND o.order_status IN (?2)")
            ->setParameter(1, $user->getId())
            ->setParameter(2,
                [
                    FO::$status_new,
                    FO::$status_accepted,
                    FO::$status_delayed,
                    FO::$status_assiged
                    //,FO::$status_unapproved
                ]
            );

        $results = $q->execute();

        foreach ($results as $row) {
            $returner[] = $row->getId();
        }

        return $returner;
    }

    /**
     * @param Request $requestOrig
     * @param JsonRequest $request
     * @param bool $isThisPre
     *
     * @return array
     * @throws ApiException
     * @throws \Exception
     */
    public function createOrder(Request $requestOrig, JsonRequest $request, $isThisPre = false)
    {

        $logger = $this->container->get('logger');
        $logger->alert("=================");
        $logger->alert("orderService->createOrder called");
        $locationInfo = array();
        $os = $this->container->get('food.order');
        /**
         * {
         * "basket_id": 1,
         * "service": {
         * "type": "delivery",
         * "address": {
         * "street": "Vokieciu g",
         * "house_number": 3,
         * "flat_number": 2,
         * "city": "Vilnius",
         * "city_id" "1", // optional
         * "comments": "Duru kodas 1234"
         * },
         * "discount": {
         * "code": "123456"
         * }
         * }
         * <!-- OR -->
         * "service": {
         * "type":"pickup",
         * "location_id": 1
         * }
         * }
         */
        $searchCrit = [
            'city' => null,
            'city_id' => null,
            'lat' => null,
            'lng' => null,
            'address' => null
        ];
        $lService = $this->container->get('food.location');

        $token = $requestOrig->headers->get('X-API-Authorization');
        $this->container->get('food_api.api')->loginByHash($token);
        $security = $this->container->get('security.context');
        $user = $security->getToken()->getUser();
        if (!$user || !$user instanceof User) {
            throw new ApiException(
                'Unauthorized',
                401,
                [
                    'error' => 'Request requires a sesion_token',
                    'description' => $this->container->get('translator')->trans('api.orders.user_not_authorized')
                ]
            );
        }

        $phone = $user->getPhone();
        if (empty($phone)) {
            throw new ApiException(
                'Unauthorized',
                401,
                [
                    'error' => 'Missing phone number',
                    'description' => $this->container->get('translator')->trans('api.orders.user_phone_empty')
                ]
            );
        }

        $country = $this->container->getParameter('country');
        $miscUtils = $this->container->get('food.app.utils.misc');
        if (!$miscUtils->isMobilePhone($phone, $country)) {
            throw new ApiException(
                'Unauthorized',
                401,
                [
                    'error' => 'Invalid phone number',
                    'description' => $this->container->get('translator')->trans('api.orders.user_phone_invalid')
                ]
            );
        }

        $em = $this->container->get('doctrine');
        $serviceVar = $request->get('service');

        $logger->alert('Service var givven: ');
        $logger->alert(var_export($serviceVar, true));
        $pp = null; // placePoint :D - jei automatu - tai NULL :D

        if ($serviceVar['type'] == "pickup") {
            // TODO Trying to catch fatal when searching for PlacePoint
            if (empty($serviceVar['location_id'])) {
                $this->container->get('logger')->error('Trying to find PlacePoint without ID in Api OrderService - createOrder');
            }
            $pp = $em->getRepository('FoodDishesBundle:PlacePoint')->find($serviceVar['location_id']);
        }

        $basket = $em->getRepository('FoodApiBundle:ShoppingBasketRelation')->find($request->get('basket_id'));

        $place = $basket->getPlaceId();

        if (strpos($place->getDeliveryOptions(), $serviceVar['type']) === false && $place->getDeliveryOptions() != $os::$deliveryPedestrian) {
            throw new ApiException(
                'Coupon for delivery',
                404,
                [
                    'error' => 'Chosen delivery option does not match restaurants delivery',
                    'description' => $this->container->get('translator')->trans('general.coupon.only_delivery')
                ]
            );
        }

        $placeService = $this->container->get('food.places');
        $adminFee = $placeService->getAdminFee($place);
        $useAdminFee = $placeService->useAdminFee($place);
        if ($useAdminFee && !$adminFee) {
            $adminFee = 0;
        }

        if (!$basket) {
            throw new ApiException(
                'Basket Not found',
                404,
                [
                    'error' => 'Basket Not found',
                    'description' => $this->container->get('translator')->trans('api.orders.basket_does_not_exists')
                ]
            );
        }

        $cartService = $this->getCartService();
        $cartService->setNewSessionId($basket->getSession());
        $list = $cartService->getCartDishes($basket->getPlaceId());
        $total_cart = $cartService->getCartTotalApi($list/*, $place*/);

        // search for alco inside the basket
        $require_lastname = $cartService->isAlcoholInCart($list);
        if ($require_lastname) {
            $lastname = $user->getLastname();
            if (empty($lastname)) {
                throw new ApiException(
                    'Unauthorized',
                    401,
                    [
                        'error' => 'Missing lastname',
                        'description' => $this->container->get('translator')->trans('api.orders.user_lastname_empty')
                    ]
                );
            }
        }

        // Discount code validation
        $coupon = null;
        $discountVar = $request->get('discount');

        if (!empty($discountVar) && !empty($discountVar['code'])) {
            $coupon = $os->getCouponByCode($discountVar['code']);
            if (empty($coupon) || !$coupon instanceof Coupon) {
                throw new ApiException(
                    'Coupon Not found',
                    404,
                    [
                        'error' => 'Coupon Not found',
                        'description' => $this->container->get('translator')->trans('api.orders.coupon_does_not_exists')
                    ]
                );
            }

            if (!$coupon->isAllowedForApi()) {
                throw new ApiException(
                    'Coupon for web',
                    404,
                    [
                        'error' => 'Coupon for web',
                        'description' => $this->container->get('translator')->trans('general.coupon.only_web')
                    ]
                );
            }

            if ($serviceVar['type'] == "pickup" && !$coupon->isAllowedForPickup()) {
                throw new ApiException(
                    'Coupon for delivery',
                    404,
                    [
                        'error' => 'Coupon only for delivery',
                        'description' => $this->container->get('translator')->trans('general.coupon.only_delivery')
                    ]
                );
            }
            if ($serviceVar['type'] != "pickup" && !$coupon->isAllowedForDelivery()) {
                throw new ApiException(
                    'Coupon for pickup',
                    404,
                    [
                        'error' => 'Coupon only for pickup',
                        'description' => $this->container->get('translator')->trans('general.coupon.only_pickup')
                    ]
                );
            }

            if (!$os->validateCouponForPlace($coupon, $place)
                || $coupon->getOnlyNav() && !$place->getNavision()
                || $coupon->getNoSelfDelivery() && $place->getSelfDelivery()
            ) {
                throw new ApiException(
                    'Coupon Wrong Place',
                    404,
                    [
                        'error' => 'Coupon Wrong Place',
                        'description' => $this->container->get('translator')->trans('general.coupon.wrong_place')
                    ]
                );
            }
            // online payment coupons disallowed in app until online payments will be made
            if ($coupon->getOnlinePaymentsOnly()) {
                throw new ApiException(
                    'Coupon Online Payments Only',
                    404,
                    [
                        'error' => 'Coupon Online Payments Only',
                        'description' => $this->container->get('translator')->trans('general.coupon.only_web')
                    ]
                );
            }
            // Coupon is still valid Begin
            if ($coupon->getEnableValidateDate()) {
                $now = date('Y-m-d H:i:s');
                if ($coupon->getValidFrom()->format('Y-m-d H:i:s') > $now) {
                    throw new ApiException(
                        'Coupon Not Valid Yet',
                        404,
                        [
                            'error' => 'Coupon Not Valid Yet',
                            'description' => $this->container->get('translator')->trans('api.orders.coupon_too_early')
                        ]
                    );
                }
                if ($coupon->getValidTo()->format('Y-m-d H:i:s') < $now) {
                    throw new ApiException(
                        'Coupon Expired',
                        404,
                        [
                            'error' => 'Coupon Expired',
                            'description' => $this->container->get('translator')->trans('api.orders.coupon_expired')
                        ]
                    );
                }
            }

            if ($coupon->getValidHourlyFrom() && $coupon->getValidHourlyFrom() > new \DateTime()) {
                throw new ApiException(
                    'Coupon Not Valid Yet',
                    404,
                    [
                        'error' => 'Coupon Not Valid Yet',
                        'description' => $this->container->get('translator')->trans('api.orders.coupon_too_early')
                    ]
                );
            }
            if ($coupon->getValidHourlyTo() && $coupon->getValidHourlyTo() < new \DateTime()) {
                throw new ApiException(
                    'Coupon Expired',
                    404,
                    [
                        'error' => 'Coupon Expired',
                        'description' => $this->container->get('translator')->trans('api.orders.coupon_expired')
                    ]
                );
            }
            // Coupon is still valid End

            $discountSize = $coupon->getDiscount();
            if (!empty($discountSize)) {
                $total_cart -= $this->getCartService()->getTotalDiscount($this->getCartService()->getCartDishes($place), $discountSize);
                $useAdminFee = false;
            } elseif (!$coupon->getFullOrderCovers()) {
                $total_cart -= $coupon->getDiscountSum();
                $useAdminFee = false;
            }

            if ($user->getIsBussinesClient() || ($serviceVar['type'] == "pickup" && !$place->getMinimalOnSelfDel())) {
                $useAdminFee = false;
            }

            if ($user->getIsBussinesClient() && $coupon->getB2b() == Coupon::B2B_NO) {
                throw new ApiException(
                    'Not for business',
                    404,
                    [
                        'error' => 'Not for business',
                        'description' => $this->container->get('translator')->trans('general.coupon.not_for_business')
                    ]
                );
            }

            if (!$user->getIsBussinesClient() && $coupon->getB2b() == Coupon::B2B_YES) {
                throw new ApiException(
                    'Only for business',
                    404,
                    [
                        'error' => 'Only for business',
                        'description' => $this->container->get('translator')->trans('general.coupon.only_for_business')
                    ]
                );
            }

            if ($os->isCouponUsed($coupon, $user)) {
                throw new ApiException(
                    'Not active',
                    404,
                    [
                        'error' => 'Not active',
                        'description' => $this->container->get('translator')->trans('general.coupon.not_active')
                    ]
                );
            }
        }


        if ($serviceVar['type'] != "pickup") {
            $placeService = $this->container->get('food.places');
            if (!$useAdminFee && ($placeService->getMinCartPrice($place->getId()) - $total_cart) >= 0.00001) { //Jei yra admin fee - cart'o minimumo netaikom
                throw new ApiException(
                    'Order Too Small',
                    400,
                    [
                        'error' => 'Order Too Small',
                        'description' => $this->container->get('translator')->trans('api.orders.order_to_small')
                    ]
                );
            }
            //if ($serviceVar)
            /**
             *         "address": {
             * "street": "Vokieciu g",
             * "house_number": 3,
             * "flat_number": 2,
             * "city": "Vilnius",
             * "city_id" : "1", // optional
             * "comments": "Duru kodas 1234"
             */
            if (empty($serviceVar['address']) || empty($serviceVar['address']['street'])) {
                throw new ApiException(
                    'Unavailable Address',
                    400,
                    [
                        'error' => 'Unavailable Address',
                        'description' => ''
                    ]
                );
            } else {
                $locationInfo = $lService->findByAddress($serviceVar['address']['street'] . " " . $serviceVar['address']['house_number'] . ", " . $serviceVar['address']['city']);

                $searchCrit = [
                    'city' => $locationInfo['city'],
                    'city_id' => $locationInfo['city_id'],
                    'latitude' => $locationInfo['latitude'],
                    'longitude' => $locationInfo['longitude'],
                    'address_orig' => $serviceVar['address']['street'] . " " . $serviceVar['address']['house_number']
                ];
                // Append flat if given
                if (isset($serviceVar['address']['flat_number']) && !empty($serviceVar['address']['flat_number'])) {
                    $searchCrit['address_orig'] .= ' - ' . $serviceVar['address']['flat_number'];
                }

                $placePointId = $em->getRepository('FoodDishesBundle:Place')->getPlacePointNear($basket->getPlaceId()->getId(), $searchCrit, true);

                if (!$placePointId) {
                    throw new ApiException(
                        'Place point not in radius',
                        400,
                        [
                            'error' => 'Place point not in radius',
                            'description' => $this->container->get('translator')->trans('cart.checkout.place_point_not_in_radius')
                        ]
                    );
                }

                $pp = $em->getRepository('FoodDishesBundle:PlacePoint')->find($placePointId);

            }
        } elseif ($basket->getPlaceId()->getMinimalOnSelfDel()) {
            $total_cart = $cartService->getCartTotal($list/*, $place*/);
            if (($place->getCartMinimum() - $total_cart) >= 0.00001 && !$useAdminFee) {
                throw new ApiException(
                    'Order Too Small',
                    0,
                    [
                        'error' => 'Order Too Small',
                        'description' => $this->container->get('translator')->trans('api.orders.order_to_small')
                    ]
                );
            }
        }

        $os->getCartService()->setNewSessionId($cartService->getSessionId());

        $dishesService = $this->container->get('food.dishes');
        foreach ($cartService->getCartDishes($basket->getPlaceId()) as $item) {
            $dish = $item->getDishId();
            if (!$dishesService->isDishAvailable($dish)) {
                throw new ApiException(
                    'Dish not available',
                    0,
                    [
                        'error' => 'Dish not available',
                        'description' => $this->container->get('translator')->trans('dishes.no_production')
                    ]
                );
            }
        }

        $placeType = $basket->getPlaceId()->getDeliveryOptions();

        if ($placeType != $os::$deliveryPedestrian) {
            $placeType = $request->get('service')['type'];
        }

        $signalToken = null;

        if($request->get('user')){
            $signalToken = $request->get('user')['token'];
        }

        $os->createOrderFromCart(
            $basket->getPlaceId()->getId(),
            $requestOrig->getLocale(),
            $user,
            $pp,
            ($serviceVar['type'] == "pickup" ? true : false),
            $coupon,
            null,
            null,
            $placeType,
            $locationInfo,
            $signalToken
        );

        $os->setMobileOrder(true);

        $paymentMethod = (isset($serviceVar['payment_option']) ? $serviceVar['payment_option'] : 'cash');
        $customerComment = (!empty($serviceVar['address']) ? $serviceVar['address']['comments'] : "");

        // overwrite old functionality
        switch ($paymentMethod) {
            case "cash":
                $paymentMethod = "local";
                break;
            case "credit_card":
                $paymentMethod = "local.card";
                break;
        }

        $os->setPaymentMethod($paymentMethod);

        if ($placeType == $os::$deliveryPedestrian) {
            $os->setDeliveryType($os::$deliveryPedestrian);
        } else {


            if ($serviceVar['type'] == "pickup") {
                $os->setDeliveryType($os::$deliveryPickup);
            } else {
                $os->setDeliveryType($os::$deliveryDeliver);
            }

        }
        $os->setLocale($requestOrig->getLocale());
        if (!empty($customerComment)) {
            $os->getOrder()->setComment($customerComment);
        }
        $os->setPaymentStatus($os::$paymentStatusWait);

        // Update order with recent address information. but only if we need to deliver
        if ($serviceVar['type'] != "pickup") {

//            ?!???
            $address = $os->createAddressMagic(
                $user,
                $searchCrit['city'],
                $searchCrit['address_orig'],
                (string)$searchCrit['latitude'],
                (string)$searchCrit['longitude'],
                '',
                (isset($searchCrit['city_id']) ? $searchCrit['city_id'] : null)
            );
            $os->getOrder()->setAddressId($address);
        }

        $os->getOrder()->setOrderStatus(
            \Food\OrderBundle\Service\OrderService::$status_pre
        );

        $os->saveOrder();
        if (!$isThisPre) {
            $billingUrl = $os->billOrder();
        }

        $this->container->get('doctrine')->getManager()->refresh($os->getOrder());

        return $this->getOrderForResponse($os->getOrder(), $list);
    }

    public function getCartService()
    {
        return $this->container->get('food.cart');
    }

    /**
     * @todo - FIX TO THE EPIC COMMON LEVEL
     *
     * @param Order $order
     * @param       $list
     *
     * @return array
     */
    public function getOrderForResponse(Order $order, $list = false)
    {
        $message = $this->getOrderStatusMessage($order);

        $title = $this->convertOrderStatus($order->getOrderStatus());
        if ($title == "pre") {
            $title = "waiting_user_confirmation";
        }

        if (!empty($list)) {
            $total_sum = (($this->getCartService()->getCartTotal($list) * 100));
            $total_sum = $total_sum + ($order->getDeliveryPrice() * 100) + ($order->getAdminFee() * 100);
        } else {
            $order_total = ($order->getTotal() * 100);
            if ($order_total > 0) {
                $total_sum = $order_total + ($order->getDiscountSum() * 100);
            } else {
                $total_sum = $order_total;
            }
        }

        // If coupon in use
        $discount = null;
        $coupon = $order->getCoupon();
        if (!empty($coupon)) {
            $discount['discount_sum'] = $order->getDiscountSum() * 100;
            $discount['discount_size'] = $order->getDiscountSize();
            $total_sum_with_discount = $total_sum - ($order->getDiscountSum() * 100);
            if ($total_sum_with_discount < 0) {
                $total_sum_with_discount = 0;
                $total_sum = 0;
                if (!$coupon->getFreeDelivery()) {
                    $total_sum = ($order->getDeliveryPrice() * 100);
                }
            }
            $discount['total_sum_with_discount'] = $total_sum_with_discount;
        }

        $productionTime = $order->getPlacePoint()->getProductionTime();
        if (empty($productionTime) || $productionTime == 0) {
            $productionTime = $order->getPlace()->getProductionTime();
        }

        if ($total_sum < 0) {
            $total_sum = 0;
        }

        $selectedPayment = [
            "name" => "[#cash#]",
            "code" => "local",
            "url" => null
        ];

        $paymentData = $this->container->getParameter('payment');

        if ($order->getPaymentStatus() != FO::$paymentStatusComplete && in_array($order->getPaymentMethod(), $paymentData['method'])) {
            $selectedPayment['name'] = $paymentData['title'][array_search($order->getPaymentMethod(), $paymentData['method'])];
            $selectedPayment['code'] = $order->getPaymentMethod();

            if ($order->getPaymentMethod() != "local" && $order->getPaymentMethod() != "local.card") {

                try {
                    $orderService = $this->container->get('food.order');
                    $paymentService = $orderService->getPaymentSystemByMethod($order->getPaymentMethod());
                    $paymentService->setOrder($order);
//                    $paymentService->setLocale($return)
                    $selectedPayment['url'] = $paymentService->bill(); // fix...
                } catch (\Exception $e) {
                    $this->container->get('logger')->addDebug("tata" . $e->getMessage());
                }
            }

        }

        $adminFee = 0;
        $placeService = $this->container->get('food.places');
        $useAdminFee = $placeService->useAdminFee($order->getPlace());


        if($order->getDeliveryType() != 'pickup') {
            $minCart = $placeService->getMinCartPrice($order->getPlace()->getId());

            if ($useAdminFee && (($minCart * 100) > $total_sum)) {
                $useAdminFee = true;
                $adminFee = $placeService->getAdminFee($order->getPlace()) * 100;
            } else {
                $useAdminFee = false;
            }

        }else{
            $useAdminFee = false;
        }

        $returner = [
            'order_id' => $order->getId(),
            'order_hash' => $order->getOrderHash(),
            'total_price' => [
                'admin_fee' => [
                    'enabled' => $useAdminFee, // todo admin-fee ar taikomas siam order admin fee
                    'amount' => $adminFee, // todo admin-fee // koks dydis yra admin fee jei taikomas
                ],
                //'amount' => $order->getTotal() * 100,
                'amount' => $total_sum, // todo admin-fee if enabled admin_fee and smaller than min cart add admin_fee size :)
                'currency' => $this->container->getParameter('currency_iso')
            ],
            'delivery_price' => $order->getDeliveryPrice(), //Kode cia ne *100?
            'place_point_self_delivery' => $order->getPlacePointSelfDelivery(),
            'payment_method' => $this->container->get('translator')->trans('mobile.payment.' . $order->getPaymentMethod()),
            'order_date' => $order->getOrderDate()->format('H:i'),
            'estimated_delivery_time' => $order->getDeliveryTime(),
            'order_date_full' => $order->getOrderDate(),
            'discount' => $discount,
            'state' => [
                'title' => $title,
                // TODO Rodome nebe restorano, o dispeceriu nr
                "info_number" => "+" . $this->container->getParameter('dispatcher_contact_phone'),
//                'info_number' => '+'.$order->getPlacePoint()->getPhone(),
                'message' => $message,
                'picked' => $order->getOrderPicked()
            ],
            'details' => [
                'restaurant_id' => $order->getPlace()->getId(),
                'restaurant_title' => $order->getPlace()->getName(),
                'restaurant_only_alcohol' => $order->getPlace()->getOnlyAlcohol(),
                'production_time' => (!empty($productionTime) && $productionTime > 0 ? $productionTime : 30),
                'payment_options' => [
                    'cash' => ($order->getPaymentMethod() == "local" ? true : false),
                    'credit_card' => ($order->getPaymentMethod() == "local.card" ? true : false),
                ],
                'payment' => $selectedPayment,
                'items' => $this->_getItemsForResponse($order)
            ],
            'service' => $this->_getServiceForResponse($order)
        ];

        return $returner;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    public function getOrderForResponseFull(Order $order)
    {
        $returner = $this->getOrderForResponse($order);

        if ($order->getAddressId()) {
            $returner['location'] = [
                'from' => [
                    'lat' => $order->getPlacePoint()->getLat(),
                    'lon' => $order->getPlacePoint()->getLon(),
                ],
                'to' => [
                    'lat' => $order->getAddressId()->getLat(),
                    'lon' => $order->getAddressId()->getLon(),
                ]
            ];
        }
        $returner['details']['restaurant_phone'] = $order->getPlacePoint()->getPhone();
        $returner['details']['restaurant_address'] = $order->getPlacePointAddress();
        $returner['details']['items'] = $this->_getItemsForResponseFull($order);
        $returner['service']['delivery_time'] = $order->getDeliveryTime()->format('Y-m-d H:i:s');
        $returner['service']['customer_firstname'] = $order->getOrderExtra()->getFirstname();
        $returner['service']['customer_lastname'] = $order->getOrderExtra()->getLastname();
        $returner['service']['customer_phone'] = $order->getOrderExtra()->getPhone();
        $returner['status'] = $order->getOrderStatus();
        if ($driver = $order->getDriver()) {
            $returner['driver'] = [
                'id' => $driver->getId(),
                'phone' => $driver->getPhone(),
                'name' => $driver->getName(),
            ];
        }

        $returner['prices'] = [
            'chart' => $order->getTotalBeforeDiscount(),
            'delivery' => $order->getDeliveryPrice(),
            'discount' => $order->getDiscountSum(),
            'total' => $order->getTotal()
        ];

        return $returner;
    }

    /**
     * @param Order $order
     * @param string $status
     * @param Request $request
     *
     * @return array
     */
    public function changeOrderStatus(Order $order, $status, $request = null)
    {
        $orderService = $this->container->get('food.order');
        if ($orderService->isValidOrderStatusChange($order->getOrderStatus(), $this->formToEntityStatus($status))) {
            switch ($status) {
                case 'confirm':
                    $orderService->statusAccepted('restourant_ng');
                    $orderService->setOrderPrepareTime($request->get('food_prepare_time'));
                    break;

                case 'delay':
                    $orderService->statusDelayed('restourant_ng', 'delay reason: ' . $request->get('delay_reason'));
                    $orderService->getOrder()->setDelayed(true);
                    if (!empty($request)) {
                        $orderService->getOrder()->setDelayReason($request->get('delay_reason'));
                        $orderService->getOrder()->setDelayDuration($request->get('delay_duration'));
                    }
                    $orderService->saveDelay();
                    break;

                case 'cancel':
                    $orderService->statusCanceled('restourant_ng');
                    break;

                case 'finish':
                    $orderService->statusFinished('restourant_ng');
                    break;

                case 'completed':
                    $orderService->statusCompleted('restourant_ng');
                    break;
            }

            $orderService->saveOrder();

            return array('status' => true, "new_status" => $status);
        } else {
            $errorMessage = sprintf(
                'Restoranas %s bande uzsakymui #%d pakeisti uzsakymo statusa is "%s" i "%s"',
                $orderService->getOrder()->getPlaceName(),
                $orderService->getOrder()->getId(),
                $order->getOrderStatus(),
                $this->formToEntityStatus($status)
            );
            $this->container->get('logger')->alert($errorMessage);
        }
    }

    /**
     * @param string $formStatus
     * @return string
     */
    public function formToEntityStatus($formStatus)
    {
        $statusTable = array(
            'confirm' => \Food\OrderBundle\Service\OrderService::$status_accepted,
            'delay' => \Food\OrderBundle\Service\OrderService::$status_delayed,
            'cancel' => \Food\OrderBundle\Service\OrderService::$status_canceled,
            'finish' => \Food\OrderBundle\Service\OrderService::$status_finished,
            'partialy_completed' => \Food\OrderBundle\Service\OrderService::$status_partialy_completed,
            'completed' => \Food\OrderBundle\Service\OrderService::$status_completed,
        );

        if (!isset($statusTable[$formStatus])) {
            return '';
        }

        return $statusTable[$formStatus];
    }

    /**
     * @param Order[] $orders
     * @return array
     */
    public function getOrdersForResponseFull($orders, $hash)
    {
        /**
         * @var PlacePoint $placePoint
         */
        $placePoint = $this->container->get('doctrine.orm.entity_manager')
            ->getRepository('FoodDishesBundle:PlacePoint')->findOneBy(['hash' => $hash]);


        if (!empty($placePoint)) {
            $ordersData = [
                'restaurant' => [
                    'title' => $placePoint->getPlace()->getName(),
                    'address' => $placePoint->getAddress(),
                    'logo' => $placePoint->getPlace()->getWebPath()
                ]
            ];
            /**
             * @var Order[] $orders
             */
            foreach ($orders as $order) {
                $ordersData['orders'][] = $order->__toArray();
            }
        } else {
            throw new \Exception('Place point not found.');
        }
        return $ordersData;
    }

    /**
     * @param Order $order
     *
     * @return string
     */
    public function getOrderStatusMessage(Order $order)
    {
        $message = '';

        if ($order->getDelayed()) {
            $message = $this->container->get('translator')->trans(
                'mobile.order_status.order_delayed',
                ['%delayTime%' => $order->getDelayDuration()]
            );
        }

        return $message;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    private function _getItemsForResponse(Order $order)
    {
        $returner = [];
        $currency = $this->container->getParameter('currency_iso');

        foreach ($order->getDetails() as $detail) {
            $this->container->get('doctrine')->getManager()->refresh($detail);
            $sum = 0;
            //$sum+= $detail->getPrice() * $detail->getQuantity();
            if ($detail->getDishId()->getDiscountPricesEnabled() && $order->getPlace()->getDiscountPricesEnabled()) {
                $current_price = $detail->getOrigPrice();
                $sizes = $detail->getDishId()->getSizes();
                foreach ($sizes as $size) {
                    /**
                     * @var $size DishSize
                     */
                    if ($size->getUnit()->getId() == $detail->getDishUnitId()->getId()) {
                        $current_price = $size->getCurrentPrice();
                    }
                }
                $sum += $current_price * $detail->getQuantity();
            } else {
                $sum += $detail->getOrigPrice() * $detail->getQuantity(); // egles prasymu rodom orig_price
            }

            $options = [];
            foreach ($detail->getOptions() as $option) {
                $sum += $option->getPrice() * $option->getQuantity();
                if ($option->getDishOptionId()) {
                    $options[] = [
                        'option_id' => $option->getDishOptionId()->getId(),
                        'price' => [
                            'count' => $option->getQuantity(),
                            'amount' => sprintf("%.0f", ($option->getQuantity() * $option->getPrice() * 100)),
                            'currency' => $currency,
                        ],
                        'title' => $option->getDishOptionName()
                    ];
                }
            }
            $sum = sprintf("%.0f", ($sum * 100));
            $returner[] = [
                'title' => $detail->getDishName(), //.', '.$detail->getDishUnitName(), Po pokalbio su shernu - laikinai skipinam papildoma info.
                'count' => $detail->getQuantity(),
                'options' => $options,
                'price' => [
                    'amount' => $sum,
                    'currency' => $currency
                ]
            ];
        }

        return $returner;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    private function _getItemsForResponseFull(Order $order)
    {
        $returner = [];
        $currency = $this->container->getParameter('currency_iso');

        foreach ($order->getDetails() as $detail) {
            $sum = 0;
            //$sum+= $detail->getPrice() * $detail->getQuantity();
            if ($detail->getDishId()->getDiscountPricesEnabled() && $order->getPlace()->getDiscountPricesEnabled()) {
                $current_price = $detail->getOrigPrice();
                $sizes = $detail->getDishId()->getSizes();
                foreach ($sizes as $size) {
                    if ($size->getUnit()->getId() == $detail->getDishUnitId()->getId()) {
                        $current_price = $size->getCurrentPrice();
                    }
                }
                $sum += $current_price * $detail->getQuantity();
            } else {
                $sum += $detail->getOrigPrice() * $detail->getQuantity(); // egles prasymu rodom orig_price
            }

            foreach ($detail->getOptions() as $option) {
                $sum += $option->getPrice() * $option->getQuantity();
            }
            $sum = sprintf("%.0f", ($sum * 100));
            $options = [];
            foreach ($detail->getOptions() as $option) {
                $options[] = $option->getDishOptionName();
            }
            $returner[] = [
                'title' => $detail->getDishName(),
                'unit' => $detail->getDishUnitName(),
                'options' => $options,
                'count' => $detail->getQuantity(),
                'price' => [
                    'amount' => $sum,
                    'currency' => $currency
                ]
            ];
        }

        return $returner;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    private function _getServiceForResponse(Order $order)
    {
        $miscUtil = $this->container->get('food.app.utils.misc');


        switch ($order->getDeliveryType()) {
            case FO::$deliveryPickup:
                $deliveryType = 'pickup';
                $parsedAddress = $miscUtil->parseAddress(
                    $order->getPlacePointAddress()
                );
                $time = $order->getPlace()->getPickupTime();
                break;

            case FO::$deliveryDeliver:
                $deliveryType = 'delivery';
                $time = $order->getPlace()->getDeliveryTime();
                $parsedAddress = $miscUtil->parseAddress(
                // @TODO check if addressId exists
                    $order->getAddressId()->getAddress()
                );
                break;

            case FO::$deliveryPedestrian:
                $deliveryType = 'pedestrian';
                $time = $this->container->get('food.places')->getPedestrianDeliveryTime() . ' min.';
                $parsedAddress = $miscUtil->parseAddress(
                // @TODO check if addressId exists
                    $order->getAddressId()->getAddress()
                );
                break;

            default:

                $parsedAddress = $miscUtil->parseAddress(
                // @TODO check if addressId exists
                    $order->getAddressId()->getAddress()
                );
                $time = $order->getPlace()->getDeliveryTime();
                break;
        }

        $returner = [
            "type" => $deliveryType,
            "time" => $time,
            "address" => [
                "street" => $parsedAddress['street'],
                "house_number" => $parsedAddress['house'],
                "flat_number" => $parsedAddress['flat'],
                "city" => $order->getPlacePointCity(),
                "comments" => $order->getComment()
            ],
        ];


        if ($cityObj = $order->getPlacePoint()->getCityId()) {
            $returner['address']['city'] = $cityObj->getTitle();
        }

        if ($order->getDeliveryType() == FO::$deliveryDeliver) {
            $returner['price'] = [
                //'amount' => $order->getPlace()->getDeliveryPrice()*100,
                'amount' => $order->getDeliveryPrice() * 100,
                'currency' => $this->container->getParameter('currency_iso'),
            ];
        }

        return $returner;
    }

    /**
     * @param string $status
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function convertOrderStatus($status)
    {
        $statusMap = [
            FO::$status_preorder => 'preorder',
            FO::$status_nav_problems => 'accepted',
            FO::$status_new => 'accepted',
            FO::$status_unapproved => 'accepted',
            FO::$status_accepted => 'preparing',
            FO::$status_assiged => 'preparing',
            FO::$status_forwarded => 'preparing',
            FO::$status_delayed => 'delayed',
            FO::$status_completed => 'completed',
            FO::$status_partialy_completed => 'completed',
            FO::$status_failed => 'failed',
            FO::$status_finished => 'prepared',
            FO::$status_canceled => 'canceled',
            FO::$status_canceled_produced => 'canceled_produced',
            FO::$status_pre => 'pre'
        ];

        if (!isset($statusMap[$status])) {
            throw new \InvalidArgumentException('Unknown status: ' . $status);
        }

        return $statusMap[$status];
    }
}
