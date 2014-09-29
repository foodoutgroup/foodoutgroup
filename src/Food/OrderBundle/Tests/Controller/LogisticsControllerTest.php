<?php

namespace Food\OrderBundle\Tests\Controller;

use Food\AppBundle\Entity\Driver;
use Food\AppBundle\Test\WebTestCase;
use Food\OrderBundle\Service\OrderService;

class LogisticsControllerTest extends WebTestCase
{
    public function testLogisticsActionsErrorForNoXml()
    {
        $this->client->request(
            'POST',
            '/logistics/status-update/'
        );

        $this->assertEquals('Food\OrderBundle\Controller\LogisticsController::orderStatusAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(500 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'No xml given') !== false));


        $this->client->request(
            'POST',
            '/logistics/driver-assign/'
        );

        $this->assertEquals('Food\OrderBundle\Controller\LogisticsController::driverAssignAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(500 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'No xml given') !== false));
    }

    public function testLogisticsAssignDriverXml()
    {
        // Prepare order and driver
        $em = $this->getDoctrine()->getManager();
        $place = $this->getPlace('Logistics test1');
        $placePoint = $this->getPlacePoint($place);
        $order = $this->getOrder($place, $placePoint, OrderService::$status_new);
        $order2 = $this->getOrder($place, $placePoint, OrderService::$status_assiged);
        $order3 = $this->getOrder($place, $placePoint, OrderService::$status_assiged);
        $driver = $this->getDriver('Vairuotojas1');
        $driver2 = $this->getDriver('Vairuotojas2');

        $order2->setDriver($driver);
        $order3->setDriver($driver2);
        $em->persist($order2);
        $em->persist($order3);
        $em->flush();

        $reloadedOrder2 = $this->getContainer()->get('food.order')->getOrderById($order2->getId());

        // Be sure first driver is assigned
        $this->assertEquals($driver->getId(), $reloadedOrder2->getDriver()->getId());

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<RoutesAssigned>
<RouteAssigned>
<Order_id>'.$order->getId().'</Order_id>
<Driver_id>'.$driver->getId().'</Driver_id>
<Vehicle_no>FCU 819</Vehicle_no>
<Planned_delivery_time>2014-07-02 11:43</Planned_delivery_time>
</RouteAssigned>
<RouteAssigned>
<Order_id>'.$order2->getId().'</Order_id>
<Driver_id>'.$driver2->getId().'</Driver_id>
<Vehicle_no>FCU 152</Vehicle_no>
<Planned_delivery_time>2014-07-02 11:44</Planned_delivery_time>
</RouteAssigned>
<RouteAssigned>
<Order_id>'.$order3->getId().'</Order_id>
<Driver_id>'.$driver2->getId().'</Driver_id>
<Vehicle_no>FCU 156</Vehicle_no>
<Planned_delivery_time>2014-07-02 11:46</Planned_delivery_time>
</RouteAssigned>
</RoutesAssigned>';

        $this->client->request(
            'POST',
            '/logistics/driver-assign/',
            array(),
            array(),
            array(),
            $xml
        );

        $this->assertEquals('Food\OrderBundle\Controller\LogisticsController::driverAssignAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $reloadedOrder = $this->getContainer()->get('food.order')->getOrderById($order->getId());
        $reloadedOrder2 = $this->getContainer()->get('food.order')->getOrderById($order2->getId());
        $reloadedOrder3 = $this->getContainer()->get('food.order')->getOrderById($order3->getId());

        $this->assertEquals(OrderService::$status_assiged, $reloadedOrder->getOrderStatus());
        $this->assertEquals($driver->getId(), $reloadedOrder->getDriver()->getId());

        $this->assertEquals(OrderService::$status_assiged, $reloadedOrder2->getOrderStatus());
        $this->assertEquals($driver2->getId(), $reloadedOrder2->getDriver()->getId());

        $this->assertEquals(OrderService::$status_assiged, $reloadedOrder3->getOrderStatus());
        $this->assertEquals($driver2->getId(), $reloadedOrder3->getDriver()->getId());
    }

    public function testLogisticsOrderStatusXml()
    {
        $this->markTestSkipped('Reikia pasidaryti optionsa, kad nesiustu mailerio laisku darant testus');
        // Prepare order and driver
        $place = $this->getPlace('Logistics test2');
        $placePoint = $this->getPlacePoint($place);
        $order = $this->getOrder($place, $placePoint, OrderService::$status_new);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<ShipmentStatuses>
    <ShipmentStatus>
        <Order_id>'.$order->getId().'</Order_id>
        <Event_Date>2014-07-02 11:43</Event_Date>
        <Status>finished</Status>
        <FailReason/>
    </ShipmentStatus>
</ShipmentStatuses>';

        $this->client->request(
            'POST',
            '/logistics/status-update/',
            array(),
            array(),
            array(),
            $xml
        );

        $this->assertEquals('Food\OrderBundle\Controller\LogisticsController::orderStatusAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $reloadedOrder = $this->getContainer()->get('food.order')->getOrderById($order->getId());

        $this->assertEquals(OrderService::$status_completed, $reloadedOrder->getOrderStatus());
    }

    public function testLogisticsOrderStatusXmlFailedStatus()
    {
        // Prepare order and driver
        $place = $this->getPlace('Logistics test3');
        $placePoint = $this->getPlacePoint($place);
        $order = $this->getOrder($place, $placePoint, OrderService::$status_new);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<ShipmentStatuses>
    <ShipmentStatus>
        <Order_id>'.$order->getId().'</Order_id>
        <Event_Date>2014-07-02 11:43</Event_Date>
        <Status>failed</Status>
        <FailReason>Omg</FailReason>
    </ShipmentStatus>
</ShipmentStatuses>';

        $this->client->request(
            'POST',
            '/logistics/status-update/',
            array(),
            array(),
            array(),
            $xml
        );

        $this->assertEquals('Food\OrderBundle\Controller\LogisticsController::orderStatusAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $reloadedOrder = $this->getContainer()->get('food.order')->getOrderById($order->getId());

        $this->assertEquals(OrderService::$status_failed, $reloadedOrder->getOrderStatus());
    }

    public function testLogisticsOrderStatusXmlMultipleOrders()
    {
        // Prepare order and driver
        $place = $this->getPlace('Logistics test3');
        $placePoint = $this->getPlacePoint($place);
        $order = $this->getOrder($place, $placePoint, OrderService::$status_new);
        $order2 = $this->getOrder($place, $placePoint, OrderService::$status_accepted);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<ShipmentStatuses>
    <ShipmentStatus>
        <Order_id>'.$order->getId().'</Order_id>
        <Event_Date>2014-07-02 11:43</Event_Date>
        <Status>failed</Status>
        <FailReason>Omg</FailReason>
    </ShipmentStatus>
    <ShipmentStatus>
        <Order_id>123447</Order_id>
        <Event_Date>2014-07-02 11:43</Event_Date>
        <Status>failed</Status>
        <FailReason>Omg2</FailReason>
    </ShipmentStatus>
    <ShipmentStatus>
        <Order_id>'.$order2->getId().'</Order_id>
        <Event_Date>2014-07-02 11:43</Event_Date>
        <Status>failed</Status>
        <FailReason>Omg3</FailReason>
    </ShipmentStatus>
</ShipmentStatuses>';

        $this->client->request(
            'POST',
            '/logistics/status-update/',
            array(),
            array(),
            array(),
            $xml
        );

        $this->assertEquals('Food\OrderBundle\Controller\LogisticsController::orderStatusAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $reloadedOrder = $this->getContainer()->get('food.order')->getOrderById($order->getId());
        $reloadedOrder2 = $this->getContainer()->get('food.order')->getOrderById($order2->getId());

        $this->assertEquals(OrderService::$status_failed, $reloadedOrder->getOrderStatus());
        $this->assertEquals(OrderService::$status_failed, $reloadedOrder2->getOrderStatus());
    }

    /**
     * @param string $name
     * @return Driver
     */
    private function getDriver($name)
    {
        $om = $this->getDoctrine()->getManager();

        $driver = new Driver();
        $driver->setName($name)
            ->setCity('Vilnius')
            ->setPhone('3706111111')
            ->setType('local')
            ->setActive(true)
            ->setCreatedAt(new \DateTime("now"));

        $om->persist($driver);
        $om->flush();

        return $driver;
    }
}
