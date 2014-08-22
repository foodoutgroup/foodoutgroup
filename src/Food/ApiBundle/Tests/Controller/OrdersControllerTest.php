<?php

namespace Food\ApiBundle\Tests\Controller;

use Food\AppBundle\Test\WebTestCase;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\OrderService;
use Food\UserBundle\Entity\User;
use Food\UserBundle\Entity\UserAddress;

class OrdersControllerTest extends WebTestCase
{
    public function testOrderStatusActionNotFound()
    {
        $this->client->request(
            'GET',
            '/api/v1/orders/12345678/status'
        );

        $this->assertEquals('Food\ApiBundle\Controller\OrdersController::getOrderStatusAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(404 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'Order not found') !== false));
    }

    public function testOrderStatusActionNew()
    {
        $place = $this->getPlace('Test place');
        $placePoint = $this->getPlacePoint($place);
        $orderNew = $this->getOrder($place, $placePoint, OrderService::$status_new);

        $expectedNewOrderData = array(
            "order_id" => $orderNew->getId(),
            "status" => array(
                "state" => 'accepted',
                "phone" => "+".$orderNew->getPlacePoint()->getPhone(),
                "message" => ''
            )
        );

        $this->client->request(
            'GET',
            '/api/v1/orders/'.$orderNew->getId().'/status'
        );

        $this->assertEquals('Food\ApiBundle\Controller\OrdersController::getOrderStatusAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $orderData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($expectedNewOrderData, $orderData);
    }

    public function testOrderStatusActionFinished()
    {
        $place = $this->getPlace('Test place 2');
        $placePoint = $this->getPlacePoint($place);
        $orderFinished = $this->getOrder($place, $placePoint, OrderService::$status_finished);

        $expectedFinishedOrderData = array(
            "order_id" => $orderFinished->getId(),
            "status" => array(
                "state" => 'prepared',
                "phone" => "+".$orderFinished->getPlacePoint()->getPhone(),
                "message" => ''
            )
        );

        // Test Finished
        $this->client->request(
            'GET',
            '/api/v1/orders/'.$orderFinished->getId().'/status'
        );

        $this->assertEquals('Food\ApiBundle\Controller\OrdersController::getOrderStatusAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $finishedOrderData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($expectedFinishedOrderData, $finishedOrderData);
    }

    public function testOrderStatusActionDelayed()
    {
        $om = $this->getDoctrine()->getManager();

        $place = $this->getPlace('Test place 3');
        $placePoint = $this->getPlacePoint($place);
        $orderDelayed = $this->getOrder($place, $placePoint, OrderService::$status_delayed);
        $orderDelayed->setDelayDuration(30)->setDelayed(true);

        $om->persist($orderDelayed);
        $om->flush();

        $expectedDelayedOrderData = array(
            "order_id" => $orderDelayed->getId(),
            "status" => array(
                "state" => $orderDelayed->getOrderStatus(),
                "phone" => "+".$orderDelayed->getPlacePoint()->getPhone(),
                "message" => $this->getContainer()->get('translator')->trans(
                        'mobile.order_status.order_delayed',
                        array('%delayTime%' => $orderDelayed->getDelayDuration())
                    )
            )
        );

        $this->client->request(
            'GET',
            '/api/v1/orders/'.$orderDelayed->getId().'/status'
        );

        $this->assertEquals('Food\ApiBundle\Controller\OrdersController::getOrderStatusAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $delayedOrderData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($expectedDelayedOrderData, $delayedOrderData);
    }

    public function testOrderDetailsActionNoOrder()
    {
        $this->client->request(
            'GET',
            '/api/v1/orders/111254'
        );

        $this->assertEquals('Food\ApiBundle\Controller\OrdersController::getOrderDetailsAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(404 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'Order not found') !== false));
    }

    public function testGetOrderDetailsActionPickup()
    {
        $om = $this->getDoctrine()->getManager();
        $place = $this->getPlace('Test place 4');
        $placePoint = $this->getPlacePoint($place);
        $order = $this->getOrder($place, $placePoint, OrderService::$status_new);

        $user = $this->getUser();
        $order->setUser($user)
            ->setAddressId($this->getAddress($user))
            ->setDeliveryType(OrderService::$deliveryPickup);
        $om->persist($order);
        $om->flush();

        $expectedDelayedOrderData = array(
            'order_id' => $order->getId(),
            'total_price' => array(
                'amount' => 10000,
                'currency' => 'LTL'
            ),
            'state' => array(
                'title' => OrderService::$status_new,
                'info_number' => '37061234567'
            ),
            'details' => array(
                'restaurant_id' => $order->getPlace()->getId(),
                'restaurant_title' => 'Test place 4',
                'payment_options' => array(
                    'cash' => true,
                    'credit_card' => false
                ),
                'items' => array()
            ),
            'service' => array(
                "type" => 'pickup',
                "address" => array(
                    "street" => 'Test address',
                    "house_number" => '123',
                    "flat_number" => '',
                    "city" => 'Vilnius',
                    "comments" => ''
                ),
            )
        );

        $this->client->request(
            'GET',
            '/api/v1/orders/'.$order->getId()
        );

        $this->assertEquals('Food\ApiBundle\Controller\OrdersController::getOrderDetailsAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $delayedOrderData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($expectedDelayedOrderData, $delayedOrderData);
    }

    public function testGetOrderDetailsActionDeliver()
    {
        $om = $this->getDoctrine()->getManager();
        $place = $this->getPlace('Test place 5');
        $placePoint = $this->getPlacePoint($place);
        $order = $this->getOrder($place, $placePoint, OrderService::$status_new);

        $user = $this->getUser();
        $order->setUser($user)
            ->setAddressId($this->getAddress($user))
            ->setDeliveryType(OrderService::$deliveryDeliver);
        $om->persist($order);
        $om->flush();

        $expectedDelayedOrderData = array(
            'order_id' => $order->getId(),
            'total_price' => array(
                'amount' => 10000,
                'currency' => 'LTL'
            ),
            'state' => array(
                'title' => OrderService::$status_new,
                'info_number' => '37061234567'
            ),
            'details' => array(
                'restaurant_id' => $order->getPlace()->getId(),
                'restaurant_title' => 'Test place 5',
                'payment_options' => array(
                    'cash' => true,
                    'credit_card' => false
                ),
                'items' => array()
            ),
            'service' => array(
                "type" => 'delivery',
                "address" => array(
                    "street" => 'Galvydzio',
                    "house_number" => '5',
                    "flat_number" => '',
                    "city" => 'Vilnius',
                    "comments" => ''
                ),
                'price' => array(
                    'amount' => 500,
                    'currency' => 'LTL',
                ),
            )
        );

        $this->client->request(
            'GET',
            '/api/v1/orders/'.$order->getId()
        );

        $this->assertEquals('Food\ApiBundle\Controller\OrdersController::getOrderDetailsAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $delayedOrderData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($expectedDelayedOrderData, $delayedOrderData);
    }

    private function getPlace($placeName)
    {
        $om = $this->getDoctrine()->getManager();

        $place = new Place();
        $place->setActive(true)
            ->setCreatedAt(new \DateTime("now"))
            ->setName($placeName)
            ->setDeliveryPrice(5)
            ->setCartMinimum(5)
            ->setDeliveryTime('1 val.')
            ->setSelfDelivery(false)
            ->setCardOnDelivery(false)
            ->setNew(false)
            ->setRecommended(true);

        $om->persist($place);
        $om->flush();

        return $place;
    }

    private function getPlacePoint($place)
    {
        $om = $this->getDoctrine()->getManager();

        $placePoint = new PlacePoint();
        $placePoint->setPlace($place)
            ->setCreatedAt(new \DateTime("now"))
            ->setCompanyCode('12345')
            ->setLat('123')
            ->setLon('345')
            ->setDeliveryTime('1 val.')
            ->setPhone('37061212122')
            ->setCity('Vilnius')
            ->setAddress('Test address 123')
            ->setActive(true)
            ->setPublic(true)
            ->setWd1Start('9:00')
            ->setWd1End('22:00')
            ->setWd2Start('9:00')
            ->setWd2End('22:00')
            ->setWd3Start('9:00')
            ->setWd3End('22:00')
            ->setWd4Start('9:00')
            ->setWd4End('22:00')
            ->setWd5Start('9:00')
            ->setWd5End('22:00')
            ->setWd6Start('9:00')
            ->setWd6End('22:00')
            ->setWd7Start('9:00')
            ->setWd7End('22:00');

        $om->persist($placePoint);
        $om->flush();

        return $placePoint;
    }

    private function getOrder($place, $placePoint, $status)
    {
        $om = $this->getDoctrine()->getManager();

        $order = new Order();
        $order->setOrderDate(new \DateTime("now"))
            ->setPlace($place)
            ->setPlacePoint($placePoint)
            ->setPlacePointCity($placePoint->getCity())
            ->setPlacePointAddress($placePoint->getAddress())
            ->setOrderStatus($status)
            ->setVat('21')
            ->setTotal('100')
            ->setOrderHash('sadasfdafsf')
            ->setPaymentStatus('complete')
            ->setLocale('lt');

        $om->persist($order);
        $om->flush();

        return $order;
    }

    /**
     * @return User
     */
    private function getUser()
    {
        $um = $this->getContainer()->get('fos_user.user_manager');

        $user = $um->findUserByEmail('order_api_buyer@foodout.lt');

        if (!$user) {
            $user = $um->createUser();
            $user->setEmail('order_api_buyer@foodout.lt');
            $user->setPlainPassword('123488');
            $user->setFirstname('Api buyer');
            $user->setPhone('37061234567');

            $user->setRoles(array('ROLE_USER'));
            $user->setFullyRegistered(1);
            $user->setEnabled(true);

            $um->updateUser($user);
        }

        return $user;
    }

    /**
     * @param User $user
     * @return UserAddress
     */
    private function getAddress($user)
    {
        $om = $this->getDoctrine()->getManager();

        $address = $user->getDefaultAddress();

        if (!$address || empty($address)) {
            $address = new UserAddress();
            $address->setAddress('Galvydzio 5')
                ->setCity('Vilnius')
                ->setLat('54.15424')
                ->setLon('24.1242')
                ->setUser($user)
                ->setDefault(1);
            $om->persist($address);
            $om->flush();
        }

        return $address;
    }
}
