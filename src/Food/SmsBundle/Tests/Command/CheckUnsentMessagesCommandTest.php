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

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );

//        $this->assertRegExp('/Hello/', $commandTester->getDisplay());
    }
}