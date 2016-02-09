<?php
namespace Food\OrderBundle\Tests\Service;

use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Connection;
use Food\AppBundle\Test\WebTestCase;
use Food\OrderBundle\Service\DailyReport;
use Food\AppBundle\Service\GoogleAnalyticsService;

class DailyReportTest extends WebTestCase
{
    public function testTemplatingSetterAndGetter()
    {
        $dailyReport = new DailyReport();

        $param = new \StdClass();

        $setterResult = $dailyReport->setTemplating($param);
        $getterResult = $dailyReport->getTemplating();

        $this->assertSame($setterResult, $dailyReport);
        $this->assertSame($getterResult, $param);
    }

    public function testGoogleAnalyticsServiceSetterAndGetter()
    {
        $dailyReport = new DailyReport();

        $service = new GoogleAnalyticsService();

        $setterResult = $dailyReport->setGoogleAnalyticsService($service);
        $getterResult = $dailyReport->getGoogleAnalyticsService();

        $this->assertSame($setterResult, $dailyReport);
        $this->assertSame($getterResult, $service);
    }

    public function testOutputSetterAndGetter()
    {
        $dailyReport = new DailyReport();

        $output = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')
                       ->disableOriginalConstructor()
                       ->getMock();

        $setterResult = $dailyReport->setOutput($output);
        $getterResult = $dailyReport->getOutput();

        $this->assertSame($setterResult, $dailyReport);
        $this->assertSame($getterResult, $output);
    }

    public function testDailyReportEmailsSetterAndGetter()
    {
        $dailyReport = new DailyReport();

        $emails = ['one', 'two', 'three'];

        $setterResult = $dailyReport->setDailyReportEmails($emails);
        $getterResult = $dailyReport->getDailyReportEmails();

        $this->assertSame($setterResult, $dailyReport);
        $this->assertSame($getterResult, $emails);
    }

    public function testConnectionSetterAndGetter()
    {
        $dailyReport = new DailyReport();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
                           ->disableOriginalConstructor()
                           ->getMock();

        $setterResult = $dailyReport->setConnection($connection);
        $getterResult = $dailyReport->getConnection();

        $this->assertSame($setterResult, $dailyReport);
        $this->assertSame($getterResult, $connection);
    }

    public function testGetDailyReportQuery()
    {
        $dailyReport = new DailyReport();

        $result1 = $dailyReport->getDailyReportQuery('income');
        $result2 = $dailyReport->getDailyReportQuery('successful_orders');
        $result3 = $dailyReport->getDailyReportQuery('average_cart');

        $this->assertInternalType('string', $result1);
        $this->assertInternalType('string', $result2);
        $this->assertInternalType('string', $result3);
    }

    public function testGetDailyDeliveryTimesByRegion()
    {
        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
                           ->disableOriginalConstructor()
                           ->setMethods(['prepare'])
                           ->getMock();

        $stmt = $this->getMockBuilder('\Doctrine\DBAL\Driver\Mysqli\Statement')
                     ->disableOriginalConstructor()
                     ->setMethods(['execute', 'fetchAll'])
                     ->getMock();

        $connection->expects($this->once())
                   ->method('prepare')
                   ->willReturn($stmt);

        $stmt->expects($this->once())
             ->method('fetchAll')
             ->willReturn([123, 456]);

        $dailyReport = new DailyReport();
        $dailyReport->setConnection($connection);

        $result = $dailyReport->getDailyDeliveryTimesByRegion();

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
    }

    public function testGetDailyDeliveryTime()
    {
        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
                           ->disableOriginalConstructor()
                           ->setMethods(['prepare'])
                           ->getMock();

        $stmt = $this->getMockBuilder('\Doctrine\DBAL\Driver\Mysqli\Statement')
                     ->disableOriginalConstructor()
                     ->setMethods(['execute', 'fetch'])
                     ->getMock();

        $connection->expects($this->once())
                   ->method('prepare')
                   ->willReturn($stmt);

        $stmt->expects($this->once())
             ->method('fetch')
             ->willReturn(['result' => [123, 456]]);

        $dailyReport = new DailyReport();
        $dailyReport->setConnection($connection);

        $result = $dailyReport->getDailyDeliveryTime();

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
    }

    public function testGetDailyMailTitle()
    {
        $dailyReport = new DailyReport();
        $dailyReport->setContainer($this->getContainer());

        $result = $dailyReport->getDailyMailTitle();

        $this->assertInternalType('string', $result);
    }

