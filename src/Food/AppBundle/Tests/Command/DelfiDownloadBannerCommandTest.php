<?php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Food\AppBundle\Command\DelfiBannerDownloadCommand;

class DelfiDownloadBannerCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testDownload()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );
        $miscService = $this->getMockBuilder('\Food\AppBundle\Utils\Misc')
            ->disableOriginalConstructor()
            ->getMock();

        $curl = $this->getMockBuilder('Curl')
            ->disableOriginalConstructor()
            ->getMock();

        $firstResponse = $this->getMockBuilder('CurlResponse')
            ->disableOriginalConstructor()
            ->getMock();

        $secondResponse = $this->getMockBuilder('CurlResponse')
            ->disableOriginalConstructor()
            ->getMock();

        $firstResponseBody = 'jsScriptHere';
        $secondResponseBody = 'bannerCodeHere';

        $firstResponse->body = $firstResponseBody;
        $secondResponse->body = $secondResponseBody;

        $application = new Application();
        $application->add(new DelfiBannerDownloadCommand());

        /**
         * @var DelfiBannerDownloadCommand $command
         */
        $command = $application->find('delfi:banner:download');
        $command->setContainer($container);
        $command->setCli($curl);

        $container->expects($this->once())
            ->method('get')
            ->with('food.app.utils.misc')
            ->will($this->returnValue($miscService));

        $curl->expects($this->at(0))
            ->method('get')
            ->with('http://www.1000receptu.lt/misc/export/header_v2.php?e=meta&p=foodout.1000receptu.lt')
            ->will($this->returnValue($firstResponse));

        $curl->expects($this->at(1))
            ->method('get')
            ->with('http://www.1000receptu.lt/misc/export/header_v2.php?e=header&p=foodout.1000receptu.lt&hide-ads=1')
            ->will($this->returnValue($secondResponse));

        $miscService->expects($this->at(0))
            ->method('setParam')
            ->with('delfiJs', $firstResponseBody);

        $miscService->expects($this->at(1))
            ->method('setParam')
            ->with('delfiBanner', $secondResponseBody);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage ajaj kai klaida
     */
    public function testException()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );
        $miscService = $this->getMockBuilder('\Food\AppBundle\Utils\Misc')
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $curl = $this->getMockBuilder('Curl')
            ->disableOriginalConstructor()
            ->getMock();

        $firstResponse = $this->getMockBuilder('CurlResponse')
            ->disableOriginalConstructor()
            ->getMock();

        $secondResponse = $this->getMockBuilder('CurlResponse')
            ->disableOriginalConstructor()
            ->getMock();

        $firstResponseBody = 'jsScriptHere';
        $secondResponseBody = 'bannerCodeHere';

        $firstResponse->body = $firstResponseBody;
        $secondResponse->body = $secondResponseBody;

        $application = new Application();
        $application->add(new DelfiBannerDownloadCommand());

        /**
         * @var DelfiBannerDownloadCommand $command
         */
        $command = $application->find('delfi:banner:download');
        $command->setContainer($container);
        $command->setCli($curl);

        $container->expects($this->at(0))
            ->method('get')
            ->with('food.app.utils.misc')
            ->will($this->returnValue($miscService));

        $curl->expects($this->at(0))
            ->method('get')
            ->with('http://www.1000receptu.lt/misc/export/header_v2.php?e=meta&p=foodout.1000receptu.lt')
            ->will($this->returnValue($firstResponse));

        $curl->expects($this->at(1))
            ->method('get')
            ->with('http://www.1000receptu.lt/misc/export/header_v2.php?e=header&p=foodout.1000receptu.lt&hide-ads=1')
            ->will($this->returnValue($secondResponse));

        $miscService->expects($this->at(0))
            ->method('setParam')
            ->with('delfiJs', $firstResponseBody);

        $miscService->expects($this->at(1))
            ->method('setParam')
            ->with('delfiBanner', $secondResponseBody)
            ->will($this->throwException(new \Exception('ajaj kai klaida')));

        $container->expects($this->at(1))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $logger->expects($this->once())
            ->method('error')
            ->with('Omg, Delfi banner save failed, what will we do??? Error: '.'ajaj kai klaida');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array('command' => $command->getName())
        );
    }
}