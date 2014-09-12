<?php

namespace Food\MonitoringBundle\Tests\Service;

use Food\OrderBundle\Service\OrderService;
use Food\AppBundle\Test\WebTestCase;

class MonitoringServiceTest extends WebTestCase
{
    public function testGetUnacceptedOrders()
    {
        $em = $this->getDoctrine()->getManager();
        $monitoringService = $this->getContainer()->get('food.monitoring');

        $place = $this->getPlace('unacceptedOrderTest');
        $placePoint = $this->getPlacePoint($place);
        $order1 = $this->getOrder($place, $placePoint, OrderService::$status_new);
        $order2 = $this->getOrder($place, $placePoint, OrderService::$status_new);
        $order3 = $this->getOrder($place, $placePoint, OrderService::$status_assiged);

        $order1->setOrderDate(new \DateTime("-30 minute"));
        $order2->setOrderDate(new \DateTime("-5 minute"));
        $order3->setOrderDate(new \DateTime("-45 minute"));

        $em->persist($order1);
        $em->persist($order2);
        $em->persist($order3);
        $em->flush();

        $unacceptedOrders = $monitoringService->getUnacceptedOrders();

        $this->assertTrue(
            count($unacceptedOrders) == 1
        );

        $this->assertEquals($order1->getId(), $unacceptedOrders[0]->getId());
    }

    public function testGetUnassignedOrders()
    {
        $em = $this->getDoctrine()->getManager();
        $monitoringService = $this->getContainer()->get('food.monitoring');

        $place = $this->getPlace('unassignedOrderTest');
        $placePoint = $this->getPlacePoint($place);
        $order1 = $this->getOrder($place, $placePoint, OrderService::$status_assiged);
        $order2 = $this->getOrder($place, $placePoint, OrderService::$status_accepted);
        $order3 = $this->getOrder($place, $placePoint, OrderService::$status_delayed);

        $order1->setOrderDate(new \DateTime("now"))
            ->setDeliveryTime(new \DateTime("+45 minute"));
        $order2->setOrderDate(new \DateTime("now"))
            ->setDeliveryTime(new \DateTime("+15 minute"));
        $order3->setOrderDate(new \DateTime("now"))
            ->setDeliveryTime(new \DateTime("+28 minute"));

        $em->persist($order1);
        $em->persist($order2);
        $em->persist($order3);
        $em->flush();

        echo "Order 1 id: ".$order1->getId()."\n";
        echo "Order 2 id: ".$order2->getId()."\n";
        echo "Order 3 id: ".$order3->getId()."\n";

        $unacceptedOrders = $monitoringService->getUnassigneddOrders();

        $this->assertTrue(
            count($unacceptedOrders) == 1
        );

        $this->assertEquals($order2->getId(), $unacceptedOrders[0]->getId());
    }
}
