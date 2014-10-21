<?php
namespace Food\OrderBundle\Tests\Service;

use Food\OrderBundle\Service\NavService;
use Food\AppBundle\Test\WebTestCase;

class NavServiceTest extends WebTestCase {

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