    public function testGetDailyMailContent()
    {
        $templating = $this->getMockBuilder('\StdClass')
                           ->setMethods(['render'])
                           ->getMock();

        $templating->expects($this->once())
                   ->method('render')
                   ->willReturn('1234');

        $dailyReport = new DailyReport();
        $dailyReport->setTemplating($templating);

        $params = new \StdClass();
        $params->name = '12345';

        $result = $dailyReport->getDailyMailContent($params);

        $this->assertInternalType('string', $result);
    }

    public function testGetCalculations()
    {
        $gaService = $this->getMockBuilder('\Food\AppBundle\Service\GoogleAnalyticsService')
                          ->setMethods(['getUsers', 'getReturningUsers'])
                          ->getMock();

        $dailyReport = $this->getMockBuilder('\Food\OrderBundle\Service\DailyReport')
                            ->setMethods(['getDailyDataFor',
                                          'getDailyDeliveryTime',
                                          'getDailyDeliveryTimesByRegion'])
                            ->getMock();
        $dailyReport->setGoogleAnalyticsService($gaService);

        $gaService->expects($this->once())
                  ->method('getUsers')
                  ->willReturn(123);

        $gaService->expects($this->once())
                  ->method('getReturningUsers')
                  ->willReturn(456);

        $dailyReport->expects($this->exactly(3))
                    ->method('getDailyDataFor')
                    ->willReturn(123);

        $dailyReport->expects($this->once())
                    ->method('getDailyDeliveryTime')
                    ->willReturn(456);

        $dailyReport->expects($this->once())
                    ->method('getDailyDeliveryTimesByRegion')
                    ->willReturn(789);

        $result = $dailyReport->getCalculations();

        $this->assertInstanceOf('\StdClass', $result);
    }

    public function testGetDailyDataFor()
    {
        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
                           ->disableOriginalConstructor()
                           ->setMethods(['prepare'])
                           ->getMock();

        $stmt = $this->getMockBuilder('\Doctrine\DBAL\Driver\Mysqli\Statement')
                     ->disableOriginalConstructor()
                     ->setMethods(['execute', 'fetch'])
                     ->getMock();

        $connection->expects($this->any())
                   ->method('prepare')
                   ->willReturn($stmt);

        $stmt->expects($this->any())
             ->method('fetch')
             ->willReturn(['result' => '123']);

        $dailyReport = $this->getMockBuilder('\Food\OrderBundle\Service\DailyReport')
                            ->setMethods(['getDailyReportQuery'])
                            ->getMock();
        $dailyReport->setConnection($connection);

        $dailyReport->expects($this->any())
                    ->method('getDailyReportQuery')
                    ->willReturn('some string');

        $result1 = $dailyReport->getDailyDataFor('income');
        $result2 = $dailyReport->getDailyDataFor('successful_orders');
        $result3 = $dailyReport->getDailyDataFor('average_cart');

        $this->assertInternalType('string', $result1);
        $this->assertInternalType('string', $result2);
        $this->assertInternalType('string', $result3);
    }

    public function testSendDailyMails()
    {
        $dailyReport = new DailyReport();

        $result = $dailyReport->sendDailyMails('email', [1, 2, 3], 'title', 'content');

        $this->assertInternalType('array', $result);
    }

    public function testSendDailyReport()
    {
        $output = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')
                       ->setMethods(['writeln'])
                       ->getMockForAbstractClass();

        $dailyReport = $this->getMockBuilder('\Food\OrderBundle\Service\DailyReport')
                            ->setMethods(['getCalculations',
                                          'getDailyMailContent',
                                          'getDailyMailTitle',
                                          'sendDailyMails'])
                            ->getMock();
        $dailyReport->setOutput($output);

        $dailyReport->expects($this->any())
                    ->method('getCalculations')
                    ->willReturn(new \StdClass());

        $dailyReport->expects($this->any())
                    ->method('getDailyMailContent')
                    ->willReturn('123');

        $dailyReport->expects($this->any())
                    ->method('getDailyMailTitle')
                    ->willReturn('4321231');

        $dailyReport->expects($this->any())
                    ->method('sendDailyMails')
                    ->willReturn(['some array']);

        $result1 = $dailyReport->sendDailyReport('email', false);
        $result2 = $dailyReport->sendDailyReport('email', true);

        $this->assertInternalType('array', $result1);
        $this->assertInternalType('array', $result2);
    }
}
