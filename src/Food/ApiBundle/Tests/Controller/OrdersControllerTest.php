<?php

namespace Food\ApiBundle\Tests\Controller;

use Food\AppBundle\Test\WebTestCase;
use Food\OrderBundle\Service\OrderService;

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
                "title" => 'accepted',
                // TODO Rodome nebe restorano, o dispeceriu nr
                "info_number" => "+".$this->getContainer()->getParameter('dispatcher_contact_phone'),
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
                "title" => 'prepared',
                "info_number" => "+".$this->getContainer()->getParameter('dispatcher_contact_phone'),
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
                "title" => $orderDelayed->getOrderStatus(),
                "info_number" => "+".$this->getContainer()->getParameter('dispatcher_contact_phone'),
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
                'currency' => 'EUR'
            ),
            'state' => array(
                'title' => 'accepted',
                'info_number' => '+37061004970',
                'message' => ''
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
                'currency' => 'EUR'
            ),
            'state' => array(
                'title' => 'accepted',
                'info_number' => '+37061004970',
                'message' => ''
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
                    'currency' => 'EUR',
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
}
