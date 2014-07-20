<?php

namespace Food\ApiBundle\Service;

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
            null, //@todo User - holy... is kur parauci ?:) - flow klausimai
            $pp,
            $basket->getPlaceId()->getSelfDelivery()
        );



        $paymentMethod = $request->get('payment-type');
        $customerComment = $serviceVar['address']['comments']
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

    }
}