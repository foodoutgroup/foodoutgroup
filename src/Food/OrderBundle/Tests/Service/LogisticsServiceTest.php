<?php
namespace Food\OrderBundle\Tests\Service;


use Food\AppBundle\Entity\Driver;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderDetails;
use Food\OrderBundle\Entity\OrderToLogistics;
use Food\OrderBundle\Service\LogisticsService;
use Food\OrderBundle\Service\OrderService;
use Food\UserBundle\Entity\User;
use Food\UserBundle\Entity\UserAddress;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

class LogisticsServiceTest extends \PHPUnit_Framework_TestCase {

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

        // Test Curl getters
        $curl = new \Curl();

        $curl2 = new \Curl();
        $curl2->options['CURLOPT_SSL_VERIFYPEER'] = false;
        $curl2->options['CURLOPT_SSL_VERIFYHOST'] = false;

        $logisticsService->setCli($curl);
        $gotCurl = $logisticsService->getCli();

        $this->assertEquals($curl, $gotCurl);

        $logisticsService->setCli(null);
        $gotCurl2 = $logisticsService->getCli();
        $this->assertEquals($curl2, $gotCurl2);
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
            array('getOrderById', 'statusAssigned', 'saveOrder', 'logOrder')
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
            ->method('logOrder')
            ->with(
                $order,
                'dispatcher_driver_assign',
                'Driver #15 assigned to order #64 from dispatcher'
            );

        $orderService->expects($this->once())
            ->method('statusAssigned')
            ->with('logistics_service');

        $orderService->expects($this->once())
            ->method('saveOrder');

