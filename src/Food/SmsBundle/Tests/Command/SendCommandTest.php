<?php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Food\SmsBundle\Command\SendCommand;

class SendCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage No messaging providers configured. Please check Your configuration!
     */
    public function testNoSmsProviders()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $messagingService = $this->getMockBuilder('\Food\SmsBundle\Service\MessagesService')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new SendCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:send');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('sms.available_providers')
            ->will($this->returnValue(array()));

        $container->expects($this->at(3))
            ->method('getParameter')
            ->with('sms.main_provider')
            ->will($this->returnValue(array()));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/No messaging providers configured. Please check Your configuration!/', $commandTester->getDisplay());
    }

    public function testNoMessagesToSend()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $messagingService = $this->getMockBuilder('\Food\SmsBundle\Service\MessagesService')
            ->disableOriginalConstructor()
            ->getMock();
        $infobipProvider = $this->getMock(
            '\Food\SmsBundle\Service\InfobipProvider',
            array('setDebugEnabled')
        );

        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $unsentMessages = array();

        $sendCommand = new SendCommand();
        $sendCommand->setMaxChecks(1);
        $application = new Application();
        $application->add($sendCommand);

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:send');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('sms.available_providers')
            ->will($this->returnValue(array('food.infobip', 'food.silverstreet')));

        $container->expects($this->at(3))
            ->method('getParameter')
            ->with('sms.main_provider')
            ->will($this->returnValue('food.infobip'));

        $container->expects($this->at(4))
            ->method('get')
            ->with('food.infobip')
            ->will($this->returnValue($infobipProvider));

        $infobipProvider->expects($this->never())
            ->method('setDebugEnabled');

        $messagingService->expects($this->once())
            ->method('setMessagingProvider')
            ->with($infobipProvider);

        $messagingService->expects($this->once())
            ->method('getUnsentMessages')
            ->will($this->returnValue($unsentMessages));

        $messagingService->expects($this->never())
            ->method('sendMessage');

        $messagingService->expects($this->never())
            ->method('saveMessage');

        $logger->expects($this->once())
            ->method('alert');

        $container->expects($this->at(5))
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

        $this->assertRegExp('/0 messages sent/', $commandTester->getDisplay());
    }

    public function testShowDebug()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $messagingService = $this->getMockBuilder('\Food\SmsBundle\Service\MessagesService')
            ->disableOriginalConstructor()
            ->getMock();
        $infobipProvider = $this->getMock(
            '\Food\SmsBundle\Service\InfobipProvider',
            array('setDebugEnabled')
        );
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $unsentMessages = array();

        $sendCommand = new SendCommand();
        $sendCommand->setMaxChecks(1);
        $application = new Application();
        $application->add($sendCommand);

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:send');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('sms.available_providers')
            ->will($this->returnValue(array('food.infobip', 'food.silverstreet')));

        $container->expects($this->at(3))
            ->method('getParameter')
            ->with('sms.main_provider')
            ->will($this->returnValue('food.infobip'));

        $container->expects($this->at(4))
            ->method('get')
            ->with('food.infobip')
            ->will($this->returnValue($infobipProvider));

        $infobipProvider->expects($this->once())
            ->method('setDebugEnabled')
            ->with(true);

        $messagingService->expects($this->once())
            ->method('setMessagingProvider')
            ->with($infobipProvider);

        $messagingService->expects($this->once())
            ->method('getUnsentMessages')
            ->will($this->returnValue($unsentMessages));

        $messagingService->expects($this->never())
            ->method('sendMessage');

        $messagingService->expects($this->never())
            ->method('saveMessage');

        $logger->expects($this->once())
            ->method('alert');

        $container->expects($this->at(5))
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
            array('command' => $command->getName(), '--debug' => true)
        );

        $this->assertRegExp('/0 unsent messages found./', $commandTester->getDisplay());
        $this->assertRegExp('/0 messages sent/', $commandTester->getDisplay());
    }

    public function testOneMessagesToSend()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $messagingService = $this->getMockBuilder('\Food\SmsBundle\Service\MessagesService')
            ->disableOriginalConstructor()
            ->getMock();
        $infobipProvider = $this->getMock(
            '\Food\SmsBundle\Service\InfobipProvider',
            array('setDebugEnabled')
        );
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->getMockBuilder('\Food\SmsBundle\Entity\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $unsentMessages = array($message);

        $sendCommand = new SendCommand();
        $sendCommand->setMaxChecks(1);
        $application = new Application();
        $application->add($sendCommand);

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:send');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('sms.available_providers')
            ->will($this->returnValue(array('food.infobip', 'food.silverstreet')));

        $container->expects($this->at(3))
            ->method('getParameter')
            ->with('sms.main_provider')
            ->will($this->returnValue('food.infobip'));

        $container->expects($this->at(4))
            ->method('get')
            ->with('food.infobip')
            ->will($this->returnValue($infobipProvider));

        $infobipProvider->expects($this->never())
            ->method('setDebugEnabled');

        $messagingService->expects($this->once())
            ->method('setMessagingProvider')
            ->with($infobipProvider);

        $messagingService->expects($this->once())
            ->method('getUnsentMessages')
            ->will($this->returnValue($unsentMessages));

        $message->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(4));

        $messagingService->expects($this->once())
            ->method('sendMessage')
            ->with($message);

        $messagingService->expects($this->once())
            ->method('saveMessage')
            ->with($message);

        $logger->expects($this->once())
            ->method('alert');

        $container->expects($this->at(5))
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

        $this->assertRegExp('/1 unsent messages found./', $commandTester->getDisplay());
        $this->assertRegExp('/Sending message id: 4/', $commandTester->getDisplay());
        $this->assertRegExp('/1 messages sent/', $commandTester->getDisplay());
    }

    public function testTwoMessagesToSend()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $messagingService = $this->getMockBuilder('\Food\SmsBundle\Service\MessagesService')
            ->disableOriginalConstructor()
            ->getMock();
        $infobipProvider = $this->getMock(
            '\Food\SmsBundle\Service\InfobipProvider',
            array('setDebugEnabled')
        );
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->getMockBuilder('\Food\SmsBundle\Entity\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $unsentMessages = array($message, $message);

        $sendCommand = new SendCommand();
        $sendCommand->setMaxChecks(1);
        $application = new Application();
        $application->add($sendCommand);

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:send');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));


        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('sms.available_providers')
            ->will($this->returnValue(array('food.infobip', 'food.silverstreet')));

        $container->expects($this->at(3))
            ->method('getParameter')
            ->with('sms.main_provider')
            ->will($this->returnValue('food.infobip'));

        $container->expects($this->at(4))
            ->method('get')
            ->with('food.infobip')
            ->will($this->returnValue($infobipProvider));

        $infobipProvider->expects($this->never())
            ->method('setDebugEnabled');

        $messagingService->expects($this->once())
            ->method('setMessagingProvider')
            ->with($infobipProvider);

        $messagingService->expects($this->once())
            ->method('getUnsentMessages')
            ->will($this->returnValue($unsentMessages));

        $message->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(5));

        $message->expects($this->at(3))
            ->method('getId')
            ->will($this->returnValue(9));

        $messagingService->expects($this->exactly(2))
            ->method('sendMessage')
            ->with($message);

        $messagingService->expects($this->exactly(2))
            ->method('saveMessage')
            ->with($message);

        $logger->expects($this->once())
            ->method('alert');

        $container->expects($this->at(5))
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

        $this->assertRegExp('/2 unsent messages found./', $commandTester->getDisplay());
        $this->assertRegExp('/Sending message id: 5/', $commandTester->getDisplay());
        $this->assertRegExp('/Sending message id: 9/', $commandTester->getDisplay());
        $this->assertRegExp('/2 messages sent/', $commandTester->getDisplay());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Omg test hack
     */
    public function testMessagesSendException()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $messagingService = $this->getMockBuilder('\Food\SmsBundle\Service\MessagesService')
            ->disableOriginalConstructor()
            ->getMock();
        $infobipProvider = $this->getMock(
            '\Food\SmsBundle\Service\InfobipProvider',
            array('setDebugEnabled')
        );
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->getMockBuilder('\Food\SmsBundle\Entity\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $unsentMessages = array($message);

        $sendCommand = new SendCommand();
        $sendCommand->setMaxChecks(1);
        $application = new Application();
        $application->add($sendCommand);

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:send');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));


        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('sms.available_providers')
            ->will($this->returnValue(array('food.infobip', 'food.silverstreet')));

        $container->expects($this->at(3))
            ->method('getParameter')
            ->with('sms.main_provider')
            ->will($this->returnValue('food.infobip'));

        $container->expects($this->at(4))
            ->method('get')
            ->with('food.infobip')
            ->will($this->returnValue($infobipProvider));

        $infobipProvider->expects($this->never())
            ->method('setDebugEnabled');

        $messagingService->expects($this->once())
            ->method('setMessagingProvider')
            ->with($infobipProvider);

        $messagingService->expects($this->once())
            ->method('getUnsentMessages')
            ->will($this->returnValue($unsentMessages));

        $message->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(4));

        $messagingService->expects($this->once())
            ->method('sendMessage')
            ->with($message)
            ->will($this->throwException(new \Exception('Omg test hack')));

        $messagingService->expects($this->never())
            ->method('saveMessage');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/1 unsent messages found./', $commandTester->getDisplay());
        $this->assertRegExp('/Sending message id: 4/', $commandTester->getDisplay());
        $this->assertRegExp('/Mayday mayday, an error knocked the process down./', $commandTester->getDisplay());
        $this->assertRegExp('/Error: Omg test hack/', $commandTester->getDisplay());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Zis iz not a message
     */
    public function testMessagesSendInvalidArgumentException()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $messagingService = $this->getMockBuilder('\Food\SmsBundle\Service\MessagesService')
            ->disableOriginalConstructor()
            ->getMock();
        $infobipProvider = $this->getMock(
            '\Food\SmsBundle\Service\InfobipProvider',
            array('setDebugEnabled')
        );
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->getMockBuilder('\Food\SmsBundle\Entity\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $unsentMessages = array($message);

        $sendCommand = new SendCommand();
        $sendCommand->setMaxChecks(1);
        $application = new Application();
        $application->add($sendCommand);

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:send');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $container->expects($this->at(1))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));


        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('sms.available_providers')
            ->will($this->returnValue(array('food.infobip', 'food.silverstreet')));

        $container->expects($this->at(3))
            ->method('getParameter')
            ->with('sms.main_provider')
            ->will($this->returnValue('food.infobip'));

        $container->expects($this->at(4))
            ->method('get')
            ->with('food.infobip')
            ->will($this->returnValue($infobipProvider));

        $infobipProvider->expects($this->never())
            ->method('setDebugEnabled');

        $messagingService->expects($this->once())
            ->method('setMessagingProvider')
            ->with($infobipProvider);

        $messagingService->expects($this->once())
            ->method('getUnsentMessages')
            ->will($this->returnValue($unsentMessages));

        $message->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(4));

        $messagingService->expects($this->once())
            ->method('sendMessage')
            ->with($message)
            ->will($this->throwException(new \InvalidArgumentException('Zis iz not a message')));

        $messagingService->expects($this->never())
            ->method('saveMessage');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/1 unsent messages found./', $commandTester->getDisplay());
        $this->assertRegExp('/Sending message id: 4/', $commandTester->getDisplay());
        $this->assertRegExp('/Sorry, lazy programmer left a bug/', $commandTester->getDisplay());
        $this->assertRegExp('/Error: Zis iz not a message/', $commandTester->getDisplay());
    }
}