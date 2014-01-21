<?php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Food\SmsBundle\Command\SendCommand;

class SendCommandTest extends \PHPUnit_Framework_TestCase
{
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

        $container->expects($this->once())
            ->method('getParameter')
            ->with('available_sms_providers')
            ->will($this->returnValue(array()));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/No messaging providers configured. Please check Your configuration!/', $commandTester->getDisplay());
    }

    public function testTooMuchSmsProviders()
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

        $container->expects($this->once())
            ->method('getParameter')
            ->with('available_sms_providers')
            ->will($this->returnValue(array('infobip', 'gsms')));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/Sorry, at the moment we dont support more than one provider!/', $commandTester->getDisplay());
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

        $unsentMessages = array();

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
            ->method('get')
            ->with('food.infobip')
            ->will($this->returnValue($infobipProvider));

        $container->expects($this->once())
            ->method('getParameter')
            ->with('available_sms_providers')
            ->will($this->returnValue(array('food.infobip')));

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

        $unsentMessages = array();

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
            ->method('get')
            ->with('food.infobip')
            ->will($this->returnValue($infobipProvider));

        $container->expects($this->once())
            ->method('getParameter')
            ->with('available_sms_providers')
            ->will($this->returnValue(array('food.infobip')));

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

        $message = $this->getMockBuilder('\Food\SmsBundle\Entity\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $unsentMessages = array($message);

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
            ->method('get')
            ->with('food.infobip')
            ->will($this->returnValue($infobipProvider));

        $container->expects($this->once())
            ->method('getParameter')
            ->with('available_sms_providers')
            ->will($this->returnValue(array('food.infobip')));

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

        $message = $this->getMockBuilder('\Food\SmsBundle\Entity\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $unsentMessages = array($message, $message);

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
            ->method('get')
            ->with('food.infobip')
            ->will($this->returnValue($infobipProvider));

        $container->expects($this->once())
            ->method('getParameter')
            ->with('available_sms_providers')
            ->will($this->returnValue(array('food.infobip')));

        $infobipProvider->expects($this->never())
            ->method('setDebugEnabled');

        $messagingService->expects($this->once())
            ->method('setMessagingProvider')
            ->with($infobipProvider);

        $messagingService->expects($this->once())
            ->method('getUnsentMessages')
            ->will($this->returnValue($unsentMessages));

        $message->expects($this->at(0))
            ->method('getId')
            ->will($this->returnValue(5));

        $message->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(9));

        $messagingService->expects($this->exactly(2))
            ->method('sendMessage')
            ->with($message);

        $messagingService->expects($this->exactly(2))
            ->method('saveMessage')
            ->with($message);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/2 unsent messages found./', $commandTester->getDisplay());
        $this->assertRegExp('/Sending message id: 5/', $commandTester->getDisplay());
        $this->assertRegExp('/Sending message id: 9/', $commandTester->getDisplay());
        $this->assertRegExp('/2 messages sent/', $commandTester->getDisplay());
    }

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

        $message = $this->getMockBuilder('\Food\SmsBundle\Entity\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $unsentMessages = array($message);

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
            ->method('get')
            ->with('food.infobip')
            ->will($this->returnValue($infobipProvider));

        $container->expects($this->once())
            ->method('getParameter')
            ->with('available_sms_providers')
            ->will($this->returnValue(array('food.infobip')));

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

        $message = $this->getMockBuilder('\Food\SmsBundle\Entity\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $unsentMessages = array($message);

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
            ->method('get')
            ->with('food.infobip')
            ->will($this->returnValue($infobipProvider));

        $container->expects($this->once())
            ->method('getParameter')
            ->with('available_sms_providers')
            ->will($this->returnValue(array('food.infobip')));

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