        $logisticsService->assignDriver($driverId, $orderIds);
    }

    public function testAssignDriverExternal()
    {
        $driverId = 17;
        $orderIds = array(66);

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
            array('getOrderById', 'statusAssigned', 'saveOrder', 'logOrder')
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
            ->method('logOrder')
            ->with(
                $order,
                'logistics_api_driver_assign',
                'Driver #17 assigned to order #66 from logistics'
            );

        $orderService->expects($this->once())
            ->method('statusAssigned')
            ->with('logistics_service_external');

        $orderService->expects($this->once())
            ->method('saveOrder');

        $logisticsService->assignDriver($driverId, $orderIds, true);
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
            array('getOrderById', 'statusAssigned', 'saveOrder', 'logOrder')
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
            ->method('logOrder');

        $orderService->expects($this->exactly(2))
            ->method('statusAssigned')
            ->with('logistics_service');

        $orderService->expects($this->exactly(2))
            ->method('saveOrder');

        $logisticsService->assignDriver($driverId, $orderIds);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot generate xml with no order
     */
    public function testGenerateOrderXmlException()
    {
        $logisticsService = new LogisticsService();

        $logisticsService->generateOrderXml(null);
    }

    public function testGenerateOrderXml()
    {
        $logisticsService = new LogisticsService();
        $logisticsService->setLogisticSystem('external');

        $expectedXml =
'<?xml version="1.0" encoding="UTF-8"?>
<Order>
 <OrderId>215</OrderId>
 <PickUp>
  <Address>Laisvės pr. 125</Address>
  <City>Vilnius</City>
  <Coordinates>
   <Long>25.236428</Long>
   <Lat>54.728609</Lat>
  </Coordinates>
  <PointName>Super kebai</PointName>
  <PointId>24</PointId>
  <Phone>37063177771</Phone>
 </PickUp>
 <Delivery>
  <Address>Laisvės 77c-58</Address>
  <City>Vilnius</City>
  <AddressId>5</AddressId>
  <Coordinates>
   <Long>25.2352689</Long>
   <Lat>54.722515</Lat>
  </Coordinates>
  <CustomerName>Mantas</CustomerName>
  <Phone>37061514333</Phone>
  <CustomerComment>3 aukstas</CustomerComment>
 </Delivery>
 <PickUpTime>
  <From>2014-07-02 15:25</From>
  <To>2014-07-02 15:45</To>
 </PickUpTime>
 <DeliveryTime>
  <From>2014-07-02 15:25</From>
  <To>2014-07-02 16:25</To>
 </DeliveryTime>
 <PaymentMethod>local.card</PaymentMethod>
 <Price>14.7</Price>
 <Status>accepted</Status>
 <Content>
  <Item>
   <Id>6</Id>
   <Name>Kebabo kompleksas</Name>
   <Qty>2</Qty>
  </Item>
  <Item>
   <Id>9</Id>
   <Name>Mesainio kompleksas</Name>
   <Qty>1</Qty>
  </Item>
 </Content>
</Order>
';

        /**
         * @var Order $order
         */
        $order = $this->getMock(
            'Food\OrderBundle\Entity\Order',
            array('getId')
        );
        $order->setPlaceName('Super kebai')
            ->setPlacePointAddress('Laisvės pr. 125')
            ->setPlacePointCity('Vilnius')
            ->setComment('3 aukstas')
            ->setPaymentMethod('local.card')
            ->setAcceptTime(new \DateTime("2014-07-02 15:25"))
            ->setTotal(14.7)
            ->setOrderStatus(OrderService::$status_accepted);

        /**
         * @var PlacePoint $placePoint
         */
        $placePoint = $this->getMock(
            'Food\DishesBundle\Entity\PlacePoint',
            array('getId')
        );
        $placePoint->setPhone('37063177771')
            ->setLat('54.728609')
            ->setLon('25.236428');

        $user = new User();
        $user->setFirstname('Mantas')
            ->setPhone('37061514333');

        /**
         * @var UserAddress $userAddress
         */
        $userAddress = $this->getMock(
            'Food\UserBundle\Entity\UserAddress',
            array('getId')
        );
        $userAddress->setAddress('Laisvės 77c-58')
            ->setCity('Vilnius')
            ->setLat('54.722515')
            ->setLon('25.2352689')
            ->setUser($user);

        /**
         * @var OrderDetails $detail
         */
        $detail = $this->getMock(
            'Food\OrderBundle\Entity\OrderDetails',
            array('getId', 'getDishName', 'getQuantity')
        );

        $order->setPlacePoint($placePoint)
            ->setAddressId($userAddress)
            ->setUser($user)
            ->addDetail($detail)
            ->addDetail($detail);

        $order->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(215));

        $placePoint->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(24));

        $userAddress->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(5));

        $detail->expects($this->at(0))
            ->method('getId')
            ->will($this->returnValue(6));

        $detail->expects($this->at(1))
            ->method('getDishName')
            ->will($this->returnValue('Kebabo kompleksas'));

        $detail->expects($this->at(2))
            ->method('getQuantity')
            ->will($this->returnValue(2));

        $detail->expects($this->at(3))
            ->method('getId')
            ->will($this->returnValue(9));

        $detail->expects($this->at(4))
            ->method('getDishName')
            ->will($this->returnValue('Mesainio kompleksas'));

        $detail->expects($this->at(5))
            ->method('getQuantity')
            ->will($this->returnValue(1));

        $xmlReturn = $logisticsService->generateOrderXml($order);

        // Suvienodinam line endus pries palyginima. Negrazu, bet the way it is
        $xmlReturn = $this->cleanXmlBeforeAssert($xmlReturn);
        $expectedXml = $this->cleanXmlBeforeAssert($expectedXml);


        $this->assertEquals($expectedXml, $xmlReturn);
    }

    public function testGenerateOrderXmlDelayed()
    {
        $logisticsService = new LogisticsService();
        $logisticsService->setLogisticSystem('external');

        $expectedXml =
'<?xml version="1.0" encoding="UTF-8"?>
<Order>
 <OrderId>215</OrderId>
 <PickUp>
  <Address>Laisvės pr. 125</Address>
  <City>Vilnius</City>
  <Coordinates>
   <Long>25.236428</Long>
   <Lat>54.728609</Lat>
  </Coordinates>
  <PointName>Super kebai</PointName>
  <PointId>24</PointId>
  <Phone>37063177771</Phone>
 </PickUp>
 <Delivery>
  <Address>Laisvės 77c-58</Address>
  <City>Vilnius</City>
  <AddressId>5</AddressId>
  <Coordinates>
   <Long>25.2352689</Long>
   <Lat>54.722515</Lat>
  </Coordinates>
  <CustomerName>Mantas</CustomerName>
  <Phone>37061514333</Phone>
  <CustomerComment>3 aukstas</CustomerComment>
 </Delivery>
 <PickUpTime>
  <From>2014-07-02 15:55</From>
  <To>2014-07-02 16:15</To>
 </PickUpTime>
 <DeliveryTime>
  <From>2014-07-02 15:55</From>
  <To>2014-07-02 16:55</To>
 </DeliveryTime>
 <PaymentMethod>local.card</PaymentMethod>
 <Price>14.7</Price>
 <Status>accepted</Status>
 <Content>
  <Item>
   <Id>6</Id>
   <Name>Kebabo kompleksas</Name>
   <Qty>2</Qty>
  </Item>
  <Item>
   <Id>9</Id>
   <Name>Mesainio kompleksas</Name>
   <Qty>1</Qty>
  </Item>
 </Content>
</Order>
';

        /**
         * @var Order $order
         */
        $order = $this->getMock(
            'Food\OrderBundle\Entity\Order',
            array('getId')
        );
        $order->setPlaceName('Super kebai')
            ->setPlacePointAddress('Laisvės pr. 125')
            ->setPlacePointCity('Vilnius')
            ->setComment('3 aukstas')
            ->setPaymentMethod('local.card')
            ->setAcceptTime(new \DateTime("2014-07-02 15:25"))
            ->setDelayed(true)
            ->setDelayDuration(30)
            ->setTotal(14.7)
            ->setOrderStatus(OrderService::$status_accepted);

        /**
         * @var PlacePoint $placePoint
         */
        $placePoint = $this->getMock(
            'Food\DishesBundle\Entity\PlacePoint',
            array('getId')
        );
        $placePoint->setPhone('37063177771')
            ->setLat('54.728609')
            ->setLon('25.236428');

        $user = new User();
        $user->setFirstname('Mantas')
            ->setPhone('37061514333');

        /**
         * @var UserAddress $userAddress
         */
        $userAddress = $this->getMock(
            'Food\UserBundle\Entity\UserAddress',
            array('getId')
        );
        $userAddress->setAddress('Laisvės 77c-58')
            ->setCity('Vilnius')
            ->setLat('54.722515')
            ->setLon('25.2352689')
            ->setUser($user);

        /**
         * @var OrderDetails $detail
         */
        $detail = $this->getMock(
            'Food\OrderBundle\Entity\OrderDetails',
            array('getId', 'getDishName', 'getQuantity')
        );

        $order->setPlacePoint($placePoint)
            ->setAddressId($userAddress)
            ->setUser($user)
            ->addDetail($detail)
            ->addDetail($detail);

        $order->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(215));

        $placePoint->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(24));

        $userAddress->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(5));

        $detail->expects($this->at(0))
            ->method('getId')
            ->will($this->returnValue(6));

        $detail->expects($this->at(1))
            ->method('getDishName')
            ->will($this->returnValue('Kebabo kompleksas'));

        $detail->expects($this->at(2))
            ->method('getQuantity')
            ->will($this->returnValue(2));

        $detail->expects($this->at(3))
            ->method('getId')
            ->will($this->returnValue(9));

        $detail->expects($this->at(4))
            ->method('getDishName')
            ->will($this->returnValue('Mesainio kompleksas'));

        $detail->expects($this->at(5))
            ->method('getQuantity')
            ->will($this->returnValue(1));

        $xmlReturn = $logisticsService->generateOrderXml($order);

        // Suvienodinam line endus pries palyginima. Negrazu, bet the way it is
        $xmlReturn = $this->cleanXmlBeforeAssert($xmlReturn);
        $expectedXml = $this->cleanXmlBeforeAssert($expectedXml);


        $this->assertEquals($expectedXml, $xmlReturn);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown payment method: kreditnaja kartacka
     */
    public function testConvertPaymentMethodException()
    {
        $logisticsService = new LogisticsService();

        $logisticsService->convertPaymentMethod('kreditnaja kartacka');
    }

    /**
     * @depends testConvertPaymentMethodException
     */
    public function testConvertPaymentMethod()
    {
        $logisticsService = new LogisticsService();

        $expectedMethod1 = 'local';
        $expectedMethod2 = 'local.card';
        $expectedMethod3 = 'prepaid';
        $expectedMethod4 = 'prepaid';

        $actualMethod1 = $logisticsService->convertPaymentMethod('local');
        $actualMethod2 = $logisticsService->convertPaymentMethod('local.card');
        $actualMethod3 = $logisticsService->convertPaymentMethod('banklink');
        $actualMethod4 = $logisticsService->convertPaymentMethod('paysera');

        $this->assertEquals($expectedMethod1, $actualMethod1);
        $this->assertEquals($expectedMethod2, $actualMethod2);
        $this->assertEquals($expectedMethod3, $actualMethod3);
        $this->assertEquals($expectedMethod4, $actualMethod4);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot put order to logistis when its not order. Dafuk?
     */
    public function testPutOrderForSendException()
    {
        $logisticsService = new LogisticsService();
        $logisticsService->putOrderForSend(null);
    }

    public function testPutOrderForSend()
    {
        $logisticsService = new LogisticsService();

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get', 'getParameter')
        );

        $orderService = $this->getMockBuilder('Food\OrderBundle\Service\OrderService')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $logisticsService->setContainer($container);

        $order = new Order();
        $order->setPlacePointSelfDelivery(false)
            ->setDeliveryType(OrderService::$deliveryDeliver);

        $placePoint = new PlacePoint();
        $placePoint->setUseExternalLogistics(true);
        $order->setPlacePoint($placePoint);

        $container->expects($this->at(0))
            ->method('getParameter')
            ->with('logistics.send_to_external')
            ->will($this->returnValue(true));

        $container->expects($this->at(1))
            ->method('getParameter')
            ->with('logistics.city_filter')
            ->will($this->returnValue(array()));

        $container->expects($this->at(2))
            ->method('get')
            ->with('food.order')
            ->will($this->returnValue($orderService));

        $orderService->expects($this->once())
            ->method('logOrder')
            ->with(
                $order,
                'schedule_logistics_api_send',
                'Order scheduled to send to logistics'
            );

        $container->expects($this->at(3))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        // Cia inicijuojam expecta, nes po to atsiranda sekundes skirtumas
        $expectedOrderSendObject = new OrderToLogistics();
        $expectedOrderSendObject->setOrder($order)
            ->setStatus('unsent')
            ->setDateAdded(new \DateTime("now"));

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($expectedOrderSendObject);

        $entityManager->expects($this->once())
            ->method('flush');

        $logisticsService->putOrderForSend($order);
    }

    public function testPutOrderForSendWithCityCheck()
    {
        $logisticsService = new LogisticsService();

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get', 'getParameter')
        );

        $orderService = $this->getMockBuilder('Food\OrderBundle\Service\OrderService')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $logisticsService->setContainer($container);

        $order = new Order();
        $order->setPlacePointSelfDelivery(false)
            ->setDeliveryType(OrderService::$deliveryDeliver)
            ->setPlacePointCity('Kaunas');

        $container->expects($this->at(0))
            ->method('getParameter')
            ->with('logistics.send_to_external')
            ->will($this->returnValue(true));

        $container->expects($this->at(1))
            ->method('getParameter')
            ->with('logistics.city_filter')
            ->will($this->returnValue(array('Vilnius', 'Kaunas')));

        $container->expects($this->at(2))
            ->method('get')
            ->with('food.order')
            ->will($this->returnValue($orderService));

        $orderService->expects($this->once())
            ->method('logOrder')
            ->with(
                $order,
                'schedule_logistics_api_send',
                'Order scheduled to send to logistics'
            );

        $container->expects($this->at(3))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())
            ->method('persist');

        $entityManager->expects($this->once())
            ->method('flush');

        $logisticsService->putOrderForSend($order);
    }

    public function testPutOrderForSendWithCityCheckFiltered()
    {
        $logisticsService = new LogisticsService();

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get', 'getParameter')
        );

        $logisticsService->setContainer($container);

        $order = new Order();
        $order->setPlacePointSelfDelivery(false)
            ->setDeliveryType(OrderService::$deliveryDeliver)
            ->setPlacePointCity('Klaipėda');

        $container->expects($this->at(0))
            ->method('getParameter')
            ->with('logistics.send_to_external')
            ->will($this->returnValue(true));

        $container->expects($this->at(1))
            ->method('getParameter')
            ->with('logistics.city_filter')
            ->will($this->returnValue(array('Vilnius', 'Kaunas')));

        $container->expects($this->never())
            ->method('get');

        $logisticsService->putOrderForSend($order);
    }

    public function testPutPickupOrderForSend()
    {
        $logisticsService = new LogisticsService();

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get', 'getParameter')
        );

        $logisticsService->setContainer($container);

        $order = new Order();
        $order->setPlacePointSelfDelivery(false)
            ->setDeliveryType(OrderService::$deliveryPickup);

        $container->expects($this->at(0))
            ->method('getParameter')
            ->with('logistics.send_to_external')
            ->will($this->returnValue(true));

        $container->expects($this->never())
            ->method('get');

        $logisticsService->putOrderForSend($order);
    }

    public function testPutSelfDeliveredOrderForSend()
    {
        $logisticsService = new LogisticsService();

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get', 'getParameter')
        );

        $logisticsService->setContainer($container);

        $order = new Order();
        $order->setPlacePointSelfDelivery(true)
            ->setDeliveryType(OrderService::$deliveryDeliver);

        $container->expects($this->at(0))
            ->method('getParameter')
            ->with('logistics.send_to_external')
            ->will($this->returnValue(true));

        $container->expects($this->never())
            ->method('get');

        $logisticsService->putOrderForSend($order);
    }



    public function testSendOrderToLogistics()
    {
        $logisticsService = new LogisticsService();

        $curl = $this->getMockBuilder('Curl')
            ->disableOriginalConstructor()
            ->getMock();
        $logisticsService->setCli($curl);

        $url = 'http://test-url.foodout.lt';
        $xml = '<so xml>';

        /**
         * @var \CurlResponse $curlResponse
         */
        $curlResponse  = $this->getMockBuilder('CurlResponse')
            ->disableOriginalConstructor()
            ->getMock();
        $curlResponse->headers = array(
            'Status-Code' => 200,
        );
        $curlResponse->body = '';

        $expectedResponse = array(
            'status' => 'sent',
            'error' => '',
        );

        $curl->expects($this->once())
            ->method('post')
            ->with($url, $xml)
            ->will($this->returnValue($curlResponse));

        $response = $logisticsService->sendToLogistics(
            $url,
            $xml
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testDriverXmlParse()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<RoutesAssigned>
<RouteAssigned>
<Order_id>324169</Order_id>
<Driver_id>165</Driver_id>
<Vehicle_no>FCU 819</Vehicle_no>
<Planned_delivery_time>2014-07-02 11:43</Planned_delivery_time>
</RouteAssigned>
</RoutesAssigned>';

        $expectedDriverData = array(
            array(
                'order_id' => 324169,
                'driver_id' => 165,
                'vehicle_no' => 'FCU 819',
                'planned_delivery_time' => new \DateTime("2014-07-02 11:43"),
            ),
        );

        $logisticsService = new LogisticsService();

        $driverData = $logisticsService->parseDriverAssignXml($xml);

        $this->assertEquals($expectedDriverData, $driverData);
    }

    public function testMultipleDriverXmlParse()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<RoutesAssigned>
<RouteAssigned>
<Order_id>324169</Order_id>
<Driver_id>165</Driver_id>
<Vehicle_no>FCU 819</Vehicle_no>
<Planned_delivery_time>2014-07-02 11:43</Planned_delivery_time>
</RouteAssigned>
<RouteAssigned>
<Order_id>3242</Order_id>
<Driver_id>123</Driver_id>
<Vehicle_no>ABC 232</Vehicle_no>
<Planned_delivery_time>2014-07-31 21:22</Planned_delivery_time>
</RouteAssigned>
</RoutesAssigned>';

        $expectedDriverData = array(
            array(
                'order_id' => 324169,
                'driver_id' => 165,
                'vehicle_no' => 'FCU 819',
                'planned_delivery_time' => new \DateTime("2014-07-02 11:43"),
            ),
            array(
                'order_id' => 3242,
                'driver_id' => 123,
                'vehicle_no' => 'ABC 232',
                'planned_delivery_time' => new \DateTime("2014-07-31 21:22"),
            ),
        );

        $logisticsService = new LogisticsService();

        $driverData = $logisticsService->parseDriverAssignXml($xml);

        $this->assertEquals($expectedDriverData, $driverData);
    }

    public function testOrderStatusXmlParse()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<ShipmentStatuses>
    <ShipmentStatus>
        <Order_id>324169</Order_id>
        <Event_Date>2014-07-02 11:43</Event_Date>
        <Status>finished</Status>
        <FailReason></FailReason>
    </ShipmentStatus>
