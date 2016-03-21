<?php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UncompleteOrdersCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testNoUncompleteOrders()
    {
        $expectedReturn = 0;

        $orders = array();
        $fromDate = new \DateTime(
            date("Y-m-d 00:00:01", strtotime("-4 day"))
        );
        $toDate = new \DateTime(
            date("Y-m-d 23:59:59", strtotime("-1 day"))
        );

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $monitoringService = $this->getMockBuilder('\Food\MonitoringBundle\Service\MonitoringService')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new \Food\MonitoringBundle\Command\UncompleteOrdersCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('monitoring:order:uncomplete');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.monitoring')
            ->will($this->returnValue($monitoringService));

        $monitoringService->expects($this->once())
            ->method('getUnfinishedOrdersForRange')
            ->with($fromDate, $toDate)
            ->will($this->returnValue($orders));

        $container->expects($this->at(1))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $connection->expects($this->once())
            ->method('close');

        $commandTester = new CommandTester($command);
        $gotReturn = $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/all orders are completed successfuly/', $commandTester->getDisplay());
        $this->assertEquals($expectedReturn, $gotReturn);
    }

    public function testOneUncompleteOrder()
    {
        $expectedReturn = 2;

        $order = $this->getMockBuilder('Food\OrderBundle\Entity\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orders = array($order);

        $fromDate = new \DateTime(
            date("Y-m-d 00:00:01", strtotime("-4 day"))
        );
        $toDate = new \DateTime(
            date("Y-m-d 23:59:59", strtotime("-1 day"))
        );

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $monitoringService = $this->getMockBuilder('\Food\MonitoringBundle\Service\MonitoringService')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new \Food\MonitoringBundle\Command\UncompleteOrdersCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('monitoring:order:uncomplete');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.monitoring')
            ->will($this->returnValue($monitoringService));

        $monitoringService->expects($this->once())
            ->method('getUnfinishedOrdersForRange')
            ->with($fromDate, $toDate)
            ->will($this->returnValue($orders));

        $container->expects($this->at(1))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $connection->expects($this->once())
            ->method('close');

        $order->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(154));

        $commandTester = new CommandTester($command);
        $gotReturn = $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/1 uncomplete orders! Ids: 154/', $commandTester->getDisplay());
        $this->assertEquals($expectedReturn, $gotReturn);
    }

    public function testThreeUncompleteOrder()
    {
        $expectedReturn = 2;

        $order = $this->getMockBuilder('Food\OrderBundle\Entity\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orders = array($order, $order, $order);

        $fromDate = new \DateTime(
            date("Y-m-d 00:00:01", strtotime("-4 day"))
        );
        $toDate = new \DateTime(
            date("Y-m-d 23:59:59", strtotime("-1 day"))
        );

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $monitoringService = $this->getMockBuilder('\Food\MonitoringBundle\Service\MonitoringService')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new \Food\MonitoringBundle\Command\UncompleteOrdersCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('monitoring:order:uncomplete');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.monitoring')
            ->will($this->returnValue($monitoringService));

        $monitoringService->expects($this->once())
            ->method('getUnfinishedOrdersForRange')
            ->with($fromDate, $toDate)
            ->will($this->returnValue($orders));

        $container->expects($this->at(1))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $connection->expects($this->once())
            ->method('close');

        $order->expects($this->at(0))
            ->method('getId')
            ->will($this->returnValue(128));

        $order->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(165));

        $order->expects($this->at(2))
            ->method('getId')
            ->will($this->returnValue(187));

        $commandTester = new CommandTester($command);
        $gotReturn = $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/3 uncomplete orders! Ids: 128, 165, 187/', $commandTester->getDisplay());
        $this->assertEquals($expectedReturn, $gotReturn);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage I failed
     */
    public function testExceptionHappened()
    {
        $expectedReturn = 2;

        $fromDate = new \DateTime(
            date("Y-m-d 00:00:01", strtotime("-4 day"))
        );
        $toDate = new \DateTime(
            date("Y-m-d 23:59:59", strtotime("-1 day"))
        );

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $monitoringService = $this->getMockBuilder('\Food\MonitoringBundle\Service\MonitoringService')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new \Food\MonitoringBundle\Command\UncompleteOrdersCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('monitoring:order:uncomplete');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.monitoring')
            ->will($this->returnValue($monitoringService));

        $monitoringService->expects($this->once())
            ->method('getUnfinishedOrdersForRange')
            ->with($fromDate, $toDate)
            ->will($this->throwException(new \Exception('I failed')));

        $commandTester = new CommandTester($command);
        $gotReturn = $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/Error in unfinished orders check: I failed/', $commandTester->getDisplay());
        $this->assertEquals($expectedReturn, $gotReturn);
    }
}