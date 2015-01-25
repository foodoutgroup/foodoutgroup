<?php

namespace Food\OrderBundle\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\TableHelper;
use Food\AppBundle\Service\GoogleAnalyticsService;

class DailyReport extends ContainerAware
{
    const PHP_1_DAY_AGO = '-1 day';
    const MYSQL_1_DAY_AGO = 'SUBDATE(CURRENT_DATE, 1)';
    const MYSQL_0_DAYS_AGO = 'SUBDATE(CURRENT_DATE, 0)';

    protected $connection;
    protected $dailyReportEmails;
    protected $output;
    protected $tableHelper;
    protected $googleAnalyticsService;
    protected $templating;
    protected $kpiMap = [
        '1' => 0.8,
        '2' => 0.8,
        '3' => 0.8,
        '4' => 1.0,
        '5' => 1.3,
        '6' => 1.3,
        '7' => 1.0
    ];
    protected $kpiIncomeMap = [
        '1' => 6704,
        '2' => 6921,
        '3' => 7955
    ];
    protected $kpiOrdersMap = [
        '1' => 564,
        '2' => 583,
        '3' => 670
    ];
    protected $kpiCartSizeMap = [
        '1' => 11.8,
        '2' => 11.8,
        '3' => 11.8
    ];
    protected $kpiDeliveryMap = [
        '1' => 60,
        '2' => 60,
        '3' => 60
    ];

    protected $sqlMap = [
        'income' => 'SELECT IFNULL(SUM(o.total - IFNULL(o.delivery_price, 0)) / 1.21, 0.0) AS result',
        'successful_orders' => 'SELECT IFNULL(COUNT(*), 0) AS result',
        'average_cart' => 'SELECT IFNULL(AVG((o.total - IFNULL(o.delivery_price, 0))/1.21), 0.0) AS result'
    ];

    public function sendDailyReport($forceEmail, $notDryRun)
    {
        if (!$notDryRun) {
            $this->getOutput()->writeln('<bg=yellow;fg=white>This is dry run. No emails will be sent.</bg=yellow;fg=white>');
        }

        $calculations = $this->getCalculations();

        if (!$notDryRun) {
            return [false, '<fg=green>Dry run was successful.</fg=green>'];
        }

        $content = $this->getDailyMailContent($calculations);
        $title = $this->getDailyMailTitle();

        return $this->sendDailyMails($forceEmail,
                                     $this->getDailyReportEmails(),
                                     $title,
                                     $content);
    }

    public function getDailyDeliveryTime()
    {
        $query = '
            SELECT AVG(IF(TIMESTAMPDIFF(MINUTE, osl2.event_date, osl.event_date)<180,
                       TIMESTAMPDIFF(MINUTE, osl2.event_date, osl.event_date),
                       60)) AS result
            FROM orders o
            INNER JOIN (
                SELECT *
                FROM order_status_log
                GROUP BY
                    order_id,
                    new_status
                HAVING new_status = \'completed\'
                ORDER BY event_date DESC
            ) osl ON osl.order_id = o.id
            INNER JOIN (
                SELECT *
                FROM order_status_log
                GROUP BY
                    order_id,
                    new_status
                HAVING new_status = \'assigned\'
                ORDER BY event_date DESC
            ) osl2 ON osl2.order_id = o.id
            WHERE
                o.order_status = \'completed\' AND
                o.payment_status = \'complete\' AND
                DATE(o.order_date) >= ' . static::MYSQL_1_DAY_AGO . ' AND
                DATE(o.order_date) < ' . static::MYSQL_0_DAYS_AGO . ' AND
                osl.event_date IS NOT NULL AND
                o.delivery_type = \'deliver\' AND
                osl.source != \'auto_close_order_command\' AND
                o.place_point_self_delivery = 0';

        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['result'];
    }

    public function getDailyDeliveryTimesByRegion()
    {
        $query = '
            SELECT
                o.place_point_city,
                AVG(IF(TIMESTAMPDIFF(MINUTE, osl2.event_date, osl.event_date)<180,
                       TIMESTAMPDIFF(MINUTE, osl2.event_date, osl.event_date),
                       60)) AS result
            FROM orders o
            INNER JOIN (
                SELECT *
                FROM order_status_log
                GROUP BY
                    order_id,
                    new_status
                HAVING new_status = \'completed\'
                ORDER BY event_date DESC
            ) osl ON osl.order_id = o.id
            INNER JOIN (
                SELECT *
                FROM order_status_log
                GROUP BY
                    order_id,
                    new_status
                HAVING new_status = \'assigned\'
                ORDER BY event_date DESC
            ) osl2 ON osl2.order_id = o.id
            WHERE
                o.order_status = \'completed\' AND
                o.payment_status = \'complete\' AND
                DATE(o.order_date) >= ' . static::MYSQL_1_DAY_AGO . ' AND
                DATE(o.order_date) < ' . static::MYSQL_0_DAYS_AGO . ' AND
                osl.event_date IS NOT NULL AND
                o.delivery_type = \'deliver\' AND
                osl.source != \'auto_close_order_command\' AND
                o.place_point_self_delivery = 0
            GROUP BY o.place_point_city
            ORDER BY o.place_point_city DESC';

        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute();

        $result = $stmt->fetchAll();

        return $result;
    }