</ShipmentStatuses>';

        $expectedStatusData = array(array(
            'order_id' => 324169,
            'event_date' => new \DateTime("2014-07-02 11:43"),
            'status' => 'finished',
            'fail_reason' => '',
        ));

        $logisticsService = new LogisticsService();

        $statusData = $logisticsService->parseOrderStatusXml($xml);

        $this->assertEquals($expectedStatusData, $statusData);
    }

    public function testOrderStatusXmlParse2()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<ShipmentStatuses>
    <ShipmentStatus>
        <Order_id>3241</Order_id>
        <Event_Date>2014-07-02 17:15</Event_Date>
        <Status>failed</Status>
        <FailReason>Client rejected</FailReason>
    </ShipmentStatus>
</ShipmentStatuses>';

        $expectedStatusData = array(array(
            'order_id' => 3241,
            'event_date' => new \DateTime("2014-07-02 17:15"),
            'status' => 'failed',
            'fail_reason' => 'Client rejected',
        ));

        $logisticsService = new LogisticsService();

        $statusData = $logisticsService->parseOrderStatusXml($xml);

        $this->assertEquals($expectedStatusData, $statusData);
    }

    public function testOrderStatusXmlParseMultiple()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<ShipmentStatuses>
    <ShipmentStatus>
        <Order_id>3231</Order_id>
        <Event_Date>2014-07-02 17:15</Event_Date>
        <Status>failed</Status>
        <FailReason>Client rejected order</FailReason>
    </ShipmentStatus>
    <ShipmentStatus>
        <Order_id>3243</Order_id>
        <Event_Date>2014-07-02 17:16</Event_Date>
        <Status>finished</Status>
        <FailReason/>
    </ShipmentStatus>
