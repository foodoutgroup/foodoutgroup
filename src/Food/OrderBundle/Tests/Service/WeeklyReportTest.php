<?php
namespace Food\OrderBundle\Tests\Service;

use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Connection;
use Food\AppBundle\Test\WebTestCase;
use Food\OrderBundle\Service\WeeklyReport;
use Food\AppBundle\Service\GoogleAnalyticsService;

class WeeklyReportTest extends WebTestCase
{
    public function testTemplatingSetterAndGetter()
    {
        $weeklyReport = new WeeklyReport();

        $param = new \StdClass();

        $setterResult = $weeklyReport->setTemplating($param);
        $getterResult = $weeklyReport->getTemplating();

        $this->assertSame($setterResult, $weeklyReport);
        $this->assertSame($getterResult, $param);
    }

    public function testGoogleAnalyticsServiceSetterAndGetter()
    {
        $weeklyReport = new WeeklyReport();

        $service = new GoogleAnalyticsService();

        $setterResult = $weeklyReport->setGoogleAnalyticsService($service);
        $getterResult = $weeklyReport->getGoogleAnalyticsService();

        $this->assertSame($setterResult, $weeklyReport);
        $this->assertSame($getterResult, $service);
    }

    public function testOutputSetterAndGetter()
    {
        $weeklyReport = new WeeklyReport();

        $output = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')
                       ->disableOriginalConstructor()
                       ->getMock();

        $setterResult = $weeklyReport->setOutput($output);
        $getterResult = $weeklyReport->getOutput();

        $this->assertSame($setterResult, $weeklyReport);
        $this->assertSame($getterResult, $output);
    }

    public function testWeeklyReportEmailsSetterAndGetter()
    {
        $weeklyReport = new WeeklyReport();

        $emails = ['one', 'two', 'three'];

        $setterResult = $weeklyReport->setWeeklyReportEmails($emails);
        $getterResult = $weeklyReport->getWeeklyReportEmails();

        $this->assertSame($setterResult, $weeklyReport);
        $this->assertSame($getterResult, $emails);
    }

    public function testConnectionSetterAndGetter()
    {
        $weeklyReport = new WeeklyReport();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
                           ->disableOriginalConstructor()
                           ->getMock();

        $setterResult = $weeklyReport->setConnection($connection);
        $getterResult = $weeklyReport->getConnection();

        $this->assertSame($setterResult, $weeklyReport);
        $this->assertSame($getterResult, $connection);
    }

    public function testGetWeeklyReportQuery()
    {
        $weeklyReport = new WeeklyReport();

        $result1 = $weeklyReport->getWeeklyReportQuery('income');
        $result2 = $weeklyReport->getWeeklyReportQuery('successful_orders');
        $result3 = $weeklyReport->getWeeklyReportQuery('average_cart');

        $this->assertInternalType('string', $result1);
        $this->assertInternalType('string', $result2);
        $this->assertInternalType('string', $result3);
    }

    public function testGetWeeklyDeliveryTimesByRegion()
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

        $weeklyReport = new WeeklyReport();
        $weeklyReport->setConnection($connection);

