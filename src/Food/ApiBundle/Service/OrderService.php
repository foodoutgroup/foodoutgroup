<?php

namespace Food\ApiBundle\Service;

use Food\ApiBundle\Common\JsonRequest;
use Food\OrderBundle\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Food\OrderBundle\Service\OrderService as FO;
use Food\ApiBundle\Exceptions\ApiException;

class OrderService extends ContainerAware
{
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
                )
            );

        $results = $q->execute();

        foreach ($results as $row) {
            $returner[] = $row->getId();
        }

        return $returner;
    }

    public function createOrder(Request $requestOrig, JsonRequest $request)
    {
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
        if (!$user) {
            throw new ApiException(
                'Unauthorized',
                401,
                array(
                    'error' => 'Request requires a sesion_token',
                    'description' => $this->container->get('translator')->_('api.orders.user_not_authorized')
                )
            );
        }

        $em = $this->container->get('doctrine')->getManager();
        $serviceVar = $request->get('service');
        $pp = null; // placePoint :D - jei automatu - tai NULL :D
        if ($serviceVar['type'] == "pickup") {
            $pp = $em->getRepository('FoodDishesBundle:PlacePoint')->find($serviceVar['location_id']);
        }

        $basket = $em->getRepository('FoodApiBundle:ShoppingBasketRelation')->find($request->get('basket_id'));

        if (!$basket) {
            throw new ApiException(
                'Basket Not found',
                404,
                array(
                    'error' => 'Basket Not found',
                    'description' => $this->container->get('translator')->_('api.orders.basket_does_not_exists')
                )
            );
        }


        $cartService = $this->getCartService();
        $cartService->setNewSessionId($basket->getSession());

        $place = $basket->getPlaceId();
        if ($serviceVar['type'] != "pickup") {
            $list = $cartService->getCartDishes($basket->getPlaceId());
            $total_cart = $cartService->getCartTotal($list/*, $place*/);
            if ($total_cart < $place->getCartMinimum()) {
                throw new ApiException(
                    'Order Too Small',
                    0,
                    array(
                        'error' => 'Order Too Small',
                        'description' => $this->container->get('translator')->_('api.orders.order_to_small')
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
            }
        } elseif ($basket->getPlaceId()->getMinimalOnSelfDel()) {
            $list = $cartService->getCartDishes($basket->getPlaceId());
            $total_cart = $cartService->getCartTotal($list/*, $place*/);
            if ($total_cart < $place->getCartMinimum()) {
                throw new ApiException(
                    'Order Too Small',
                    0,
                    array(
                        'error' => 'Order Too Small',
                        'description' => $this->container->get('translator')->_('api.orders.order_to_small')
                    )
                );
            }
        }

        $os = $this->container->get('food.order');
        $os->getCartService()->setNewSessionId($cartService->getSessionId());

        $os->createOrderFromCart(
            $basket->getPlaceId()->getId(),
            $requestOrig->getLocale(),
            $user,
            $pp,
            $basket->getPlaceId()->getSelfDelivery()
        );

        $paymentMethod = $request->get('payment-type');
        $customerComment = (!empty($serviceVar['address']) ? $serviceVar['address']['comments'] : "");

        $os->setPaymentMethod('local');
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
        $os->saveOrder();
        $billingUrl = $os->billOrder();
        $order = $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order')->findOneBy(
            array(
                'id' => $os->getOrder()->getId()
            )
        );
        $this->container->get('doctrine')->getManager()->refresh($order);

        return $this->getOrderForResponse($order);
    }

    public function getCartService()
    {
        return $this->container->get('food.cart');
    }

    /**
     * @todo - FIX TO THE EPIC COMMON LEVEL
     *
     * @param Order $order
     *
     * @return array
     */
    public function getOrderForResponse(Order $order)
    {
        $message = $this->getOrderStatusMessage($order);

        $returner = array(
            'order_id' => $order->getId(),
            'total_price' => array(
                'amount' => $order->getTotal()*100,
                'currency' => 'LTL'
            ),
            'state' => array(
                'title' => $this->convertOrderStatus($order->getOrderStatus()),
                'info_number' => $order->getPlacePoint()->getPhone(),
                'message' => $message
            ),
            'details' => array(
                'restaurant_id' => $order->getPlace()->getId(),
                'restaurant_title' => $order->getPlace()->getName(),
                'payment_options' => array(
                    'cash' => true,
                    'credit_card' => $order->getPlace()->getCardOnDelivery()
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
        foreach ($order->getDetails() as $detail) {
            $sum = 0;
            $sum+= $detail->getPrice() * $detail->getQuantity();
            foreach ($detail->getOptions() as $option) {
                $sum+= $option->getPrice() * $option->getQuantity();
            }
            $returner[] = array(
                'title' => $detail->getDishName(), //.', '.$detail->getDishUnitName(), Po pokalbio su shernu - laikinai skipinam papildoma info.
                'count' => $detail->getQuantity(),
                'price' => array(
                    'amount' => $sum * 100,
                    'currency' => 'LTL'
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
                break;

            case FO::$deliveryDeliver:
            default:
                $deliveryType = 'delivery';
                $parsedAddress = $miscUtil->parseAddress(
                    $order->getAddressId()->getAddress()
                );
                break;
        }

        $returner = array(
            "type" => $deliveryType,
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
                'amount' => $order->getPlace()->getDeliveryPrice()*100,
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
            FO::$status_new => 'accepted',
            FO::$status_accepted => 'preparing',
            FO::$status_assiged => 'preparing',
            FO::$status_forwarded => 'preparing',
            FO::$status_delayed => 'delayed',
            FO::$status_completed => 'completed',
            FO::$status_failed => 'failed',
            FO::$status_finished => 'prepared',
            FO::$status_canceled => 'canceled',
        );

        if (!isset($statusMap[$status])) {
            throw new \InvalidArgumentException('Unknown status: '.$status);
        }

        return $statusMap[$status];
    }
}