<?php

namespace Food\ApiBundle\Service;

use Food\OrderBundle\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;

class OrderService extends ContainerAware
{
    public function createOrder(Request $request)
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

        $token = $request->headers->get('X-API-Authorization');
        $this->container->get('food_api.api')->loginByHash($token);
        $security = $this->container->get('security.context');
        $user = $security->getToken()->getUser();


        $em = $this->container->get('doctrine')->getManager();
        $serviceVar = $request->get('service');
        $pp = null; // placePoint :D - jei automatu - tai NULL :D
        if ($serviceVar['type'] == "pickup") {
            $pp = $em->getRepository('FoodDishesBundle:PlacePoint')->find($serviceVar['location_id']);
        }

        $basket = $em->getRepository('FoodApiBundle:ShoppingBasketRelation')->find($request->get('basket_id'));
        $os = $this->container->get('food.order');
        $googleGisService = $this->container->get('food.googlegis');
        $os->createOrderFromCart(
            $basket->getPlaceId()->getId(),
            $request->getLocale(),
            $user,
            $pp,
            $basket->getPlaceId()->getSelfDelivery()
        );



        $paymentMethod = $request->get('payment-type');
        $customerComment = $serviceVar['address']['comments'];
        $os->setPaymentMethod($paymentMethod);
        if ($serviceVar['type'] == "pickup") {
            $os->setDeliveryType($os::$deliveryPickup);
        } else {
            $os->setDeliveryType($os::$deliveryDeliver);
        }
        $os->setLocale($request->getLocale());
        if (!empty($customerComment)) {
            $os->getOrder()->setComment($customerComment);
        }
        $os->setPaymentStatus($os::$paymentStatusWait);

        // Update order with recent address information. but only if we need to deliver
        if ($deliveryType == $os::$deliveryDeliver) {
            $locationData = $googleGisService->getLocationFromSession();
            $address = $os->createAddressMagic(
                $user,
                $locationData['city'],
                $locationData['address_orig'],
                (string)$locationData['lat'],
                (string)$locationData['lng']
            );
            $os->getOrder()->setAddressId($address);
        }
        $os->saveOrder();
        $billingUrl = $os->billOrder();
        return $this->getOrderForResponse($os->getOrder());
    }

    /**
     * @todo - FIX TO THE EPIC COMMON LEVEL
     */
    public function getOrderForResponse(Order $order)
    {
        $returner = array(
            'order_id' => $order->getId(),
            'total_price' => array(
                'amount' => $order->getTotal()*100,
                'currency' => 'LTL'
            ),
            'state' => array(
                'title' => $order->getOrderStatus(),
                'info_number' => $order->getUser()->getPhone()
            ),
            'details' => array(
                'restaurant_id' => $order->getPlace()->getId(),
                'payment_options' => array(
                    'cach' => true,
                    'credit_card' => $order->getPlace()->getCardOnDelivery()
                ),
                'items' => $this->_getItemsForResponse($order)
            ),
            'service' => $this->_getServiceForResponse($order)
        );
    }

    /**
     * @param Order $order
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
                'title' => $detail->getDishName().', '.$detail->getDishUnitName(),
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
     */
    private function _getServiceForResponse(Order $order)
    {
        $returner = array();
        return $returner;
    }
}