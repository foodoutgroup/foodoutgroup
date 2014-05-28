<?php
namespace Food\OrderBundle\Tests\Service;

use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\LocalBiller;
use Food\OrderBundle\Service\OrderService;
use Food\OrderBundle\Service\PaySera;

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
        $this->markTestIncomplete();
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
    }

    /**
     * TODO useles test?
     * @depends testGetPaymentSystemByMethod
     */
    public function testGetBillingInterface()
    {
        $this->markTestIncomplete();
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
}