<?php

namespace Food\MonitoringBundle\Tests\Service;

use Food\OrderBundle\Entity\OrderToLogistics;
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
        $order4 = $this->getOrder($place, $placePoint, OrderService::$status_assiged);
        $order5 = $this->getOrder($place, $placePoint, OrderService::$status_assiged);

        $order1->setOrderDate(new \DateTime("now"))
            ->setDeliveryTime(new \DateTime("+45 minute"))
            ->setPlacePointSelfDelivery(false)
            ->setDeliveryType(OrderService::$deliveryDeliver);
        $order2->setOrderDate(new \DateTime("now"))
            ->setDeliveryTime(new \DateTime("+15 minute"))
            ->setPlacePointSelfDelivery(false)
            ->setDeliveryType(OrderService::$deliveryDeliver);
        $order3->setOrderDate(new \DateTime("now"))
            ->setDeliveryTime(new \DateTime("+28 minute"))
            ->setPlacePointSelfDelivery(false)
            ->setDeliveryType(OrderService::$deliveryDeliver);
        $order4->setOrderDate(new \DateTime("now"))
            ->setDeliveryTime(new \DateTime("+45 minute"))
            ->setPlacePointSelfDelivery(true)
            ->setDeliveryType(OrderService::$deliveryDeliver);
        $order5->setOrderDate(new \DateTime("now"))
            ->setDeliveryTime(new \DateTime("+45 minute"))
            ->setPlacePointSelfDelivery(false)
            ->setDeliveryType(OrderService::$deliveryPickup);

        $em->persist($order1);
        $em->persist($order2);
        $em->persist($order3);
        $em->persist($order4);
        $em->persist($order5);
        $em->flush();

        $unacceptedOrders = $monitoringService->getUnassignedOrders();

        $this->assertTrue(
            count($unacceptedOrders) == 1
        );

        $this->assertEquals($order2->getId(), $unacceptedOrders[0]->getId());
    }

    public function testGetLogisticsProblemsNone()
    {
        // Clear table
        $stmt = $this->getDoctrine()->getManager()->getConnection()
            ->prepare('DELETE FROM orders_to_logistics');
        $stmt->execute();

        $monitoringService = $this->getContainer()->get('food.monitoring');

        $expectedResult = array(
            'unsent' => 0,
            'error' =>
                array(
                    'count' => 0,
                    'lastError' => '',
                )
        );

        $result = $monitoringService->getLogisticsSyncProblems();

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetLogisticsProblems()
    {
        $em = $this->getDoctrine()->getManager();
        $monitoringService = $this->getContainer()->get('food.monitoring');

        $expectedResult = array(
            'unsent' => 2,
            'error' =>
             array(
                 'count' => 1,
                 'lastError' => 'Some error',
             )
        );

        $place = $this->getPlace('logisticsSyncProblemsTest');
        $placePoint = $this->getPlacePoint($place);
        $order = $this->getOrder($place, $placePoint, OrderService::$status_accepted);

        $orderToLogistics1 = new OrderToLogistics();
        $orderToLogistics2 = new OrderToLogistics();
        $orderToLogistics3 = new OrderToLogistics();
        $orderToLogistics4 = new OrderToLogistics();
        $orderToLogistics5 = new OrderToLogistics();

        $orderToLogistics1->setOrder($order)
            ->setDateAdded(new \DateTime("-5 minute"))
            ->setDateSent(new \DateTime("-2 mintue"))
            ->setStatus('sent');

        $orderToLogistics2->setOrder($order)
            ->setDateAdded(new \DateTime("-7 minute"))
            ->setStatus('unsent');

        $orderToLogistics3->setOrder($order)
            ->setDateAdded(new \DateTime("-5 minute"))
            ->setStatus('unsent');

        $orderToLogistics4->setOrder($order)
            ->setDateAdded(new \DateTime("-5 minute"))
            ->setStatus('error')
            ->setLastError('Some error');

        $orderToLogistics5->setOrder($order)
            ->setDateAdded(new \DateTime("now"))
            ->setStatus('unsent');

        $em->persist($orderToLogistics1);
        $em->persist($orderToLogistics2);
        $em->persist($orderToLogistics3);
        $em->persist($orderToLogistics4);
        $em->persist($orderToLogistics5);
        $em->flush();

        $result = $monitoringService->getLogisticsSyncProblems();

        $this->assertEquals($expectedResult, $result);
    }
}
