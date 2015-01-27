<?php

namespace Food\OrderBundle\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\TableHelper;
use Food\AppBundle\Service\GoogleAnalyticsService;

class WeeklyReport extends ContainerAware
{
    const PHP_1_DAY_AGO = '-1 day';
    const PHP_7_DAYS_AGO = '-7 day';
    const MYSQL_7_DAYS_AGO = 'SUBDATE(CURRENT_DATE, 7)';
    const MYSQL_0_DAYS_AGO = 'SUBDATE(CURRENT_DATE, 0)';

    protected $connection;
    protected $weeklyReportEmails;
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
    protected $kpiPlacesMap = [
        '1' => 83,
        '2' => 92,
        '3' => 101
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
        'average_cart' => 'SELECT IFNULL(AVG(o.total - IFNULL(o.delivery_price, 0)) / 1.21, 0.0) AS result'
    ];

    public function sendWeeklyReport($forceEmail, $notDryRun)
    {
        if (!$notDryRun) {
            $this->getOutput()->writeln('<bg=yellow;fg=white>This is dry run. No emails will be sent.</bg=yellow;fg=white>');
        }

        if (!$notDryRun) {
            return [false, '<fg=green>Dry run was successful.</fg=green>'];
        }

        $calculations = $this->getCalculations();

        $title = $this->getWeeklyMailTitle();
        $content = $this->getWeeklyMailContent($calculations);

        return $this->sendWeeklyMails($forceEmail,
                                      $this->getWeeklyReportEmails(),
                                      $title,
                                      $content);
    }

    public function getNumberOfPlacesFromLastWeek()
    {
        $query = '
            SELECT IFNULL(COUNT(*), 0) AS result
            FROM place p
            WHERE
                p.active = 1 AND
                p.deleted_at IS NULL
        ';

        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['result'];
    }

    public function getWeeklyDeliveryTime()
    {
        $query = '
            SELECT AVG(IF(TIMESTAMPDIFF(MINUTE, o.accept_time, osl.event_date)<180,
                       TIMESTAMPDIFF(MINUTE, o.accept_time, osl.event_date),
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
            WHERE
                o.order_status = \'completed\' AND
                o.payment_status = \'complete\' AND
                DATE(o.order_date) >= ' . static::MYSQL_7_DAYS_AGO . ' AND
                DATE(o.order_date) < ' . static::MYSQL_0_DAYS_AGO . ' AND
                o.accept_time IS NOT NULL AND
                o.delivery_type = \'deliver\' AND
                osl.source != \'auto_close_order_command\' AND
                o.place_point_self_delivery = 0';

        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['result'];
    }

    public function getWeeklyDeliveryTimesByRegion()
    {
        $query = '
            SELECT
                o.place_point_city,
                AVG(IF(TIMESTAMPDIFF(MINUTE, o.accept_time, osl.event_date)<180,
                       TIMESTAMPDIFF(MINUTE, o.accept_time, osl.event_date),
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
            WHERE
                o.order_status = \'completed\' AND
                o.payment_status = \'complete\' AND
                DATE(o.order_date) >= ' . static::MYSQL_7_DAYS_AGO . ' AND
                DATE(o.order_date) < ' . static::MYSQL_0_DAYS_AGO . ' AND
                o.accept_time IS NOT NULL AND
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

    public function getWeeklyMailTitle()
    {
        return sprintf('Weekly Foodout.lt report for %s to %s',
                       date('Y-m-d', strtotime(static::PHP_7_DAYS_AGO)),
                       date('Y-m-d', strtotime(static::PHP_1_DAY_AGO)));
    }

    public function getWeeklyMailContent(\StdClass $params)
    {
        $template = 'FoodOrderBundle:WeeklyReport:email.html.twig';
        $data = [];
        $paramsList = get_object_vars($params);

        foreach ($paramsList as $key => $value) {
            $data[$key] = $value;
        }

        return $this->getTemplating()->render($template, $data);
    }

    public function sendWeeklyMails($forceEmail,
                                    $weeklyReportEmails,
                                    $title,
                                    $content)
    {
        $mailSent = true;
        $emails = !empty($forceEmail) ? [$forceEmail] : $weeklyReportEmails;

        foreach ($emails as $email) {
            $headers = "Content-Type: text/html;charset=utf-8\r\nFrom: info@foodout.lt";
            $mailSent = @mail($email, $title, $content, $headers) && $mailSent;
        }

        return $mailSent
               ? [false, '<fg=green>Successfully sent mails.</fg=green>']
               : [true, '<fg=red>There was at least one error sending mails.</fg=red>'];
    }

    public function getWeeklyDataFor($metric)
    {
        $query = $this->getWeeklyReportQuery($metric);

        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['result'];
    }

    public function getWeeklyReportQuery($metric)
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
                DATE(o.order_date) >= ' . static::MYSQL_7_DAYS_AGO . ' AND
                DATE(o.order_date) < ' . static::MYSQL_0_DAYS_AGO . ' AND
                osl.event_date IS NOT NULL
        ';

        return sprintf($query, $partialSql);
    }

    public function getCalculations()
    {
        // local
        $calculations = new \StdClass();
        $calculations->places = $this->getNumberOfPlacesFromLastWeek();
        $calculations->income = number_format($this->getWeeklyDataFor('income'), 2, '.', '');
        $calculations->successfulOrders = $this->getWeeklyDataFor('successful_orders');
        $calculations->averageCartSize = number_format($this->getWeeklyDataFor('average_cart'), 2, '.', '');
        $calculations->averageDeliveryTime = round($this->getWeeklyDeliveryTime());
        $calculations->averageDeliveryTimeByRegion = $this->getWeeklyDeliveryTimesByRegion();

        // from google analytics
        $from = date('Y-m-d', strtotime(static::PHP_7_DAYS_AGO));
        $to = date('Y-m-d', strtotime(static::PHP_1_DAY_AGO));

        $calculations->uniqueUsers = $this->getGoogleAnalyticsService()
                                          ->getUsers($from, $to);
        $calculations->returningUsers = $this->getGoogleAnalyticsService()
                                             ->getReturningusers($from, $to);

        // KPI
        $dayOfWeek = date('N', strtotime(static::PHP_7_DAYS_AGO));
        $monthOfYear = date('n', strtotime(static::PHP_7_DAYS_AGO));

        $calculations->kpiPlaces = $this->kpiPlacesMap[$monthOfYear];
        $calculations->kpiIncome = number_format(7 * $this->kpiIncomeMap[$monthOfYear], 2, '.', '');
        $calculations->kpiSuccessfulOrders = round(7 * $this->kpiOrdersMap[$monthOfYear]);
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

    public function setWeeklyReportEmails(array $emails = [])
    {
        $this->weeklyReportEmails = $emails;
        return $this;
    }

    public function getWeeklyReportEmails()
    {
        return $this->weeklyReportEmails;
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