    public function getDailyDataFor($metric)
    {
        $query = $this->getDailyReportQuery($metric);

        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['result'];
    }

    public function getDailyMailTitle()
    {
        return sprintf('Daily Foodout.lt report for %s',
                       date('Y-m-d', strtotime(static::PHP_1_DAY_AGO)));
    }

    public function getDailyMailContent(\StdClass $params)
    {
        $template = 'FoodOrderBundle:DailyReport:email.html.twig';
        $data = [];
        $paramsList = get_object_vars($params);

        foreach ($paramsList as $key => $value) {
            $data[$key] = $value;
        }

        return $this->getTemplating()->render($template, $data);
    }

    public function sendDailyMails($forceEmail,
                                   $dailyReportEmails,
                                   $title,
                                   $content)
    {
        $mailSent = true;
        $emails = !empty($forceEmail) ? [$forceEmail] : $dailyReportEmails;

        foreach ($emails as $email) {
            $headers = "Content-Type: text/html;charset=utf-8\r\nFrom: info@foodout.lt";
            $mailSent = @mail($email, $title, $content, $headers) && $mailSent;
        }

        return $mailSent
               ? [false, '<fg=green>Successfully sent mails.</fg=green>']
               : [true, '<fg=red>There was at least one error sending mails.</fg=red>'];
    }

    public function getDailyReportQuery($metric)
    {
        $partialSql = $this->sqlMap[$metric];

        $query = '
            %s
            FROM orders o
            INNER JOIN (
                SELECT *
                FROM order_status_log
                GROUP BY
                    order_id,
                    new_status
                HAVING new_status = \'completed\'
                ORDER BY event_date DESC
            ) osl ON osl.order_id = o.id
            WHERE
                o.order_status = \'completed\' AND
                o.payment_status = \'complete\' AND
                DATE(o.order_date) >= ' . static::MYSQL_1_DAY_AGO . ' AND
                DATE(o.order_date) < ' . static::MYSQL_0_DAYS_AGO . ' AND
                osl.event_date IS NOT NULL
        ';

        return sprintf($query, $partialSql);
    }

    public function getCalculations()
    {
        // local
        $calculations = new \StdClass();
        $calculations->income = number_format($this->getDailyDataFor('income'), 2, '.', '');
        $calculations->successfulOrders = $this->getDailyDataFor('successful_orders');
        $calculations->averageCartSize = number_format($this->getDailyDataFor('average_cart'), 2, '.', '');
        $calculations->averageDeliveryTime = round($this->getDailyDeliveryTime());
        $calculations->averageDeliveryTimeByRegion = $this->getDailyDeliveryTimesByRegion();

        // from google analytics
        $from = date('Y-m-d', strtotime(static::PHP_1_DAY_AGO));
        $to = $from;

        $calculations->pageviews = $this->getGoogleAnalyticsService()
                                        ->getPageviews($from, $to);
        $calculations->uniquePageviews = $this->getGoogleAnalyticsService()
                                              ->getUniquePageviews($from, $to);

        // KPI
        $dayOfWeek = date('N', strtotime(static::PHP_1_DAY_AGO));
        $monthOfYear = date('n', strtotime(static::PHP_1_DAY_AGO));

        $calculations->kpiIncome = number_format($this->kpiMap[$dayOfWeek] * $this->kpiIncomeMap[$monthOfYear], 2, '.', '');
        $calculations->kpiSuccessfulOrders = round($this->kpiMap[$dayOfWeek] * $this->kpiOrdersMap[$monthOfYear]);
        $calculations->kpiAverageCartSize = number_format($this->kpiCartSizeMap[$monthOfYear], 2, '.', '');
        $calculations->kpiAverageDeliveryTime = $this->kpiDeliveryMap[$monthOfYear];

        // result
        return $calculations;
    }

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function setDailyReportEmails(array $emails = [])
    {
        $this->dailyReportEmails = $emails;
        return $this;
    }

    public function getDailyReportEmails()
    {
        return $this->dailyReportEmails;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function setTableHelper(TableHelper $tableHelper)
    {
        $this->tableHelper = $tableHelper;
        return $this;
    }

    public function getTableHelper()
    {
        return $this->tableHelper;
    }

    public function setGoogleAnalyticsService(GoogleAnalyticsService $service)
    {
        $this->googleAnalyticsService = $service;
        return $this;
    }

    public function getGoogleAnalyticsService()
    {
        return $this->googleAnalyticsService;
    }

    public function setTemplating($templating)
    {
        $this->templating = $templating;
        return $this;
    }

    public function getTemplating()
    {
        return $this->templating;
    }
}
