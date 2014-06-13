<?php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Food\SmsBundle\Command\SendCommand;

class SmsBalanceCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testBalanceIsOk()
    {
        $expectedCommandReturn = 0;
        $warnLevel = 10;
        $critLevel = 5;
        $testableBalance = 12.63;

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

        $application = new Application();
        $application->add(new \Food\SmsBundle\Command\SmsBalanceCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:check:balance');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $container->expects($this->at(1))
            ->method('getParameter')
            ->with('sms.available_providers')
            ->will($this->returnValue(array('food.infobip')));
        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('sms.balance_limit_warn')
            ->will($this->returnValue($warnLevel));

        $container->expects($this->at(3))
            ->method('getParameter')
            ->with('sms.balance_limit_critical')
            ->will($this->returnValue($critLevel));

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
            ->method('getAccountBalance')
            ->will($this->returnValue($testableBalance));


        $commandTester = new CommandTester($command);
        $commandReturn = $commandTester->execute(
            array('command' => $command->getName())
        );

        $commandOutput = $commandTester->getDisplay();

        $this->assertRegExp('/all providers have enough of money/', $commandOutput);
        $this->assertRegExp('/InfoBip - '.$testableBalance.'/', $commandOutput);
        $this->assertEquals($expectedCommandReturn, $commandReturn);
    }

    public function testBalanceIsWarning()
    {
        $expectedCommandReturn = 1;
        $warnLevel = 10;
        $critLevel = 5;
        $testableBalance = 9.15;

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

        $application = new Application();
        $application->add(new \Food\SmsBundle\Command\SmsBalanceCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:check:balance');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $container->expects($this->at(1))
            ->method('getParameter')
            ->with('sms.available_providers')
            ->will($this->returnValue(array('food.infobip')));
        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('sms.balance_limit_warn')
            ->will($this->returnValue($warnLevel));

        $container->expects($this->at(3))
            ->method('getParameter')
            ->with('sms.balance_limit_critical')
            ->will($this->returnValue($critLevel));

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
            ->method('getAccountBalance')
            ->will($this->returnValue($testableBalance));


        $commandTester = new CommandTester($command);
        $commandReturn = $commandTester->execute(
            array('command' => $command->getName())
        );

        $commandOutput = $commandTester->getDisplay();

        $this->assertRegExp('/1 of providers soon will be low on balance/', $commandOutput);
        $this->assertRegExp('/InfoBip - '.$testableBalance.'/', $commandOutput);
        $this->assertEquals($expectedCommandReturn, $commandReturn);
    }

    public function testBalanceIsCritical()
    {
        $expectedCommandReturn = 2;
        $warnLevel = 10;
        $critLevel = 5;
        $testableBalance = 4.31;

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

        $application = new Application();
        $application->add(new \Food\SmsBundle\Command\SmsBalanceCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:check:balance');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $container->expects($this->at(1))
            ->method('getParameter')
            ->with('sms.available_providers')
            ->will($this->returnValue(array('food.infobip')));
        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('sms.balance_limit_warn')
            ->will($this->returnValue($warnLevel));

        $container->expects($this->at(3))
            ->method('getParameter')
            ->with('sms.balance_limit_critical')
            ->will($this->returnValue($critLevel));

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
            ->method('getAccountBalance')
            ->will($this->returnValue($testableBalance));


        $commandTester = new CommandTester($command);
        $commandReturn = $commandTester->execute(
            array('command' => $command->getName())
        );

        $commandOutput = $commandTester->getDisplay();

        $this->assertRegExp('/1 of providers has criticaly low balance/', $commandOutput);
        $this->assertRegExp('/InfoBip - '.$testableBalance.'/', $commandOutput);
        $this->assertEquals($expectedCommandReturn, $commandReturn);
    }

    public function testBalanceExceptionHappened()
    {
        $expectedCommandReturn = 2;
        $warnLevel = 10;
        $critLevel = 5;

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

        $application = new Application();
        $application->add(new \Food\SmsBundle\Command\SmsBalanceCommand());

        /**
         * @var SendCommand $command
         */
        $command = $application->find('sms:check:balance');
        $command->setContainer($container);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.messages')
            ->will($this->returnValue($messagingService));

        $container->expects($this->at(1))
            ->method('getParameter')
            ->with('sms.available_providers')
            ->will($this->returnValue(array('food.infobip')));
        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('sms.balance_limit_warn')
            ->will($this->returnValue($warnLevel));

        $container->expects($this->at(3))
            ->method('getParameter')
            ->with('sms.balance_limit_critical')
            ->will($this->returnValue($critLevel));

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
            ->method('getAccountBalance')
            ->will($this->throwException(new \Exception('I failed')));


        $commandTester = new CommandTester($command);
        $commandReturn = $commandTester->execute(
            array('command' => $command->getName())
        );

        $commandOutput = $commandTester->getDisplay();

        $this->assertRegExp('/1 of providers has criticaly low balance/', $commandOutput);
        $this->assertRegExp('/InfoBip \- ERROR\: I failed/', $commandOutput);
        $this->assertEquals($expectedCommandReturn, $commandReturn);
    }
}