        $result = $weeklyReport->getWeeklyDeliveryTimesByRegion();

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
    }

    public function testGetWeeklyDeliveryTime()
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

        $weeklyReport = new WeeklyReport();
        $weeklyReport->setConnection($connection);

        $result = $weeklyReport->getWeeklyDeliveryTime();

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
    }

    public function testGetWeeklyMailTitle()
    {
        $weeklyReport = new WeeklyReport();
        $weeklyReport->setContainer($this->getContainer());

        $result = $weeklyReport->getWeeklyMailTitle();

        $this->assertInternalType('string', $result);
    }

    public function testGetWeeklyMailContent()
    {
        $templating = $this->getMockBuilder('\StdClass')
                           ->setMethods(['render'])
                           ->getMock();

        $templating->expects($this->once())
                   ->method('render')
                   ->willReturn('1234');

        $weeklyReport = new WeeklyReport();
        $weeklyReport->setTemplating($templating);

        $params = new \StdClass();
        $params->name = '12345';

        $result = $weeklyReport->getWeeklyMailContent($params);

        $this->assertInternalType('string', $result);
    }

    public function testGetCalculations()
    {
        $this->markTestIncomplete(
            'No data for 2016'
        );
        $gaService = $this->getMockBuilder('\Food\AppBundle\Service\GoogleAnalyticsService')
                          ->setMethods(['getUsers', 'getReturningUsers'])
                          ->getMock();

        $weeklyReport = $this->getMockBuilder('\Food\OrderBundle\Service\WeeklyReport')
                            ->setMethods(['getWeeklyDataFor',
                                          'getWeeklyDeliveryTime',
                                          'getWeeklyDeliveryTimesByRegion',
                                          'getNumberOfPlacesFromLastWeek'])
                            ->getMock();
        $weeklyReport->setGoogleAnalyticsService($gaService);

        $gaService->expects($this->once())
                  ->method('getUsers')
                  ->willReturn(123);

        $gaService->expects($this->once())
                  ->method('getReturningUsers')
                  ->willReturn(456);

        $weeklyReport->expects($this->exactly(3))
                    ->method('getWeeklyDataFor')
                    ->willReturn(123);

        $weeklyReport->expects($this->once())
                    ->method('getWeeklyDeliveryTime')
                    ->willReturn(456);

        $weeklyReport->expects($this->once())
                    ->method('getWeeklyDeliveryTimesByRegion')
                    ->willReturn(789);

        $result = $weeklyReport->getCalculations();

        $this->assertInstanceOf('\StdClass', $result);
    }

    public function testGetWeeklyDataFor()
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

        $weeklyReport = $this->getMockBuilder('\Food\OrderBundle\Service\WeeklyReport')
                            ->setMethods(['getWeeklyReportQuery'])
                            ->getMock();
        $weeklyReport->setConnection($connection);

        $weeklyReport->expects($this->any())
                    ->method('getWeeklyReportQuery')
                    ->willReturn('some string');

        $result1 = $weeklyReport->getWeeklyDataFor('income');
        $result2 = $weeklyReport->getWeeklyDataFor('successful_orders');
        $result3 = $weeklyReport->getWeeklyDataFor('average_cart');

        $this->assertInternalType('string', $result1);
        $this->assertInternalType('string', $result2);
        $this->assertInternalType('string', $result3);
    }

    public function testSendWeeklyMails()
    {
        $weeklyReport = new WeeklyReport();

        $result = $weeklyReport->sendWeeklyMails('email', [1, 2, 3], 'title', 'content');

        $this->assertInternalType('array', $result);
    }

    public function testSendWeeklyReport()
    {
        $output = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')
                       ->setMethods(['writeln'])
                       ->getMockForAbstractClass();

        $weeklyReport = $this->getMockBuilder('\Food\OrderBundle\Service\WeeklyReport')
                            ->setMethods(['getCalculations',
                                          'getWeeklyMailContent',
                                          'getWeeklyMailTitle',
                                          'sendWeeklyMails'])
                            ->getMock();
        $weeklyReport->setOutput($output);

        $weeklyReport->expects($this->any())
                    ->method('getCalculations')
                    ->willReturn(new \StdClass());

        $weeklyReport->expects($this->any())
                    ->method('getWeeklyMailContent')
                    ->willReturn('123');

        $weeklyReport->expects($this->any())
                    ->method('getWeeklyMailTitle')
                    ->willReturn('4321231');

        $weeklyReport->expects($this->any())
                    ->method('sendWeeklyMails')
                    ->willReturn(['some array']);

        $result1 = $weeklyReport->sendWeeklyReport('email', false);
        $result2 = $weeklyReport->sendWeeklyReport('email', true);

        $this->assertInternalType('array', $result1);
        $this->assertInternalType('array', $result2);
    }

    public function testGetNumberOfPlacesFromLastWeek()
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

        $weeklyReport = new WeeklyReport();
        $weeklyReport->setConnection($connection);

        $result = $weeklyReport->getNumberOfPlacesFromLastWeek();

        $this->assertInternalType('string', $result);
    }
}
