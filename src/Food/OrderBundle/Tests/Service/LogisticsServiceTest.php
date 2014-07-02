<?php
namespace Food\OrderBundle\Tests\Service;


use Food\AppBundle\Entity\Driver;
use Food\OrderBundle\Service\LogisticsService;
use Food\OrderBundle\Service\OrderService;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

class LogisticsServiceTest extends \PHPUnit_Framework_TestCase {
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
        $expectedLogisticsSystem1 = 'local';
        $expectedLogisticsSystem2 = 'external';

        $logisticsService = new LogisticsService();

        $gotLogisticsSystem1 = $logisticsService->getLogisticSystem();

        $logisticsService->setLogisticSystem('external');
        $gotLogisticsSystem2 = $logisticsService->getLogisticSystem();

        $this->assertEquals($expectedLogisticsSystem1, $gotLogisticsSystem1);
        $this->assertEquals($expectedLogisticsSystem2, $gotLogisticsSystem2);

        $orderService = new OrderService();
        $logisticsService->setOrderService($orderService);
        $gotOrderService = $logisticsService->getOrderService();

        $this->assertEquals($orderService, $gotOrderService);
    }

    public function testGetDrivers()
    {
        $logisticsService = $this->getMock(
            'Food\OrderBundle\Service\LogisticsService',
            array('getDriversExternal', 'getDriversLocal')
        );

        $drivers = array('Jonas', 'Petras');
        $extenalDrivers = array('Jani', 'Piotr');

        $logisticsService->expects($this->once())
            ->method('getDriversLocal')
            ->with('Vilnius')
            ->will($this->returnValue($drivers));

        $logisticsService->expects($this->once())
            ->method('getDriversExternal')
            ->with(25, 34)
            ->will($this->returnValue($extenalDrivers));

        $gotDrivers = $logisticsService->getDrivers(15, 16, 'Vilnius');
        $this->assertEquals($drivers, $gotDrivers);

        // External part
        $logisticsService->setLogisticSystem('etaxi');

        $gotExternalDrivers = $logisticsService->getDrivers(25, 34, 'Kaunas');
        $this->assertEquals($extenalDrivers, $gotExternalDrivers);
    }

    public function testGetDriverById()
    {
        $driverId = 22;
        $driver = new Driver();
        $driver->setName('Jonas Jonauskas');

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
        $driverRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
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
            ->with('Food\AppBundle\Entity\Driver')
            ->will($this->returnValue($driverRepository));

        $driverRepository->expects($this->once())
            ->method('find')
            ->with($driverId)
            ->will($this->returnValue($driver));

        $logisticService = new LogisticsService();
        $logisticService->setContainer($container);

        $driverGot = $logisticService->getDriverById($driverId);

        $this->assertEquals($driver, $driverGot);
    }

    public function testGetDriverByIdNotFound()
    {
        $expected = false;
        $driverId = 22;

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
        $driverRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
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
            ->with('Food\AppBundle\Entity\Driver')
            ->will($this->returnValue($driverRepository));

        $driverRepository->expects($this->once())
            ->method('find')
            ->with($driverId)
            ->will($this->returnValue(false));

        $logisticService = new LogisticsService();
        $logisticService->setContainer($container);

        $driverGot = $logisticService->getDriverById($driverId);

        $this->assertEquals($expected, $driverGot);
    }

    public function testGetDriversLocal()
    {
        $city = 'Vilnius';
        $driver1 = new Driver();
        $driver1->setName('Jonas Jonauskas');
        $driver2 = new Driver();
        $driver2->setName('Petras Petraitis');
        $expectedDrivers = array($driver1, $driver2);

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
        $driverRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
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
            ->with('Food\AppBundle\Entity\Driver')
            ->will($this->returnValue($driverRepository));

        $driverRepository->expects($this->once())
            ->method('findBy')
            ->with(array(
                'active' => true,
                'city' => $city,
            ))
            ->will($this->returnValue($expectedDrivers));

        $logisticService = new LogisticsService();
        $logisticService->setContainer($container);

        $driversGot = $logisticService->getDrivers(15, 16, 'Vilnius');

        $this->assertEquals($expectedDrivers, $driversGot);
    }

    public function testGetDriversLocalNotFound()
    {
        $city = 'Kaunas';
        $expectedDrivers = array();

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
        $driverRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
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
            ->with('Food\AppBundle\Entity\Driver')
            ->will($this->returnValue($driverRepository));

        $driverRepository->expects($this->once())
            ->method('findBy')
            ->with(array(
                'active' => true,
                'city' => $city,
            ))
            ->will($this->returnValue(false));

        $logisticService = new LogisticsService();
        $logisticService->setContainer($container);

        $driversGot = $logisticService->getDrivers(15, 16, 'Kaunas');

        $this->assertEquals($expectedDrivers, $driversGot);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage I are not implemented yet
     */
    public function testGetDriversExternal()
    {
        $logisticsService = new LogisticsService();
        $logisticsService->setLogisticSystem('etaxi');
        $logisticsService->getDrivers(15, 16, 'Vilnius');
    }

    public function testAssignDriver()
    {
        $driverId = 15;
        $orderIds = array(64);

        $driver = new Driver();
        $driver->setName('Jonas Jonauskas');

        $order =$this->getMock(
            'Food\OrderBundle\Entity\Order',
            array('setDriver')
        );

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );

        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $logisticsService = $this->getMock(
            'Food\OrderBundle\Service\LogisticsService',
            array('getDriverById', 'getDriversLocal')
        );
        $orderService = $this->getMock(
            'Food\OrderBundle\Service\OrderService',
            array('getOrderById', 'statusAssigned', 'saveOrder')
        );

        $logisticsService->setOrderService($orderService);
        $logisticsService->setContainer($container);

        $container->expects($this->once())
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $logger->expects($this->at(0))
            ->method('alert')
            ->with('++ assignDriver');

        $logger->expects($this->at(1))
            ->method('alert')
            ->with('driverId: '.$driverId);

        $logisticsService->expects($this->once())
            ->method('getDriverById')
            ->with($driverId)
            ->will($this->returnValue($driver));

        $orderService->expects($this->once())
            ->method('getOrderById')
            ->with($orderIds[0])
            ->will($this->returnValue($order));

        $order->expects($this->once())
            ->method('setDriver')
            ->with($driver);

        $orderService->expects($this->once())
            ->method('statusAssigned')
            ->with('logistics_service');

        $orderService->expects($this->once())
            ->method('saveOrder');

        $logisticsService->assignDriver($driverId, $orderIds);
    }

    public function testAssignDriverMultiple()
    {
        $driverId = 15;
        $orderIds = array(64, 84);

        $driver = new Driver();
        $driver->setName('Jonas Jonauskas');

        $order =$this->getMock(
            'Food\OrderBundle\Entity\Order',
            array('setDriver')
        );

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );

        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $logisticsService = $this->getMock(
            'Food\OrderBundle\Service\LogisticsService',
            array('getDriverById', 'getDriversLocal')
        );
        $orderService = $this->getMock(
            'Food\OrderBundle\Service\OrderService',
            array('getOrderById', 'statusAssigned', 'saveOrder')
        );

        $logisticsService->setOrderService($orderService);
        $logisticsService->setContainer($container);

        $container->expects($this->once())
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $logger->expects($this->at(0))
            ->method('alert')
            ->with('++ assignDriver');

        $logger->expects($this->at(1))
            ->method('alert')
            ->with('driverId: '.$driverId);

        $logisticsService->expects($this->once())
            ->method('getDriverById')
            ->with($driverId)
            ->will($this->returnValue($driver));

        $orderService->expects($this->exactly(2))
            ->method('getOrderById')
            ->will($this->returnValue($order));

        $order->expects($this->exactly(2))
            ->method('setDriver')
            ->with($driver);

        $orderService->expects($this->exactly(2))
            ->method('statusAssigned')
            ->with('logistics_service');

        $orderService->expects($this->exactly(2))
            ->method('saveOrder');

        $logisticsService->assignDriver($driverId, $orderIds);
    }
}