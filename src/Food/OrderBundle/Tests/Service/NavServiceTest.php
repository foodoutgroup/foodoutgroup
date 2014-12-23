<?php
namespace Food\OrderBundle\Tests\Service;

use Food\OrderBundle\Service\NavService;
use Food\AppBundle\Test\WebTestCase;

class NavServiceTest extends WebTestCase {

    public function testNoTestTablesShouldBeLeft()
    {
        $expectedDeliveryOrderTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Delivery Order]';
        $expectedPosTransactionTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$POS Trans_ Line]';
        $expectedHeaderTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Web ORDER Header]';
        $expectedLineTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Web ORDER Lines]';
        $expectedOrderTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$FoodOut Order]';
        $expectedMessagesTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Web Order Messages]';
        $expectedItemsTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Item]';
        $expectedDeliveryOrderStatus = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Delivery order status]';

        $navService = new NavService();

        $gotDeliveryOrderTable = $navService->getDeliveryOrderTable();
        $gotPosTransactionTable = $navService->getPosTransactionLinesTable();
        $gotHeaderTable = $navService->getHeaderTable();
        $gotLineTable = $navService->getLineTable();
        $gotOrderTable = $navService->getOrderTable();
        $gotMessagesTable = $navService->getMessagesTable();
        $gotItemsTable = $navService->getItemsTable();
        $gotDeliveryOrderStatus = $navService->getDeliveryOrderStatusTable();

        $this->assertEquals($expectedDeliveryOrderTable, $gotDeliveryOrderTable);
        $this->assertEquals($expectedPosTransactionTable, $gotPosTransactionTable);
        $this->assertEquals($expectedHeaderTable, $gotHeaderTable);
        $this->assertEquals($expectedLineTable, $gotLineTable);
        $this->assertEquals($expectedOrderTable, $gotOrderTable);
        $this->assertEquals($expectedMessagesTable, $gotMessagesTable);
        $this->assertEquals($expectedItemsTable, $gotItemsTable);
        $this->assertEquals($expectedDeliveryOrderStatus, $gotDeliveryOrderStatus);
    }

    public function testGetNavOrderId()
    {
        $navService = new NavService();

        $orderId = 1457;
        $expectedNavOrderId = $navService->getNavIdModifier()+$orderId;

        $order = $this->getMockBuilder('\Food\OrderBundle\Entity\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $order->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($orderId));

        $this->assertEquals($expectedNavOrderId, $navService->getNavOrderId($order));
    }

    public function testOrderIdFromNavId()
    {
        $navService = new NavService();

        $navOrderId = $navService->getNavIdModifier()+1622;
        $expectedOrderId = $navOrderId-$navService->getNavIdModifier();

        $this->assertEquals($expectedOrderId, $navService->getOrderIdFromNavId($navOrderId));
    }

    // TODO aptestuoti NAV'a
    // TODO butina aptestuoti statusu keitima, bet tam reikia padaryti, kad test aplinkoje nebutu siunciami mailer laiskai
}