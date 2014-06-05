<?php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Food\SmsBundle\Command\CheckUndeliveredMessagesCommand;

class CheckUndeliveredMessagesCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testNoUndeliveredMessages()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $messagingService = $this->getMockBuilder('\Food\SmsBundle\Service\MessagesService')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new CheckUndeliveredMessagesCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:check:undelivered');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $messagingService->expects($this->once())
            ->method('getUndeliveredMessagesForRange')
            ->with($this->isInstanceOf('\DateTime'), $this->isInstanceOf('\DateTime'))
            ->will($this->returnValue(array()));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/OK: all messages delivered to client/', $commandTester->getDisplay());
    }

    /**
     * @depends testNoUndeliveredMessages
     * @expectedException \Exception
     */
    public function testExceptionHappenedSoundTheAlarm()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $messagingService = $this->getMockBuilder('\Food\SmsBundle\Service\MessagesService')
            ->disableOriginalConstructor()
            ->getMock();
        $mailer = $this->getMockBuilder('\Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new CheckUndeliveredMessagesCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:check:undelivered');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $messagingService->expects($this->once())
            ->method('getUndeliveredMessagesForRange')
            ->with($this->isInstanceOf('\DateTime'), $this->isInstanceOf('\DateTime'))
            ->will($this->throwException(new \Exception('I failed')));

        $container->expects($this->at(1))
            ->method('getParameter')
            ->with('domain')
            ->will($this->returnValue('foodout'));

        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('admin.emails')
            ->will($this->returnValue(array()));

        $container->expects($this->at(3))
            ->method('get')
            ->with('mailer')
            ->will($this->returnValue($mailer));

        $container->expects($this->at(4))
            ->method('getParameter')
            ->with('admin.send_monitoring_message')
            ->will($this->returnValue(false));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/Error in undelivered messages check: I failed/', $commandTester->getDisplay());
    }

    /**
     * @depends testExceptionHappenedSoundTheAlarm
     */
    public function testFoundUnsentSoundTheAlarm()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $messagingService = $this->getMockBuilder('\Food\SmsBundle\Service\MessagesService')
            ->disableOriginalConstructor()
            ->getMock();
        $mailer = $this->getMockBuilder('\Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();
        $infobipProvider = $this->getMockBuilder('\Food\SmsBundle\Service\InfobipProvider')
            ->disableOriginalConstructor()
            ->getMock();

        // Testable vars
        $email = 'support@niamniamas.info';
        $emails = array($email);
        $phone = '37060000000';
        $phones = array($phone);
        $sendMessages = true;
        $sender = 'niamniamas.info monitoring';
        $errorMessage = 'ERROR: 1 undelivered messages!';
        $smsMessage = new \Food\SmsBundle\Entity\Message();
        $smsMessage->setSender($sender);
        $smsMessage->setRecipient($phone);
        $smsMessage->setMessage($errorMessage);
        $messages = array($smsMessage);

        $application = new Application();
        $application->add(new CheckUndeliveredMessagesCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:check:undelivered');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $messagingService->expects($this->once())
            ->method('getUndeliveredMessagesForRange')
            ->with($this->isInstanceOf('\DateTime'), $this->isInstanceOf('\DateTime'))
            ->will($this->returnValue($messages));

        $container->expects($this->at(1))
            ->method('getParameter')
            ->with('domain')
            ->will($this->returnValue('foodout'));

        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('admin.emails')
            ->will($this->returnValue($emails));

        $container->expects($this->at(3))
            ->method('get')
            ->with('mailer')
            ->will($this->returnValue($mailer));

        $container->expects($this->at(4))
            ->method('getParameter')
            ->with('admin.send_monitoring_message')
            ->will($this->returnValue($sendMessages));

        $container->expects($this->at(5))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $container->expects($this->at(6))
            ->method('get')
            ->with('food.infobip')
            ->will($this->returnValue($infobipProvider));

        $messagingService->expects($this->once())
            ->method('setMessagingProvider')
            ->with($infobipProvider);

        $container->expects($this->at(7))
            ->method('getParameter')
            ->with('admin.phones')
            ->will($this->returnValue($phones));

        $container->expects($this->at(8))
            ->method('getParameter')
            ->with('sms.sender')
            ->will($this->returnValue($sender));

        $mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf('\Swift_Mime_MimePart'));

        $messagingService->expects($this->once())
            ->method('createMessage')
            ->with($sender, $phone, $errorMessage)
            ->will($this->returnValue($smsMessage));

        $messagingService->expects($this->once())
            ->method('sendMessage')
            ->with($smsMessage);

        $messagingService->expects($this->once())
            ->method('saveMessage')
            ->with($smsMessage);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/'.$errorMessage.'/', $commandTester->getDisplay());
    }
}