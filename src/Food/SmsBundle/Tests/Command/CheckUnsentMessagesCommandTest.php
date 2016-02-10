<?php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Food\SmsBundle\Command\CheckUnsentMessagesCommand;

class CheckUnsentMessagesCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testNoUnsentMessages()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );
        $messagingService = $this->getMockBuilder('\Food\SmsBundle\Service\MessagesService')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new CheckUnsentMessagesCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:check:unsent');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $messagingService->expects($this->once())
            ->method('getUnsentMessagesForRange')
            ->with($this->isInstanceOf('\DateTime'), $this->isInstanceOf('\DateTime'))
            ->will($this->returnValue(array()));

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
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/OK: all messages sent. Have a nice day/', $commandTester->getDisplay());
    }

    /**
     * @depends testNoUnsentMessages
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

        $application = new Application();
        $application->add(new CheckUnsentMessagesCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:check:unsent');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $messagingService->expects($this->once())
            ->method('getUnsentMessagesForRange')
            ->with($this->isInstanceOf('\DateTime'), $this->isInstanceOf('\DateTime'))
            ->will($this->throwException(new \Exception('I failed')));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/Error in unsent messages check: I failed/', $commandTester->getDisplay());
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

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        // Testable vars
        $phone = '37060000000';
        $sender = 'niamniamas.info monitoring';
        $errorMessage = 'ERROR: 1 unsent messages!';
        $smsMessage = new \Food\SmsBundle\Entity\Message();
        $smsMessage->setSender($sender);
        $smsMessage->setRecipient($phone);
        $smsMessage->setMessage($errorMessage);
        $messages = array($smsMessage);

        $application = new Application();
        $application->add(new CheckUnsentMessagesCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:check:unsent');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $messagingService->expects($this->once())
            ->method('getUnsentMessagesForRange')
            ->with($this->isInstanceOf('\DateTime'), $this->isInstanceOf('\DateTime'))
            ->will($this->returnValue($messages));

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
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertRegExp('/'.$errorMessage.'/', $commandTester->getDisplay());
        $this->assertEquals(2, $commandTester->getStatusCode());
    }
}