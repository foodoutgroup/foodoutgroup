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
        $order3 = $this->getOrder($place, $placePoint, OrderService::$status_accepted);

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
}
