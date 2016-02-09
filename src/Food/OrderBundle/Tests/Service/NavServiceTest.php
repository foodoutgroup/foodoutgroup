<?php
namespace Food\OrderBundle\Tests\Service;

use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Service\NavService;
use Food\AppBundle\Test\WebTestCase;

class NavServiceTest extends WebTestCase {

    public function testNoRealTablesShouldBeTested()
    {
        $expectedDeliveryOrderTable = '[skamb_centras].[dbo].[PROTOTIPAS Skambuciu Centras$Delivery Order]';
        $expectedPosTransactionTable = '[skamb_centras].[dbo].[PROTOTIPAS Skambuciu Centras$POS Trans_ Line]';
        $expectedHeaderTable = '[skamb_centras].[dbo].[PROTOTIPAS Skambuciu Centras$Web ORDER Header]';
        $expectedLineTable = '[skamb_centras].[dbo].[PROTOTIPAS Skambuciu Centras$Web ORDER Lines]';
        $expectedOrderTable = '[skamb_centras].[dbo].[PROTOTIPAS Skambuciu Centras$FoodOut Order]';
        $expectedMessagesTable = '[skamb_centras].[dbo].[PROTOTIPAS Skambuciu Centras$Web Order Messages]';
        $expectedItemsTable = '[skamb_centras].[dbo].[PROTOTIPAS Skambuciu Centras$Item]';
        $expectedDeliveryOrderStatus = '[skamb_centras].[dbo].[PROTOTIPAS Skambuciu Centras$Delivery order status]';
        $expectedPostedDeliveryOrders = '[skamb_centras].[dbo].[PROTOTIPAS Skambuciu Centras$Posted Delivery Orders]';

        $navService = $this->getContainer()->get('food.nav');

        $gotDeliveryOrderTable = $navService->getDeliveryOrderTable();
        $gotPosTransactionTable = $navService->getPosTransactionLinesTable();
        $gotHeaderTable = $navService->getHeaderTable();
        $gotLineTable = $navService->getLineTable();
        $gotOrderTable = $navService->getOrderTable();
        $gotMessagesTable = $navService->getMessagesTable();
        $gotItemsTable = $navService->getItemsTable();
        $gotDeliveryOrderStatus = $navService->getDeliveryOrderStatusTable();
        $gotPostedDeliveryOrders = $navService->getPostedDeliveryOrdersTable();

        $this->assertEquals($expectedDeliveryOrderTable, $gotDeliveryOrderTable);
        $this->assertEquals($expectedPosTransactionTable, $gotPosTransactionTable);
        $this->assertEquals($expectedHeaderTable, $gotHeaderTable);
        $this->assertEquals($expectedLineTable, $gotLineTable);
        $this->assertEquals($expectedOrderTable, $gotOrderTable);
        $this->assertEquals($expectedMessagesTable, $gotMessagesTable);
        $this->assertEquals($expectedItemsTable, $gotItemsTable);
        $this->assertEquals($expectedDeliveryOrderStatus, $gotDeliveryOrderStatus);
        $this->assertEquals($expectedPostedDeliveryOrders, $gotPostedDeliveryOrders);
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

    public function testGetLocalPlacePoint()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $place1 = new Place();
        $place1->setActive(true)
            ->setName('getLocalPlacePointTest')
            ->setCartMinimum(5)
            ->setCreatedAt(new \DateTime("now"))
            ->setDeliveryPrice(5)
            ->setDescription('a')
            ->setPickupTime('30')
            ->setDeliveryTime('1 val.')
            ->setChain('chain1')
            ->setNew(false)
            ->setRecommended(true)
            ->setSelfDelivery(false)
            ->setCardOnDelivery(false);

        $em->persist($place1);
        $em->flush();

        $pp1 = new PlacePoint();
        $pp1->setActive(true)
            ->setAddress('omg1')
            ->setCreatedAt(new \DateTime("now"))
            ->setCity('Vilnius')
            ->setLat('70')
            ->setLon('71')
            ->setCoords('70 71')
            ->setInternalCode('code_red')
            ->setDeliveryTime('5')
            ->setPlace($place1)
            ->setPhone('37061514333')
            ->setCompanyCode('161514')
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
            ->setWd7End('22:00')
        ;

        $em->persist($pp1);
        $em->flush();

        $pp2 = new PlacePoint();
        $pp2->setActive(true)
            ->setAddress('omg1')
            ->setCreatedAt(new \DateTime("now"))
            ->setCity('Vilnius')
            ->setLat('70')
            ->setLon('71')
            ->setCoords('70 71')
            ->setInternalCode('code_red')
            ->setDeliveryTime('5')
            ->setPlace($place1)
            ->setParentId($pp1->getId())
            ->setPhone('37061514333')
            ->setCompanyCode('161514')
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

        $em->persist($pp1);
        $em->flush();

        $ppGot = $this->getContainer()->get('food.nav')->getLocalPlacePoint('chain1', 'code_red');

        $this->assertEquals($pp1->getId(), $ppGot->getId());
    }

    // TODO aptestuoti NAV'a
    // TODO butina aptestuoti statusu keitima, bet tam reikia padaryti, kad test aplinkoje nebutu siunciami mailer laiskai
}