</ShipmentStatuses>';

        $expectedStatusData = array(
            array(
                'order_id' => 3231,
                'event_date' => new \DateTime("2014-07-02 17:15"),
                'status' => 'failed',
                'fail_reason' => 'Client rejected order',
            ),
            array(
                'order_id' => 3243,
                'event_date' => new \DateTime("2014-07-02 17:16"),
                'status' => 'finished',
                'fail_reason' => '',
            )
        );

        $logisticsService = new LogisticsService();

        $statusData = $logisticsService->parseOrderStatusXml($xml);

        $this->assertEquals($expectedStatusData, $statusData);
    }

    public function testGenerateDriversXml()
    {
        $logisticsService = new LogisticsService();

        $expectedXml = '<?xml version="1.0" encoding="UTF-8"?>
<Drivers>
 <Driver>
  <Id>16</Id>
  <Phone>37061274411</Phone>
  <Name>Vardenis Pavardenis</Name>
  <City>Vilnius</City>
  <Active>Y</Active>
 </Driver>
 <Driver>
  <Id>301</Id>
  <Phone>37149931555</Phone>
  <Name>Pūkis Baisoklis</Name>
  <City>Rīga</City>
  <Active>N</Active>
 </Driver>
</Drivers>
';

        /**
         * @var Driver $driver1
         */
        $driver1 = $this->getMock(
            'Food\AppBundle\Entity\Driver',
            array('getId')
        );
        $driver1->setPhone('37061274411')
            ->setName('Vardenis Pavardenis')
            ->setCity('Vilnius')
            ->setActive(true);

        /**
         * @var Driver $driver2
         */
        $driver2 = $this->getMock(
            'Food\AppBundle\Entity\Driver',
            array('getId')
        );
        $driver2->setPhone('37149931555')
            ->setName('Pūkis Baisoklis')
            ->setCity('Rīga')
            ->setActive(false);

        $driver1->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(16));

        $driver2->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(301));

        $drivers = array(
            $driver1, $driver2
        );

        $gotXml = $logisticsService->generateDriverXml($drivers);

        // Suvienodinam line endus pries palyginima. Negrazu, bet the way it is
        $gotXml = $this->cleanXmlBeforeAssert($gotXml);
        $expectedXml = $this->cleanXmlBeforeAssert($expectedXml);

        $this->assertEquals($expectedXml, $gotXml);
    }

    public function testConvertCityForLogTime()
    {
        $testCities = array(
            'Vilnius' => 'Vilnius',
            'Kaunas' => 'Kaunas',
            'Klaipėda' => 'Klaipėda',
            'Klaipeda' => 'Klaipėda',
            'Vilniaus rajonas' => 'Vilnius',
            'Vilniaus raj.' => 'Vilnius',
            'Kauno raj.' => 'Kaunas',
            'Klaipėdos raj.' => 'Klaipėda',
            'Plungė' => 'Plungė',
        );

        $logisticsService = new LogisticsService();

        foreach($testCities as $testCity => $expectedCity) {
            $gotCity = $logisticsService->convertCityForLogTime($testCity);

            $this->assertEquals($expectedCity, $gotCity);
        }
    }

    /**
     * Clean xml that PhpUnit wont fail because of new lines...
     * @param string $xml
     * @return string
     */
    private function cleanXmlBeforeAssert($xml)
    {
        return  preg_replace('~(*BSR_ANYCRLF)\R~', "\r\n", $xml);
    }
}