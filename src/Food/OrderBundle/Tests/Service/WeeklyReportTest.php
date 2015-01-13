<?php
namespace Food\OrderBundle\Tests\Service;

use Food\AppBundle\Test\WebTestCase;
use Food\OrderBundle\Service\WeeklyReport;

class WeeklyReportTest extends WebTestCase
{
    public function test_get_weekly_report_query()
    {
        $weekly_report = new WeeklyReport();

        $result = $weekly_report->getWeeklyReportQuery('very specific string');

        $this->assertContains('very specific string', $result);
    }

    public function test_get_weekly_data_for_income()
    {
        // 1.
        $connection_mock = $this->getMockBuilder('\Doctrine\DBAL\Connection')
                                ->disableOriginalConstructor()
                                ->setMethods(['prepare'])
                                ->getMock();

        $stmt_mock = $this->getMockBuilder('\StdClass')
                          ->setMethods(['bindValue', 'execute', 'fetch'])
                          ->getMock();

        // 2.
        $connection_mock->expects($this->atLeastOnce())
                        ->method('prepare')
                        ->willReturn($stmt_mock);

        $stmt_mock->expects($this->atLeastOnce())
                  ->method('fetch')
                  ->willReturn(['result' => '3.45']);

        $stmt_mock->expects($this->atLeastOnce())
                  ->method('execute')
                  ->willReturn($stmt_mock);

        // 3.
        $weekly_report = new WeeklyReport();
        $weekly_report->setConnection($connection_mock);

        // 4.
        $income = $weekly_report->getWeeklyDataFor('income');
        $successful_orders = $weekly_report->getWeeklyDataFor('successful_orders');
        $average_cart = $weekly_report->getWeeklyDataFor('average_cart');
        $average_delivery = $weekly_report->getWeeklyDataFor('average_delivery');

        // 5.
        $this->assertInternalType('string', $income);
        $this->assertInternalType('string', $successful_orders);
        $this->assertInternalType('string', $average_cart);
        $this->assertInternalType('string', $average_delivery);
        $this->assertSame('3.45', $income);
        $this->assertSame('3.45', $successful_orders);
        $this->assertSame('3.45', $average_cart);
        $this->assertSame('3.45', $average_delivery);
    }

    public function test_get_weekly_mail_title()
    {
        $weekly_report = new WeeklyReport();

        $result = $weekly_report->getWeeklyMailTitle();

        $this->assertInternalType('string', $result);
    }

    public function test_get_weekly_mail_content()
    {
        $templating_mock = $this->getMockBuilder('\Symfony\Bridge\Twig\Form\TwigRenderer')
                                ->disableOriginalConstructor()
                                ->setMethods(['render'])
                                ->getMock();

        $templating_mock->expects($this->once())
                        ->method('render')
                        ->willReturn('content');

        $weekly_report = new WeeklyReport();
        $weekly_report->setTemplating($templating_mock);

        $result = $weekly_report->getWeeklyMailContent(1, 2, 3, 4, 5, 6, 7);

        $this->assertInternalType('string', $result);
    }

    public function test_set_and_get_connection()
    {
        $weekly_report = new WeeklyReport();

        $connection_mock = $this->getMockBuilder('\Doctrine\DBAL\Connection')
                                ->disableOriginalConstructor()
                                ->getMock();

        $result = $weekly_report->setConnection($connection_mock);

        $this->assertSame($weekly_report, $result);
        $this->assertSame($result->getConnection(), $connection_mock);
    }

    public function test_set_and_get_weekly_report_emails()
    {
        $weekly_report = new WeeklyReport();

        $result = $weekly_report->setWeeklyReportEmails(['a', 'b']);

        $this->assertSame($result, $weekly_report);
        $this->assertSame(['a', 'b'], $result->getWeeklyReportEmails());
    }

    public function test_set_and_get_output()
    {
        $weekly_report = new WeeklyReport();

        $output_mock = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')
                            ->disableOriginalConstructor()
                            ->getMock();

        $result = $weekly_report->setOutput($output_mock);

        $this->assertSame($result, $weekly_report);
        $this->assertSame($result->getOutput(), $output_mock);
    }

    public function test_set_and_get_table_helper()
    {
        $weekly_report = new WeeklyReport();

        $table_helper_mock = $this->getMockBuilder('\Symfony\Component\Console\Helper\TableHelper')
                                  ->disableOriginalConstructor()
                                  ->getMock();

        $result = $weekly_report->setTableHelper($table_helper_mock);

        $this->assertSame($result, $weekly_report);
        $this->assertSame($result->getTableHelper(), $table_helper_mock);
    }

