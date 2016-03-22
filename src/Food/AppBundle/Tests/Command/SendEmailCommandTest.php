<?php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Food\AppBundle\Command\SendEmailCommand;

class SendEmailCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testWrongFormatEmail()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $mailService = $this->getMockBuilder('\Food\AppBundle\Service\MailService')
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $orderService = $this->getMockBuilder('\Food\OrderBundle\Service\OrderService')
            ->disableOriginalConstructor()
            ->getMock();

        $emailToSend = $this->getMockBuilder('\Food\AppBundle\Entity\EmailToSend')
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMockBuilder('\Food\OrderBundle\Entity\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new SendEmailCommand());

        /**
         * @var SendEmailCommand $command
         */
        $command = $application->find('email:send');
        $command->setContainer($container);
        $command->setMaxChecks(1);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.mail')
            ->will($this->returnValue($mailService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $container->expects($this->at(2))
            ->method('get')
            ->with('food.order')
            ->will($this->returnValue($orderService));

        $mailService->expects($this->once())
            ->method('getEmailsToSend')
            ->will($this->returnValue(array(
                $emailToSend
            )));

        $emailToSend->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue(15));

        $emailToSend->expects($this->exactly(2))
            ->method('getType')
            ->will($this->returnValue('omgUnknownType'));

        $emailToSend->expects($this->exactly(3))
            ->method('getOrder')
            ->will($this->returnValue($order));

        $order->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue(123));

        $logger->expects($this->once())
            ->method('error')
            ->with('Error while sending an email. Mail ID: 15 type: "omgUnknownType". Error: Damaged order in email sending found. Order ID: 123');

//        $mailService->expects($this->once())
//            ->method('markEmailSent')
//            ->with($emailToSend);

        $logger->expects($this->once())
            ->method('alert');

        $container->expects($this->at(3))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $connection->expects($this->once())
            ->method('close');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/1 unsent emails found/', $commandTester->getDisplay());
        $this->assertRegExp('/Sending message id: 15 of type: "omgUnknownType" for order id: 123/', $commandTester->getDisplay());
        $this->assertRegExp('/0 emails sent in [0-9\.]{1,5}/', $commandTester->getDisplay());
    }

    public function testNoEmailsToSend()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $mailService = $this->getMockBuilder('\Food\AppBundle\Service\MailService')
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $orderService = $this->getMockBuilder('\Food\OrderBundle\Service\OrderService')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new SendEmailCommand());

        /**
         * @var SendEmailCommand $command
         */
        $command = $application->find('email:send');
        $command->setContainer($container);
        $command->setMaxChecks(1);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.mail')
            ->will($this->returnValue($mailService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $container->expects($this->at(2))
            ->method('get')
            ->with('food.order')
            ->will($this->returnValue($orderService));

        $mailService->expects($this->once())
            ->method('getEmailsToSend')
            ->will($this->returnValue(array()));

        $container->expects($this->at(3))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $connection->expects($this->once())
            ->method('close');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/0 unsent emails found/', $commandTester->getDisplay());
        $this->assertRegExp('/0 emails sent in [0-9\.]{1,5}/', $commandTester->getDisplay());
    }

    public function testCompleteEmailSend()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $mailService = $this->getMockBuilder('\Food\AppBundle\Service\MailService')
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $orderService = $this->getMockBuilder('\Food\OrderBundle\Service\OrderService')
            ->disableOriginalConstructor()
            ->getMock();

        $emailToSend = $this->getMockBuilder('\Food\AppBundle\Entity\EmailToSend')
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMockBuilder('\Food\OrderBundle\Entity\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new SendEmailCommand());

        /**
         * @var SendEmailCommand $command
         */
        $command = $application->find('email:send');
        $command->setContainer($container);
        $command->setMaxChecks(1);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.mail')
            ->will($this->returnValue($mailService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $container->expects($this->at(2))
            ->method('get')
            ->with('food.order')
            ->will($this->returnValue($orderService));

        $mailService->expects($this->once())
            ->method('getEmailsToSend')
            ->will($this->returnValue(array(
                $emailToSend
            )));

        $emailToSend->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(12));

        $emailToSend->expects($this->exactly(2))
            ->method('getType')
            ->will($this->returnValue('order_completed'));

        $emailToSend->expects($this->exactly(2))
            ->method('getOrder')
            ->will($this->returnValue($order));

        $order->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1515));

        $orderService->expects($this->once())
            ->method('setOrder')
            ->with($order);

        $orderService->expects($this->once())
            ->method('sendCompletedMail');

        $mailService->expects($this->once())
            ->method('markEmailSent')
            ->with($emailToSend);

        $logger->expects($this->once())
            ->method('alert');

        $container->expects($this->at(3))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $connection->expects($this->once())
            ->method('close');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/1 unsent emails found/', $commandTester->getDisplay());
        $this->assertRegExp('/Sending message id: 12 of type: "order_completed" for order id: 1515/', $commandTester->getDisplay());
        $this->assertRegExp('/1 emails sent in [0-9\.]{1,5}/', $commandTester->getDisplay());
    }

    public function testPartialyCompleteEmailSend()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $mailService = $this->getMockBuilder('\Food\AppBundle\Service\MailService')
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $orderService = $this->getMockBuilder('\Food\OrderBundle\Service\OrderService')
            ->disableOriginalConstructor()
            ->getMock();

        $emailToSend = $this->getMockBuilder('\Food\AppBundle\Entity\EmailToSend')
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMockBuilder('\Food\OrderBundle\Entity\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new SendEmailCommand());

        /**
         * @var SendEmailCommand $command
         */
        $command = $application->find('email:send');
        $command->setContainer($container);
        $command->setMaxChecks(1);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.mail')
            ->will($this->returnValue($mailService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $container->expects($this->at(2))
            ->method('get')
            ->with('food.order')
            ->will($this->returnValue($orderService));

        $mailService->expects($this->once())
            ->method('getEmailsToSend')
            ->will($this->returnValue(array(
                $emailToSend
            )));

        $emailToSend->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(16));

        $emailToSend->expects($this->exactly(2))
            ->method('getType')
            ->will($this->returnValue('order_partialy_completed'));

        $emailToSend->expects($this->exactly(2))
            ->method('getOrder')
            ->will($this->returnValue($order));

        $order->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(222));

        $orderService->expects($this->once())
            ->method('setOrder')
            ->with($order);

        $orderService->expects($this->once())
            ->method('sendCompletedMail')
            ->with(true);

        $mailService->expects($this->once())
            ->method('markEmailSent')
            ->with($emailToSend);

        $logger->expects($this->once())
            ->method('alert');

        $container->expects($this->at(3))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $connection->expects($this->once())
            ->method('close');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/1 unsent emails found/', $commandTester->getDisplay());
        $this->assertRegExp('/Sending message id: 16 of type: "order_partialy_completed" for order id: 222/', $commandTester->getDisplay());
        $this->assertRegExp('/1 emails sent in [0-9\.]{1,5}/', $commandTester->getDisplay());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage iAreInvalidError
     */
    public function testInvalidArgumentException()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $mailService = $this->getMockBuilder('\Food\AppBundle\Service\MailService')
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $orderService = $this->getMockBuilder('\Food\OrderBundle\Service\OrderService')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new SendEmailCommand());

        /**
         * @var SendEmailCommand $command
         */
        $command = $application->find('email:send');
        $command->setContainer($container);
        $command->setMaxChecks(1);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.mail')
            ->will($this->returnValue($mailService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $container->expects($this->at(2))
            ->method('get')
            ->with('food.order')
            ->will($this->returnValue($orderService));

        $mailService->expects($this->once())
            ->method('getEmailsToSend')
            ->will($this->throwException(new \InvalidArgumentException('iAreInvalidError')));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/Sorry, lazy programmer left a bug/', $commandTester->getDisplay());
        $this->assertRegExp('/Error: iAreInvalidError/', $commandTester->getDisplay());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage criticalError
     */
    public function testMajorException()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $mailService = $this->getMockBuilder('\Food\AppBundle\Service\MailService')
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $orderService = $this->getMockBuilder('\Food\OrderBundle\Service\OrderService')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new SendEmailCommand());

        /**
         * @var SendEmailCommand $command
         */
        $command = $application->find('email:send');
        $command->setContainer($container);
        $command->setMaxChecks(1);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.mail')
            ->will($this->returnValue($mailService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $container->expects($this->at(2))
            ->method('get')
            ->with('food.order')
            ->will($this->returnValue($orderService));

        $mailService->expects($this->once())
            ->method('getEmailsToSend')
            ->will($this->throwException(new \Exception('criticalError')));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/Mayday mayday, an error knocked the process down/', $commandTester->getDisplay());
        $this->assertRegExp('/Error: criticalError/', $commandTester->getDisplay());
    }
}