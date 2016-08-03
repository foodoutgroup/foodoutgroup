<?php

namespace Food\ApiBundle\Service;

use Food\ApiBundle\Common\JsonRequest;
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
     * @return array
     * @throws ApiException
     */
    public function getPendingOrders(Request $requestOrig, JsonRequest $request)
    {
        $returner = array();

        $token = $requestOrig->headers->get('X-API-Authorization');
        $this->container->get('food_api.api')->loginByHash($token);
        $security = $this->container->get('security.context');
        $user = $security->getToken()->getUser();

        $q = $this->container->get('doctrine')->getManager()->createQuery("SELECT o from Food\OrderBundle\Entity\Order o where o.user=?1 AND o.order_status IN (?2)")
            ->setParameter(1, $user->getId())
            ->setParameter(2,
                array(
                    FO::$status_new,
                    FO::$status_accepted,
                    FO::$status_delayed,
                    FO::$status_assiged
                    //,FO::$status_unapproved
                )
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
     * @return array
     * @throws ApiException
     * @throws \Exception
     */
    public function createOrder(Request $requestOrig, JsonRequest $request, $isThisPre = false)
    {
        $logger = $this->container->get('logger');
        $logger->alert("=================");
        $logger->alert("orderService->createOrder called");

        $os = $this->container->get('food.order');
        /**
         * {
            "basket_id": 1,
            "service": {
                "type": "delivery",
                "address": {
                    "street": "Vokieciu g",
                    "house_number": 3,
                    "flat_number": 2,
                   "city": "Vilnius",
                    "comments": "Duru kodas 1234"
                },
                "discount": {
                    "code": "123456"
                }
             }
        <!-- OR -->
            "service": {
                "type":"pickup",
                "location_id": 1
            }
        }
         */
        $searchCrit = array(
            'city' => null,
            'lat' => null,
            'lng' => null,
            'address' => null
        );
        $googleGisService = $this->container->get('food.googlegis');

        $token = $requestOrig->headers->get('X-API-Authorization');
        $this->container->get('food_api.api')->loginByHash($token);
        $security = $this->container->get('security.context');
        $user = $security->getToken()->getUser();
        if (!$user || !$user instanceof User) {
            throw new ApiException(
                'Unauthorized',
                401,
                array(
                    'error' => 'Request requires a sesion_token',
                    'description' => $this->container->get('translator')->trans('api.orders.user_not_authorized')
                )
            );
        }

        $phone = $user->getPhone();
        if (empty($phone)) {
            throw new ApiException(
                'Unauthorized',
                401,
                array(
                    'error' => 'Missing phone number',
                    'description' => $this->container->get('translator')->trans('api.orders.user_phone_empty')
                )
            );
        }

        $country = $this->container->getParameter('country');
        $miscUtils = $this->container->get('food.app.utils.misc');
        if (!$miscUtils->isMobilePhone($phone, $country)) {
            throw new ApiException(
                'Unauthorized',
                401,
                array(
                    'error' => 'Invalid phone number',
                    'description' => $this->container->get('translator')->trans('api.orders.user_phone_invalid')
                )
            );
        }

        $em = $this->container->get('doctrine')->getManager();
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

        if (!$basket) {
            throw new ApiException(
                'Basket Not found',
                404,
                array(
                    'error' => 'Basket Not found',
                    'description' => $this->container->get('translator')->trans('api.orders.basket_does_not_exists')
                )
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
                    array(
                        'error' => 'Missing lastname',
                        'description' => $this->container->get('translator')->trans('api.orders.user_lastname_empty')
                    )
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
                    array(
                        'error' => 'Coupon Not found',
                        'description' => $this->container->get('translator')->trans('api.orders.coupon_does_not_exists')
                    )
                );
            }

            if (!$coupon->isAllowedForApi()) {
                throw new ApiException(
                    'Coupon for web',
                    404,
                    array(
                        'error' => 'Coupon for web',
                        'description' => $this->container->get('translator')->trans('general.coupon.only_web')
                    )
                );
            }

            if ($serviceVar['type'] == "pickup" && !$coupon->isAllowedForPickup()) {
                throw new ApiException(
                    'Coupon for delivery',
                    404,
                    array(
                        'error' => 'Coupon only for delivery',
                        'description' => $this->container->get('translator')->trans('general.coupon.only_delivery')
                    )
                );
            }
            if ($serviceVar['type'] != "pickup" && !$coupon->isAllowedForDelivery()) {
                throw new ApiException(
                    'Coupon for pickup',
                    404,
                    array(
                        'error' => 'Coupon only for pickup',
                        'description' => $this->container->get('translator')->trans('general.coupon.only_pickup')
                    )
                );
            }

            if (!$os->validateCouponForPlace($coupon, $place)
                || $coupon->getOnlyNav() && !$place->getNavision()
                || $coupon->getNoSelfDelivery() && $place->getSelfDelivery()) {
                throw new ApiException(
                    'Coupon Wrong Place',
                    404,
                    array(
                        'error' => 'Coupon Wrong Place',
                        'description' => $this->container->get('translator')->trans('general.coupon.wrong_place')
                    )
                );
            }
            // online payment coupons disallowed in app until online payments will be made
            if ($coupon->getOnlinePaymentsOnly()) {
                throw new ApiException(
                    'Coupon Online Payments Only',
                    404,
                    array(
                        'error' => 'Coupon Online Payments Only',
                        'description' => $this->container->get('translator')->trans('general.coupon.only_web')
                    )
                );
            }
            // Coupon is still valid Begin
            if ($coupon->getEnableValidateDate()) {
                $now = date('Y-m-d H:i:s');
                if ($coupon->getValidFrom()->format('Y-m-d H:i:s') > $now) {
                    throw new ApiException(
                        'Coupon Not Valid Yet',
                        404,
                        array(
                            'error' => 'Coupon Not Valid Yet',
                            'description' => $this->container->get('translator')->trans('api.orders.coupon_too_early')
                        )
                    );
                }
                if ($coupon->getValidTo()->format('Y-m-d H:i:s') < $now) {
                    throw new ApiException(
                        'Coupon Expired',
                        404,
                        array(
                            'error' => 'Coupon Expired',
                            'description' => $this->container->get('translator')->trans('api.orders.coupon_expired')
                        )
                    );
                }
            }

            if ($coupon->getValidHourlyFrom() && $coupon->getValidHourlyFrom() > new \DateTime()) {
                throw new ApiException(
                    'Coupon Not Valid Yet',
                    404,
                    array(
                        'error' => 'Coupon Not Valid Yet',
                        'description' => $this->container->get('translator')->trans('api.orders.coupon_too_early')
                    )
                );
            }
            if ($coupon->getValidHourlyTo() && $coupon->getValidHourlyTo() < new \DateTime()) {
                throw new ApiException(
                    'Coupon Expired',
                    404,
                    array(
                        'error' => 'Coupon Expired',
                        'description' => $this->container->get('translator')->trans('api.orders.coupon_expired')
                    )
                );
            }
            // Coupon is still valid End

            $discountSize = $coupon->getDiscount();
            if (!empty($discountSize)) {
                $total_cart -= $this->getCartService()->getTotalDiscount($this->getCartService()->getCartDishes($place), $discountSize);
            } elseif (!$coupon->getFullOrderCovers()) {
                $total_cart -= $coupon->getDiscountSum();
            }

            if ($user->getIsBussinesClient() && $coupon->getB2b() == Coupon::B2B_NO) {
                throw new ApiException(
                    'Not for business',
                    404,
                    array(
                        'error' => 'Not for business',
                        'description' => $this->container->get('translator')->trans('general.coupon.not_for_business')
                    )
                );
            }

            if (!$user->getIsBussinesClient() && $coupon->getB2b() == Coupon::B2B_YES) {
                throw new ApiException(
                    'Only for business',
                    404,
                    array(
                        'error' => 'Only for business',
                        'description' => $this->container->get('translator')->trans('general.coupon.only_for_business')
                    )
                );
            }

            if ($os->isCouponUsed($coupon, $user)) {
                throw new ApiException(
                    'Not active',
                    404,
                    array(
                        'error' => 'Not active',
                        'description' => $this->container->get('translator')->trans('general.coupon.not_active')
                    )
                );
            }
        }

        if ($serviceVar['type'] != "pickup") {
            if ($total_cart < $place->getCartMinimum()) {
                throw new ApiException(
                    'Order Too Small',
                    400,
                    array(
                        'error' => 'Order Too Small',
                        'description' => $this->container->get('translator')->trans('api.orders.order_to_small')
                    )
                );
            }

            //if ($serviceVar)
            /**
             *         "address": {
            "street": "Vokieciu g",
            "house_number": 3,
            "flat_number": 2,
            "city": "Vilnius",
            "comments": "Duru kodas 1234"
             */
            if (empty($serviceVar['address']) ||  empty($serviceVar['address']['street'])) {
                throw new ApiException(
                    'Unavailable Address',
                    400,
                    array(
                        'error' => 'Unavailable Address',
                        'description' => ''
                    )
                );
            } else {
                $placeData = $googleGisService->getPlaceData(
                    $serviceVar['address']['street']." ".$serviceVar['address']['house_number'].",".$serviceVar['address']['city']
                );
                $locationInfo = $googleGisService->groupData(
                    $placeData,
                    $serviceVar['address']['street']." ".$serviceVar['address']['house_number'],
                    $serviceVar['address']['city']
                );
                $searchCrit = array(
                    'city' => $locationInfo['city'],
                    'lat' => $locationInfo['lat'],
                    'lng' => $locationInfo['lng'],
                    'address_orig' => $serviceVar['address']['street']." ".$serviceVar['address']['house_number']
                );
                // Append flat if given
                if (isset($serviceVar['address']['flat_number']) && !empty($serviceVar['address']['flat_number'])) {
                    $searchCrit['address_orig'] .= ' - '.$serviceVar['address']['flat_number'];
                }

                $pp = $em->getRepository('FoodDishesBundle:PlacePoint')->find(
                    $em->getRepository('FoodDishesBundle:Place')->getPlacePointNear(
                        $basket->getPlaceId()->getId(),
                        $searchCrit,
                        true
                    )
                );

            }
        } elseif ($basket->getPlaceId()->getMinimalOnSelfDel()) {
            $total_cart = $cartService->getCartTotal($list/*, $place*/);
            if ($total_cart < $place->getCartMinimum()) {
                throw new ApiException(
                    'Order Too Small',
                    0,
                    array(
                        'error' => 'Order Too Small',
                        'description' => $this->container->get('translator')->trans('api.orders.order_to_small')
                    )
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
                    array(
                        'error' => 'Dish not available',
                        'description' => $this->container->get('translator')->trans('dishes.no_production')
                    )
                );
            }
        }

        $os->createOrderFromCart(
            $basket->getPlaceId()->getId(),
            $requestOrig->getLocale(),
            $user,
            $pp,
            ($serviceVar['type'] == "pickup" ? true : false),
            $coupon
        );

        $os->setMobileOrder(true);

        $paymentMethod = (isset($serviceVar['payment_option']) ? $serviceVar['payment_option'] : 'cash');
        $customerComment = (!empty($serviceVar['address']) ? $serviceVar['address']['comments'] : "");

        $os->setPaymentMethod(($paymentMethod == 'cash' ? 'local':'local.card'));

        @mail("paulius@foodout.lt", "MOBILE REQUEST JSONobject", print_r($request, true), "FROM: info@foodout.lt");

        if ($serviceVar['type'] == "pickup") {
            $os->setDeliveryType($os::$deliveryPickup);
        } else {
            $os->setDeliveryType($os::$deliveryDeliver);
        }
        $os->setLocale($requestOrig->getLocale());
        if (!empty($customerComment)) {
            $os->getOrder()->setComment($customerComment);
        }
        $os->setPaymentStatus($os::$paymentStatusWait);

        // Update order with recent address information. but only if we need to deliver
        if ($serviceVar['type']!="pickup") {
            // $locationData = $googleGisService->getLocationFromSession();
            $address = $os->createAddressMagic(
                $user,
                $searchCrit['city'],
                $searchCrit['address_orig'],
                (string)$searchCrit['lat'],
                (string)$searchCrit['lng']
            );
            $os->getOrder()->setAddressId($address);
        }
        if ($isThisPre) {
            $os->getOrder()->setOrderStatus(
                \Food\OrderBundle\Service\OrderService::$status_pre
            );
        }
        $os->saveOrder();
        if (!$isThisPre) {
            $billingUrl = $os->billOrder();
        }
        $order = $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order')->findOneBy(
            array(
                'id' => $os->getOrder()->getId()
            )
        );

        $this->container->get('doctrine')->getManager()->refresh($order);

        return $this->getOrderForResponse($order, $list);
    }

    public function getCartService()
    {
        return $this->container->get('food.cart');
    }

    /**
     * @todo - FIX TO THE EPIC COMMON LEVEL
     *
     * @param Order $order
     * @param $list
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
            $total_sum = $total_sum + ($order->getDeliveryPrice() * 100);
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

        $returner = array(
            'order_id' => $order->getId(),
            'total_price' => array(
                //'amount' => $order->getTotal() * 100,
                'amount' => $total_sum,
                'currency' => $this->container->getParameter('currency_iso')
            ),
            'discount' => $discount,
            'state' => array(
                'title' => $title,
                // TODO Rodome nebe restorano, o dispeceriu nr
                "info_number" => "+".$this->container->getParameter('dispatcher_contact_phone'),
//                'info_number' => '+'.$order->getPlacePoint()->getPhone(),
                'message' => $message
            ),
            'details' => array(
                'restaurant_id' => $order->getPlace()->getId(),
                'restaurant_title' => $order->getPlace()->getName(),
                'payment_options' => array(
                    'cash' => ($order->getPaymentMethod() == "local" ? true: false),
                    'credit_card' => ($order->getPaymentMethod() == "local.card" ? true: false),
                ),
                'items' => $this->_getItemsForResponse($order)
            ),
            'service' => $this->_getServiceForResponse($order)
        );
        return $returner;
    }

    /**
     * @param Order $order
     * @return string
     */
    public function getOrderStatusMessage(Order $order)
    {
        $message = '';

        if ($order->getDelayed()) {
            $message = $this->container->get('translator')->trans(
                'mobile.order_status.order_delayed',
                array('%delayTime%' => $order->getDelayDuration())
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
        $returner = array();
        $currency = $this->container->getParameter('currency_iso');

        foreach ($order->getDetails() as $detail) {
            $sum = 0;
            //$sum+= $detail->getPrice() * $detail->getQuantity();
            if ($detail->getDishId()->getDiscountPricesEnabled() && $order->getPlace()->getDiscountPricesEnabled()) {
                $current_price = $detail->getOrigPrice();
                $sizes = $detail->getDishId()->getSizes();
                foreach ($sizes as $size) {
                    if ($size->getUnit()->getId() == $detail->getDishUnitId()) {
                        $current_price = $size->getCurrentPrice();
                    }
                }
                $sum+= $current_price * $detail->getQuantity();
            } else {
                $sum+= $detail->getOrigPrice() * $detail->getQuantity(); // egles prasymu rodom orig_price
            }

            foreach ($detail->getOptions() as $option) {
                $sum+= $option->getPrice() * $option->getQuantity();
            }
            $sum = sprintf("%.0f", ($sum * 100));
            $returner[] = array(
                'title' => $detail->getDishName(), //.', '.$detail->getDishUnitName(), Po pokalbio su shernu - laikinai skipinam papildoma info.
                'count' => $detail->getQuantity(),
                'price' => array(
                    'amount' => $sum,
                    'currency' => $currency
                )
            );
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

        switch($order->getDeliveryType()) {
            case FO::$deliveryPickup:
                $deliveryType = 'pickup';
                $parsedAddress = $miscUtil->parseAddress(
                    $order->getPlacePointAddress()
                );
                $time = $order->getPlace()->getPickupTime();
                break;

            case FO::$deliveryDeliver:
            default:
                $deliveryType = 'delivery';
                $parsedAddress = $miscUtil->parseAddress(
                    // @TODO check if addressId exists
                    $order->getAddressId()->getAddress()
                );
                $time = $order->getPlace()->getDeliveryTime();
                break;
        }

        $returner = array(
            "type" => $deliveryType,
            "time" => $time,
            "address" => array(
                "street" => $parsedAddress['street'],
                "house_number" => $parsedAddress['house'],
                "flat_number" => $parsedAddress['flat'],
                "city" => $order->getPlacePointCity(),
                "comments" => $order->getComment()
            ),
        );

        if ($order->getDeliveryType() == FO::$deliveryDeliver) {
            $returner['price'] = array(
                //'amount' => $order->getPlace()->getDeliveryPrice()*100,
                'amount' => $order->getDeliveryPrice() * 100,
                'currency' => $this->container->getParameter('currency_iso'),
            );
        }

        return $returner;
    }

    /**
     * @param string $status
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function convertOrderStatus($status)
    {
        $statusMap = array(
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
            FO::$status_pre => 'pre'
        );

        if (!isset($statusMap[$status])) {
            throw new \InvalidArgumentException('Unknown status: '.$status);
        }

        return $statusMap[$status];
    }
}
