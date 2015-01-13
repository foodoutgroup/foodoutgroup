<?php
namespace Food\OrderBundle\Tests\Service;

use Food\AppBundle\Test\WebTestCase;
use Food\OrderBundle\Service\DailyReport;

class DailyReportTest extends WebTestCase
{
    public function test_get_daily_report_query()
    {
        $daily_report = new DailyReport();

        $result = $daily_report->getDailyReportQuery('very specific string');

        $this->assertContains('very specific string', $result);
    }

    public function test_get_daily_data_for_income()
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
        $daily_report = new DailyReport();
        $daily_report->setConnection($connection_mock);

        // 4.
        $income = $daily_report->getDailyDataFor('income');
        $successful_orders = $daily_report->getDailyDataFor('successful_orders');
        $average_cart = $daily_report->getDailyDataFor('average_cart');
        $average_delivery = $daily_report->getDailyDataFor('average_delivery');

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

    public function test_get_daily_mail_title()
    {
        $daily_report = new DailyReport();

        $result = $daily_report->getDailyMailTitle();

        $this->assertInternalType('string', $result);
    }

    public function test_get_daily_mail_content()
    {
        $templating_mock = $this->getMockBuilder('\Symfony\Bridge\Twig\Form\TwigRenderer')
                                ->disableOriginalConstructor()
                                ->setMethods(['render'])
                                ->getMock();

        $templating_mock->expects($this->once())
                        ->method('render')
                        ->willReturn('content');

        $daily_report = new DailyReport();
        $daily_report->setTemplating($templating_mock);

        $result = $daily_report->getDailyMailContent(1, 2, 3, 4, 5, 6);

        $this->assertInternalType('string', $result);
    }

    public function test_set_and_get_connection()
    {
        $daily_report = new DailyReport();

        $connection_mock = $this->getMockBuilder('\Doctrine\DBAL\Connection')
                                ->disableOriginalConstructor()
                                ->getMock();

        $result = $daily_report->setConnection($connection_mock);

        $this->assertSame($daily_report, $result);
        $this->assertSame($result->getConnection(), $connection_mock);
    }

    public function test_set_and_get_daily_report_emails()
    {
        $daily_report = new DailyReport();

        $result = $daily_report->setDailyReportEmails(['a', 'b']);

        $this->assertSame($result, $daily_report);
        $this->assertSame(['a', 'b'], $result->getDailyReportEmails());
    }

    public function test_set_and_get_output()
    {
        $daily_report = new DailyReport();

        $output_mock = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')
                            ->disableOriginalConstructor()
                            ->getMock();

        $result = $daily_report->setOutput($output_mock);

        $this->assertSame($result, $daily_report);
        $this->assertSame($result->getOutput(), $output_mock);
    }

    public function test_set_and_get_table_helper()
    {
        $daily_report = new DailyReport();

        $table_helper_mock = $this->getMockBuilder('\Symfony\Component\Console\Helper\TableHelper')
                                  ->disableOriginalConstructor()
                                  ->getMock();

        $result = $daily_report->setTableHelper($table_helper_mock);

        $this->assertSame($result, $daily_report);
        $this->assertSame($result->getTableHelper(), $table_helper_mock);
    }

    public function test_success_daily_send_mails()
    {
        $daily_report = new DailyReport();

        $result = $daily_report->sendDailyMails('127.0.0.1', ['127.0.0.1'], 'title', 'content');

        $this->assertInternalType('array', $result);
    }

    public function test_failure_daily_send_mails()
    {
        $daily_report = new DailyReport();

        $result = $daily_report->sendDailyMails(null, [null, null], 'title', 'content');

        $this->assertInternalType('array', $result);
    }

    public function test_send_daily_report_dry_run()
    {
        $output_mock = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')
                            ->disableOriginalConstructor()
                            ->getMock();

        $table_helper_mock = $this->getMockBuilder('\Symfony\Component\Console\Helper\TableHelper')
                                  ->disableOriginalConstructor()
                                  ->getMock();

        $daily_report = $this->getMockBuilder('\Food\OrderBundle\Service\DailyReport')
                             ->setMethods(['getDailyDataFor'])
                             ->getMock();

        $ga_mock = $this->getMockBuilder('\Food\AppBundle\Service\GoogleAnalyticsService')
                        ->disableOriginalConstructor()
                        ->setMethods([])
                        ->getMock();

        $daily_report->setOutput($output_mock);
        $daily_report->setTableHelper($table_helper_mock);
        $daily_report->expects($this->atLeastOnce())
                     ->method('getDailyDataFor')
                     ->willReturn('123');
        $daily_report->setGoogleAnalyticsService($ga_mock);

        $result = $daily_report->sendDailyReport('127.0.0.1', false);

        $this->assertInternalType('array', $result);
        $this->assertSame(false, $result[0]);
    }

    public function test_send_daily_report_not_dry_run()
    {
        $output_mock = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')
                            ->disableOriginalConstructor()
                            ->getMock();

        $table_helper_mock = $this->getMockBuilder('\Symfony\Component\Console\Helper\TableHelper')
                                  ->disableOriginalConstructor()
                                  ->getMock();

        $daily_report = $this->getMockBuilder('\Food\OrderBundle\Service\DailyReport')
                             ->setMethods(['getDailyDataFor'])
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

        $daily_report->setOutput($output_mock);
        $daily_report->setTableHelper($table_helper_mock);
        $daily_report->expects($this->atLeastOnce())
                     ->method('getDailyDataFor')
                     ->willReturn('123');
        $daily_report->setGoogleAnalyticsService($ga_mock);
        $daily_report->setTemplating($templating_mock);

        $result = $daily_report->sendDailyReport('127.0.0.1', true);

        $this->assertInternalType('array', $result);
        $this->assertSame(false, $result[0]);
    }
}
