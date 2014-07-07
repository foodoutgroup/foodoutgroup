<?php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MonthlyDriverReportCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testSuccessfulReport()
    {
        $accountingEmail = 'dev@foodout.lt';
        $orders = array('1', array('2'));
        $lastMonthDate = date("Y-m", strtotime('-1 month'));

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $orderService = $this->getMockBuilder('\Food\OrderBundle\Service\OrderService')
            ->disableOriginalConstructor()
            ->getMock();

        $translatorService = $this->getMockBuilder('\Symfony\Bundle\FrameworkBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $mailerService = $this->getMockBuilder('Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();

        $templatingService = $this->getMockBuilder('\Symfony\Bundle\TwigBundle\Debug\TimedTwigEngine')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new \Food\OrderBundle\Command\MonthlyDriverReportCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('order:report:monthly_driver');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.order')
            ->will($this->returnValue($orderService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('translator')
            ->will($this->returnValue($translatorService));

        $orderService->expects($this->once())
            ->method('getDriversMonthlyOrderCount')
            ->will($this->returnValue($orders));

        $container->expects($this->at(2))
            ->method('get')
            ->with('mailer')
            ->will($this->returnValue($mailerService));

        $container->expects($this->at(3))
            ->method('getParameter')
            ->with('title')
            ->will($this->returnValue('Foodout.lt'));

        $container->expects($this->at(4))
            ->method('getParameter')
            ->with('domain')
            ->will($this->returnValue('foodout.lt'));

        $translatorService->expects($this->at(0))
            ->method('trans')
            ->with('general.email.driver_monthly_report')
            ->will($this->returnValue('praejusio menesio vairuotoju ataskaita'));

        $container->expects($this->at(6))
            ->method('get')
            ->with('templating')
            ->will($this->returnValue($templatingService));

        $templatingService->expects($this->once())
            ->method('render')
            ->with(
                'FoodOrderBundle:Command:accounting_monthly_driver_report.html.twig',
                array(
                    'orders' => $orders,
                    'reportFor' => $lastMonthDate,
                )
            )
            ->will($this->returnValue(''));

        $mailerService->expects($this->once())
            ->method('send');

        $container->expects($this->at(5))
            ->method('getParameter')
            ->with('accounting_email')
            ->will($this->returnValue($accountingEmail));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/Message sent to: '.$accountingEmail.'/', $commandTester->getDisplay());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage I failed
     */
    public function testExceptionReport()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $orderService = $this->getMockBuilder('\Food\OrderBundle\Service\OrderService')
            ->disableOriginalConstructor()
            ->getMock();

        $translatorService = $this->getMockBuilder('\Symfony\Bundle\FrameworkBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new \Food\OrderBundle\Command\MonthlyDriverReportCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('order:report:monthly_driver');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.order')
            ->will($this->returnValue($orderService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('translator')
            ->will($this->returnValue($translatorService));

        $orderService->expects($this->once())
            ->method('getDriversMonthlyOrderCount')
            ->will($this->throwException(new \Exception('I failed')));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/Error: I failed/', $commandTester->getDisplay());
    }
}