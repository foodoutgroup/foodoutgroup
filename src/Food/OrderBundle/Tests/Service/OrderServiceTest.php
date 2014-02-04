<?php
namespace Food\OrderBundle\Tests\Service;

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
    }

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