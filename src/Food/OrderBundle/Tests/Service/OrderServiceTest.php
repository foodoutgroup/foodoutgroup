<?php
namespace Food\OrderBundle\Tests\Service;

use Food\CartBundle\Service\CartService;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\LocalBiller;
use Food\OrderBundle\Service\OrderService;
use Food\OrderBundle\Service\PaySera;
use Food\UserBundle\Entity\User;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

class OrderServiceTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var \Symfony\Component\HttpKernel\AppKernel
     */
    protected $kernel;

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;

    /**
     * @return null
     */
    public function setUp()
    {
        $this->kernel = new \AppKernel('test', true);
        $this->kernel->boot();

        $this->container = $this->kernel->getContainer();

        parent::setUp();
    }

    /**
     * @return null
     */
    public function tearDown()
    {
        $this->kernel->shutdown();

        parent::tearDown();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetPaymentSystemByMethodException()
    {
        $orderService = new OrderService();

        $orderService->getPaymentSystemByMethod('omg.service');
    }

    /**
     * @depends testGetPaymentSystemByMethodException
     */
    public function testGetPaymentSystemByMethod()
    {
        $orderService = new OrderService();
        $orderService->setContainer($this->container);

        $biller = $orderService->getPaymentSystemByMethod('paysera');
        $biller2 = $orderService->getPaymentSystemByMethod('local');
        $biller3 = $orderService->getPaymentSystemByMethod('local.card');

        $this->assertInstanceOf('\Food\OrderBundle\Service\Paysera', $biller);
        $this->assertInstanceOf('\Food\OrderBundle\Service\LocalBiller', $biller2);
        $this->assertInstanceOf('\Food\OrderBundle\Service\LocalBiller', $biller3);
    }

    public function testIsAvailablePaymentMethod()
    {
        $orderService = new OrderService();
        $orderService->setContainer($this->container);

        $isAvailable1 = $orderService->isAvailablePaymentMethod('paysera');
        $isAvailable2 = $orderService->isAvailablePaymentMethod('local');
        $isAvailable3 = $orderService->isAvailablePaymentMethod('local.card');
        $isAvailable4 = $orderService->isAvailablePaymentMethod('card.local');

        $this->assertTrue($isAvailable1);
        $this->assertTrue($isAvailable2);
        $this->assertTrue($isAvailable3);
        $this->assertFalse($isAvailable4);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetOrderEmptyException()
    {
        $orderService = new OrderService();
        $orderService->setOrder(null);
    }

    /**
     * @depends testSetOrderEmptyException
     * @expectedException \InvalidArgumentException
     */
    public function testSetOrderNotOrderException()
    {
        $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();

        $orderService = new OrderService();
        $orderService->setOrder($container);
    }

    /**
     * @depends testSetOrderEmptyException
     * @depends testSetOrderNotOrderException
     */
    public function testSetPaymentMethod()
    {
        $this->markTestSkipped(
            'Pridetas loggeris - sutvarkyti testus'
        );
        $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();

        $order = new Order();
        $goodOrder = new Order();
        $goodOrder->setPaymentMethod('local.card');

        $orderService = new OrderService();
        $orderService->setContainer($container);

        $container->expects($this->once())
            ->method('getParameter')
            ->with('payment.methods')
            ->will($this->returnValue(array('local', 'local.card', 'paysera')));

        $orderService->setOrder($order);
        $orderService->setPaymentMethod('local.card');

        $this->assertEquals($goodOrder, $orderService->getOrder());
    }

    public function testSettersGetters()
    {
        $orderService = new OrderService();
        $payseraBiller = new PaySera();
        $localBiller = new LocalBiller();

        $testSetter = 'abc';
        $testSetter2 = 'cab';

        $orderService->setPayseraBiller($testSetter);
        $payseraTestBiller = $orderService->getPayseraBiller();

        $this->assertEquals($testSetter, $payseraTestBiller);

        $orderService->setLocalBiller($testSetter2);
        $localBillerTest = $orderService->getLocalBiller();

        $this->assertEquals($testSetter2, $localBillerTest);

        // Start with new one
        $orderService = new OrderService();

        $payseraTestBiller = $orderService->getPayseraBiller();
        $this->assertEquals($payseraBiller, $payseraTestBiller);

        $localBillerTest = $orderService->getLocalBiller();
        $this->assertEquals($localBiller, $localBillerTest);

        $cartService = new CartService();
        $orderService->setCartService($cartService);
        $gotCartService = $orderService->getCartService();
        $this->assertEquals($cartService, $gotCartService);

        $user = new User();
        $user->setEmail('testovicius');
        $orderService->setUser($user);
        $gotUser = $orderService->getUser();
        $this->assertEquals($user, $gotUser);

        $locale = 'en';
        $orderService->setLocale($locale);
        $gotLocale = $orderService->getLocale();
        $this->assertEquals($locale, $gotLocale);
    }

    /**
     * @depends testGetPaymentSystemByMethod
     */
    public function testGetBillingInterface()
    {
        $localBiller = new LocalBiller();
        $payseraBiller = new PaySera();

        $orderService = new OrderService();
        $orderService->setLocalBiller($localBiller);
        $orderService->setPayseraBiller($payseraBiller);

        $testBiller1 = $orderService->getBillingInterface();
        $testBiller2 = $orderService->getBillingInterface('paysera');
        $testBiller3 = $orderService->getBillingInterface('local');

        $this->assertEquals($localBiller, $testBiller1);
        $this->assertEquals($payseraBiller, $testBiller2);
        $this->assertEquals($localBiller, $testBiller3);
    }

    /**
     * TODO kai bus getOrderById is db - mockinti DB ir returninti dumb orderi
     * @depends testGetPaymentSystemByMethod
     */
    public function testBillOrderLocal()
    {
        $this->markTestIncomplete();
        $localBiller = $this->getMock(
            '\Food\OrderBundle\Service\LocalBiller',
            array('setOrder', 'bill')
        );
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );
        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMockBuilder('Food\OrderBundle\Entity\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $orderRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $orderService = new OrderService();
        $orderService->setLocalBiller($localBiller);
        $orderService->setContainer($container);

        $container->expects($this->once())
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($orderRepository));

        $orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->will($this->returnValue($order));

        $localBiller->expects($this->once())
            ->method('setOrder');

        $localBiller->expects($this->once())
            ->method('bill');

        $orderService->billOrder(1, 'local');
    }

    /**
     * TODO kai bus getOrderById is db - mockinti DB ir returninti dumb orderi
     * @depends testGetPaymentSystemByMethod
     */
    public function testBillOrderPaysera()
    {
        $this->markTestIncomplete();
        $payseraBiller = $this->getMock(
            '\Food\OrderBundle\Service\PaySera',
            array('setOrder', 'bill')
        );
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );
        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMockBuilder('Food\OrderBundle\Entity\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $orderRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $orderService = new OrderService();
        $orderService->setPayseraBiller($payseraBiller);
        $orderService->setContainer($container);

        $container->expects($this->once())
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($orderRepository));

        $orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->will($this->returnValue($order));

        $payseraBiller->expects($this->once())
            ->method('setOrder');

        $payseraBiller->expects($this->once())
            ->method('bill');

        $orderService->billOrder(1, 'paysera');
    }

    public function testCreateOrderPlacePointGiven()
    {
        $placeId = 5;

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get', 'getParameter')
        );
        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $placeRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $placePoint = $this->getMockBuilder('Food\DishesBundle\Entity\PlacePoint')
            ->disableOriginalConstructor()
            ->getMock();
        $place = $this->getMockBuilder('Food\DishesBundle\Entity\Place')
            ->disableOriginalConstructor()
            ->getMock();
        $securityContext = $this->getMockBuilder('\Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();
        $token = $this->getMockBuilder('\Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken')
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder('\Food\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->at(0))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('FoodDishesBundle:Place')
            ->will($this->returnValue($placeRepository));

        $placeRepository->expects($this->once())
            ->method('find')
            ->with($placeId)
            ->will($this->returnValue($place));

        $container->expects($this->at(1))
            ->method('get')
            ->with('security.context')
            ->will($this->returnValue($securityContext));

        $securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $place->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Skanus maistas'));

        $place->expects($this->once())
            ->method('getSelfDelivery')
            ->will($this->returnValue(1));

        $placePoint->expects($this->once())
            ->method('getCity')
            ->will($this->returnValue('Vilnius'));

        $placePoint->expects($this->once())
            ->method('getAddress')
            ->will($this->returnValue('Laisves pr. 77'));

        $container->expects($this->any())
            ->method('getParameter')
            ->with('vat')
            ->will($this->returnValue(21));

        $user->expects($this->exactly(3))
            ->method('getId')
            ->will($this->returnValue(1254));

        $container->expects($this->at(3))
            ->method('get')
            ->with('request')
            ->will($this->returnValue($request));

        $request->expects($this->once())
            ->method('getClientIp')
            ->will($this->returnValue('192.1.10.15'));


        $orderService = new OrderService();
        $orderService->setContainer($container);

        $expectedOrder = new Order();
        $expectedOrder->setPlacePointSelfDelivery(true)
            ->setPlace($place)
            ->setPlacePoint($placePoint)
            ->setUser($user)
            ->setPlaceName('Skanus maistas')
            ->setPlacePointCity('Vilnius')
            ->setPlacePointAddress('Laisves pr. 77')
            ->setOrderDate(new \DateTime("now"))
            ->setUserIp('192.1.10.15');

        $gotOrder = $orderService->createOrder($placeId, $placePoint);


        $this->assertEquals($this->cleanOrderForCompare($expectedOrder->__toArray()), $this->cleanOrderForCompare($gotOrder->__toArray()));
    }

    public function testStatusesGetters()
    {
        $expectedOrderStatuses = array(
            OrderService::$status_new,
            OrderService::$status_accepted,
            OrderService::$status_delayed,
            OrderService::$status_forwarded,
            OrderService::$status_finished,
            OrderService::$status_assiged,
            OrderService::$status_completed,
            OrderService::$status_partialy_completed,
            OrderService::$status_canceled,
        );

        $expectedPaymentStatuses = array(
            OrderService::$paymentStatusNew,
            OrderService::$paymentStatusWait,
            OrderService::$paymentStatusWaitFunds,
            OrderService::$paymentStatusCanceled,
            OrderService::$paymentStatusComplete,
            OrderService::$paymentStatusError,
        );

        $gotOrderStatuses = OrderService::getOrderStatuses();
        $gotPaymentStatuses = OrderService::getPaymentStatuses();

        $this->assertEquals($expectedOrderStatuses, $gotOrderStatuses);
        $this->assertEquals($expectedPaymentStatuses, $gotPaymentStatuses);
    }

    public function testDeliveryTypeValidation()
    {
        $expected1 = true;
        $expected2 = false;
        $expected3 = true;
        $expected4 = false;
        $expected5 = false;

        $orderService = new OrderService();

        $gotValidity1 = $orderService->isValidDeliveryType('pickup');
        $gotValidity2 = $orderService->isValidDeliveryType('atidok');
        $gotValidity3 = $orderService->isValidDeliveryType('deliver');
        $gotValidity4 = $orderService->isValidDeliveryType('');
        $gotValidity5 = $orderService->isValidDeliveryType(null);

        $this->assertEquals($expected1, $gotValidity1);
        $this->assertEquals($expected2, $gotValidity2);
        $this->assertEquals($expected3, $gotValidity3);
        $this->assertEquals($expected4, $gotValidity4);
        $this->assertEquals($expected5, $gotValidity5);
    }

    public function testPaymentStatusValidity()
    {
        $expected1 = true;
        $expected2 = true;
        $expected3 = false;
        $expected4 = false;
        $expected5 = false;

        $orderService = new OrderService();

        $gotValidity1 = $orderService->isAllowedPaymentStatus('complete');
        $gotValidity2 = $orderService->isAllowedPaymentStatus('cancel');
        $gotValidity3 = $orderService->isAllowedPaymentStatus('assigned');
        $gotValidity4 = $orderService->isAllowedPaymentStatus('');
        $gotValidity5 = $orderService->isAllowedPaymentStatus(null);

        $this->assertEquals($expected1, $gotValidity1);
        $this->assertEquals($expected2, $gotValidity2);
        $this->assertEquals($expected3, $gotValidity3);
        $this->assertEquals($expected4, $gotValidity4);
        $this->assertEquals($expected5, $gotValidity5);
    }

    public function testPaymentStatusChange()
    {
        $expected1 = false;
        $expected2 = false;
        $expected3 = true;
        $expected4 = true;
        $expected5 = true;
        $expected6 = true;
        $expected7 = true;
        $expected8 = true;
        $expected9 = true;
        $expected10 = true;
        $expected11 = true;
        $expected12 = true;
        $expected13 = false;
        $expected14 = true;
        $expected15 = false;
        $expected16 = false;
        $expected17 = false;
        $expected18 = false;
        $expected19 = false;
        $expected20 = true;

        $orderService = new OrderService();

        $gotValidity1 = $orderService->isValidPaymentStatusChange('', '');
        $gotValidity2 = $orderService->isValidPaymentStatusChange('new', '');
        $gotValidity3 = $orderService->isValidPaymentStatusChange('new', 'wait');
        $gotValidity4 = $orderService->isValidPaymentStatusChange('new', 'wait_funds');
        $gotValidity5 = $orderService->isValidPaymentStatusChange('new', 'complete');
        $gotValidity6 = $orderService->isValidPaymentStatusChange('new', 'cancel');
        $gotValidity7 = $orderService->isValidPaymentStatusChange('new', 'error');
        $gotValidity8 = $orderService->isValidPaymentStatusChange('wait', 'wait_funds');
        $gotValidity9 = $orderService->isValidPaymentStatusChange('wait', 'complete');
        $gotValidity10 = $orderService->isValidPaymentStatusChange('wait', 'cancel');
        $gotValidity11 = $orderService->isValidPaymentStatusChange('wait', 'error');
        $gotValidity12 = $orderService->isValidPaymentStatusChange('cancel', 'complete');
        $gotValidity13 = $orderService->isValidPaymentStatusChange('complete', 'cancel');
        $gotValidity14 = $orderService->isValidPaymentStatusChange('error', 'complete');
        $gotValidity15 = $orderService->isValidPaymentStatusChange('error', 'cancel');
        $gotValidity16 = $orderService->isValidPaymentStatusChange('complete', 'new');
        $gotValidity17 = $orderService->isValidPaymentStatusChange('complete', 'wait');
        $gotValidity18 = $orderService->isValidPaymentStatusChange('completedas', 'wait');
        $gotValidity19 = $orderService->isValidPaymentStatusChange('complete', 'waitas');
        $gotValidity20 = $orderService->isValidPaymentStatusChange('', 'new');

        $this->assertEquals($expected1, $gotValidity1);
        $this->assertEquals($expected2, $gotValidity2);
        $this->assertEquals($expected3, $gotValidity3);
        $this->assertEquals($expected4, $gotValidity4);
        $this->assertEquals($expected5, $gotValidity5);
        $this->assertEquals($expected6, $gotValidity6);
        $this->assertEquals($expected7, $gotValidity7);
        $this->assertEquals($expected8, $gotValidity8);
        $this->assertEquals($expected9, $gotValidity9);
        $this->assertEquals($expected10, $gotValidity10);
        $this->assertEquals($expected11, $gotValidity11);
        $this->assertEquals($expected12, $gotValidity12);
        $this->assertEquals($expected13, $gotValidity13);
        $this->assertEquals($expected14, $gotValidity14);
        $this->assertEquals($expected15, $gotValidity15);
        $this->assertEquals($expected16, $gotValidity16);
        $this->assertEquals($expected17, $gotValidity17);
        $this->assertEquals($expected18, $gotValidity18);
        $this->assertEquals($expected19, $gotValidity19);
        $this->assertEquals($expected20, $gotValidity20);
    }

    public function testIsAllowedOrderStatusChange()
    {
        $expectedValidity1 = true;
        $expectedValidity2 = true;
        $expectedValidity3 = true;
        $expectedValidity4 = true;
        $expectedValidity5 = true;
        $expectedValidity6 = true;
        $expectedValidity7 = true;
        $expectedValidity8 = true;
        $expectedValidity9 = true;
        $expectedValidity10 = true;
        $expectedValidity11 = true;
        $expectedValidity12 = false;
        $expectedValidity13 = false;
        $expectedValidity14 = false;
        $expectedValidity15 = true;
        $expectedValidity16 = true;
        $expectedValidity17 = false;
        $expectedValidity18 = true;
        $expectedValidity19 = true;
        $expectedValidity20 = false;
        $expectedValidity21 = false;
        $expectedValidity22 = false;
        $expectedValidity23 = false;
        $expectedValidity24 = false;
        $expectedValidity25 = false;
        $expectedValidity26 = true;
        $expectedValidity27 = false;
        $expectedValidity28 = false;
        $expectedValidity29 = false;
        $expectedValidity30 = false;
        $expectedValidity31 = true;
        $expectedValidity32 = true;
        $expectedValidity33 = true;
        $expectedValidity34 = false;
        $expectedValidity35 = true;
        $expectedValidity36 = false;
        $expectedValidity37 = false;
        $expectedValidity38 = false;

        $orderService = new OrderService();

        $gotValidity1 = $orderService->isValidOrderStatusChange('new', 'accepted');
        $gotValidity2 = $orderService->isValidOrderStatusChange('new', 'canceled');
        $gotValidity3 = $orderService->isValidOrderStatusChange('new', 'finished');
        $gotValidity4 = $orderService->isValidOrderStatusChange('new', 'assigned');
        $gotValidity5 = $orderService->isValidOrderStatusChange('new', 'completed');
        $gotValidity6 = $orderService->isValidOrderStatusChange('new', 'delayed');
        $gotValidity7 = $orderService->isValidOrderStatusChange('accepted', 'canceled');
        $gotValidity8 = $orderService->isValidOrderStatusChange('accepted', 'assigned');
        $gotValidity9 = $orderService->isValidOrderStatusChange('accepted', 'finished');
        $gotValidity10 = $orderService->isValidOrderStatusChange('accepted', 'delayed');
        $gotValidity11 = $orderService->isValidOrderStatusChange('accepted', 'completed');
        $gotValidity12 = $orderService->isValidOrderStatusChange('accepted', 'new');
        $gotValidity13 = $orderService->isValidOrderStatusChange('accepted', 'accepted');
        $gotValidity14 = $orderService->isValidOrderStatusChange('finished', 'accepted');
        $gotValidity15 = $orderService->isValidOrderStatusChange('finished', 'assigned');
        $gotValidity16 = $orderService->isValidOrderStatusChange('finished', 'canceled');
        $gotValidity17 = $orderService->isValidOrderStatusChange('finished', 'new');
        $gotValidity18 = $orderService->isValidOrderStatusChange('assigned', 'canceled');
        $gotValidity19 = $orderService->isValidOrderStatusChange('assigned', 'completed');
        $gotValidity20 = $orderService->isValidOrderStatusChange('assigned', 'accepted');
        $gotValidity21 = $orderService->isValidOrderStatusChange('assigned', 'finished');
        $gotValidity22 = $orderService->isValidOrderStatusChange('completed', 'new');
        $gotValidity23 = $orderService->isValidOrderStatusChange('completed', 'accepted');
        $gotValidity24 = $orderService->isValidOrderStatusChange('completed', 'assigned');
        $gotValidity25 = $orderService->isValidOrderStatusChange('completed', 'finished');
        $gotValidity26 = $orderService->isValidOrderStatusChange('completed', 'canceled');
        $gotValidity27 = $orderService->isValidOrderStatusChange('canceled', 'new');
        $gotValidity28 = $orderService->isValidOrderStatusChange('canceled', 'assigned');
        $gotValidity29 = $orderService->isValidOrderStatusChange('canceled', 'accepted');
        $gotValidity30 = $orderService->isValidOrderStatusChange('canceled', 'finished');
        $gotValidity31 = $orderService->isValidOrderStatusChange('delayed', 'finished');
        $gotValidity32 = $orderService->isValidOrderStatusChange('delayed', 'assigned');
        $gotValidity33 = $orderService->isValidOrderStatusChange('delayed', 'completed');
        $gotValidity34 = $orderService->isValidOrderStatusChange('delayed', 'accepted');
        $gotValidity35 = $orderService->isValidOrderStatusChange('', 'new');
        $gotValidity36 = $orderService->isValidOrderStatusChange('delayed', '');
        $gotValidity37 = $orderService->isValidOrderStatusChange('', '');
        $gotValidity38 = $orderService->isValidOrderStatusChange('omg', 'accepted');

        $this->assertEquals($expectedValidity1, $gotValidity1);
        $this->assertEquals($expectedValidity2, $gotValidity2);
        $this->assertEquals($expectedValidity3, $gotValidity3);
        $this->assertEquals($expectedValidity4, $gotValidity4);
        $this->assertEquals($expectedValidity5, $gotValidity5);
        $this->assertEquals($expectedValidity6, $gotValidity6);
        $this->assertEquals($expectedValidity7, $gotValidity7);
        $this->assertEquals($expectedValidity8, $gotValidity8);
        $this->assertEquals($expectedValidity9, $gotValidity9);
        $this->assertEquals($expectedValidity10, $gotValidity10);
        $this->assertEquals($expectedValidity11, $gotValidity11);
        $this->assertEquals($expectedValidity12, $gotValidity12);
        $this->assertEquals($expectedValidity13, $gotValidity13);
        $this->assertEquals($expectedValidity14, $gotValidity14);
        $this->assertEquals($expectedValidity15, $gotValidity15);
        $this->assertEquals($expectedValidity16, $gotValidity16);
        $this->assertEquals($expectedValidity17, $gotValidity17);
        $this->assertEquals($expectedValidity18, $gotValidity18);
        $this->assertEquals($expectedValidity19, $gotValidity19);
        $this->assertEquals($expectedValidity20, $gotValidity20);
        $this->assertEquals($expectedValidity21, $gotValidity21);
        $this->assertEquals($expectedValidity22, $gotValidity22);
        $this->assertEquals($expectedValidity23, $gotValidity23);
        $this->assertEquals($expectedValidity24, $gotValidity24);
        $this->assertEquals($expectedValidity25, $gotValidity25);
        $this->assertEquals($expectedValidity26, $gotValidity26);
        $this->assertEquals($expectedValidity27, $gotValidity27);
        $this->assertEquals($expectedValidity28, $gotValidity28);
        $this->assertEquals($expectedValidity29, $gotValidity29);
        $this->assertEquals($expectedValidity30, $gotValidity30);
        $this->assertEquals($expectedValidity31, $gotValidity31);
        $this->assertEquals($expectedValidity32, $gotValidity32);
        $this->assertEquals($expectedValidity33, $gotValidity33);
        $this->assertEquals($expectedValidity34, $gotValidity34);
        $this->assertEquals($expectedValidity35, $gotValidity35);
        $this->assertEquals($expectedValidity36, $gotValidity36);
        $this->assertEquals($expectedValidity37, $gotValidity37);
        $this->assertEquals($expectedValidity38, $gotValidity38);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGenerateOrderHashException()
    {
        $orderService = new OrderService();
        $orderService->generateOrderHash('aa');
    }

    public function testIsValidDeliveryType()
    {
        $expectedValidity1 = true;
        $expectedValidity2 = true;
        $expectedValidity3 = false;
        $expectedValidity4 = false;

        $orderService = new OrderService();

        $gotValidity1 = $orderService->isValidDeliveryType('deliver');
        $gotValidity2 = $orderService->isValidDeliveryType('pickup');
        $gotValidity3 = $orderService->isValidDeliveryType('send');
        $gotValidity4 = $orderService->isValidDeliveryType('');

        $this->assertEquals($expectedValidity1, $gotValidity1);
        $this->assertEquals($expectedValidity2, $gotValidity2);
        $this->assertEquals($expectedValidity3, $gotValidity3);
        $this->assertEquals($expectedValidity4, $gotValidity4);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Delivery type must be set
     */
    public function testSetDeliveryTypeNoTypeException()
    {
        $orderService = new OrderService();
        $orderService->setDeliveryType('');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage is unknown or not allowed
     */
    public function testSetDeliveryTypeInvalidTypeException()
    {
        $order = $this->getMockBuilder('Food\OrderBundle\Entity\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $orderService = new OrderService();
        $orderService->setOrder($order);
        $orderService->setDeliveryType('atnesk');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage no order here
     */
    public function testSetDeliveryTypeNoOrderException()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get', 'getParameter')
        );

        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $orderService = new OrderService();
        $orderService->setContainer($container);

        $container->expects($this->once())
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $logger->expects($this->once())
            ->method('error');

        $orderService->setDeliveryType('deliver');
    }

    public function testSetDeliveryType()
    {
        $order = $this->getMockBuilder('Food\OrderBundle\Entity\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $order->expects($this->once())
            ->method('setDeliveryType')
            ->with('deliver');

        $orderService = new OrderService();
        $orderService->setOrder($order);

        $orderService->setDeliveryType('deliver');
    }

    public function testCreapyFixer()
    {
        $orderService = new OrderService();

        $expectedString = 'Sesios zasys su sesiais zasyciais. Gerve gyrune gyresi gera gira geroj girioj gerai gerusi.';
        $testedString = 'Šešios žąsys su šešiais žąsyčiais. Gervė gyrūnė gyrėsi gerą girą geroj girioj gerai gėrusi.';

        $reality = $orderService->creepyFixer($testedString);

        $this->assertEquals($expectedString, $reality);
    }

    private function cleanOrderForCompare($orderArray)
    {
        unset($orderArray['orderDate']);
        unset($orderArray['orderHash']);
    }
}