    public function test_success_weekly_send_mails()
    {
        $weekly_report = new WeeklyReport();

        $result = $weekly_report->sendWeeklyMails('127.0.0.1', ['127.0.0.1'], 'title', 'content');

        $this->assertInternalType('array', $result);
    }

    public function test_failure_weekly_send_mails()
    {
        $weekly_report = new WeeklyReport();

        $result = $weekly_report->sendWeeklyMails(null, [null, null], 'title', 'content');

        $this->assertInternalType('array', $result);
    }

    public function test_send_weekly_report_dry_run()
    {
        $output_mock = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')
                            ->disableOriginalConstructor()
                            ->getMock();

        $table_helper_mock = $this->getMockBuilder('\Symfony\Component\Console\Helper\TableHelper')
                                  ->disableOriginalConstructor()
                                  ->getMock();

        $weekly_report = $this->getMockBuilder('\Food\OrderBundle\Service\WeeklyReport')
                              ->setMethods(['getWeeklyDataFor', 'getNumberOfPlacesFromLastWeek'])
                              ->getMock();

        $ga_mock = $this->getMockBuilder('\Food\AppBundle\Service\GoogleAnalyticsService')
                        ->disableOriginalConstructor()
                        ->setMethods([])
                        ->getMock();

        $weekly_report->setOutput($output_mock);
        $weekly_report->setTableHelper($table_helper_mock);
        $weekly_report->expects($this->any())
                      ->method('getNumberOfPlacesFromLastWeek')
                      ->willReturn('123');
        $weekly_report->expects($this->atLeastOnce())
                      ->method('getWeeklyDataFor')
                      ->willReturn('123');
        $weekly_report->setGoogleAnalyticsService($ga_mock);

        $result = $weekly_report->sendWeeklyReport('127.0.0.1', false);

        $this->assertInternalType('array', $result);
        $this->assertSame(false, $result[0]);
    }

    public function test_send_weekly_report_not_dry_run()
    {
        $output_mock = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')
                            ->disableOriginalConstructor()
                            ->getMock();

        $table_helper_mock = $this->getMockBuilder('\Symfony\Component\Console\Helper\TableHelper')
                                  ->disableOriginalConstructor()
                                  ->getMock();

        $weekly_report = $this->getMockBuilder('\Food\OrderBundle\Service\WeeklyReport')
                              ->setMethods(['getWeeklyDataFor', 'getNumberOfPlacesFromLastWeek'])
                              ->getMock();

        $ga_mock = $this->getMockBuilder('\Food\AppBundle\Service\GoogleAnalyticsService')
                        ->disableOriginalConstructor()
                        ->setMethods([])
                        ->getMock();

        $templating_mock = $this->getMockBuilder('\Symfony\Bridge\Twig\Form\TwigRenderer')
                                ->disableOriginalConstructor()
                                ->setMethods(['render'])
                                ->getMock();

        $templating_mock->expects($this->once())
                        ->method('render')
                        ->willReturn('content');

        $weekly_report->setOutput($output_mock);
        $weekly_report->setTableHelper($table_helper_mock);
        $weekly_report->expects($this->any())
                      ->method('getNumberOfPlacesFromLastWeek')
                      ->willReturn('123');
        $weekly_report->expects($this->atLeastOnce())
                      ->method('getWeeklyDataFor')
                      ->willReturn('123');
        $weekly_report->setGoogleAnalyticsService($ga_mock);
        $weekly_report->setTemplating($templating_mock);

        $result = $weekly_report->sendWeeklyReport('127.0.0.1', true);

        $this->assertInternalType('array', $result);
        $this->assertSame(false, $result[0]);
    }

    public function test_get_number_of_places_from_last_week()
    {
        $weekly_report = new WeeklyReport();

        $connection_mock = $this->getMockBuilder('\Doctrine\DBAL\Connection')
                                ->disableOriginalConstructor()
                                ->setMethods(['prepare'])
                                ->getMock();

        $stmt_mock = $this->getMockBuilder('\StdClass')
                          ->setMethods(['bindValue', 'execute', 'fetch'])
                          ->getMock();

        // 2.
        $connection_mock->expects($this->atLeastOnce())
                        ->method('prepare')
                        ->willReturn($stmt_mock);

        $stmt_mock->expects($this->atLeastOnce())
                  ->method('fetch')
                  ->willReturn(['result' => '3.45']);

        $stmt_mock->expects($this->atLeastOnce())
                  ->method('execute')
                  ->willReturn($stmt_mock);

        $weekly_report->setConnection($connection_mock);

        $result = $weekly_report->getNumberOfPlacesFromLastWeek();

        $this->assertSame('3.45', $result);
    }
}
