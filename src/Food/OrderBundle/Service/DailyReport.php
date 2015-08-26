<?php

namespace Food\OrderBundle\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Console\Output\OutputInterface;
use Food\AppBundle\Service\GoogleAnalyticsService;

class DailyReport extends ContainerAware
{
    const PHP_1_DAY_AGO = '-1 day';
    const MYSQL_1_DAY_AGO = 'SUBDATE(CURRENT_DATE, 1)';
    const MYSQL_0_DAYS_AGO = 'SUBDATE(CURRENT_DATE, 0)';

    protected $connection;
    protected $dailyReportEmails;
    protected $output;
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
    protected $kpiPlacesMap = [];
    protected $kpiIncomeMap = [];
    protected $kpiOrdersMap = [];
    protected $kpiCartSizeMap = [];
    protected $kpiDeliveryMap = [];

    protected $sqlMap = [
        'income' => 'SELECT IFNULL(SUM(o.total - IFNULL(o.delivery_price, 0.0)) / 1.21, 0.0) AS result',
        'successful_orders' => 'SELECT IFNULL(COUNT(*), 0) AS result',
        'average_cart' => 'SELECT IFNULL(AVG(o.total - IFNULL(o.delivery_price, 0.0)) / 1.21, 0.0) AS result'
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
                DATE(o.order_date) >= ' . static::MYSQL_1_DAY_AGO . ' AND
                DATE(o.order_date) < ' . static::MYSQL_0_DAYS_AGO . ' AND
                osl.event_date IS NOT NULL AND
                o.accept_time IS NOT NULL AND
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
                DATE(o.order_date) >= ' . static::MYSQL_1_DAY_AGO . ' AND
                DATE(o.order_date) < ' . static::MYSQL_0_DAYS_AGO . ' AND
                osl.event_date IS NOT NULL AND
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
        return sprintf('Daily '.$this->container->getParameter('domain').' report for %s',
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

        $calculations->uniqueUsers = $this->getGoogleAnalyticsService()
                                          ->getUsers($from, $to);
        $calculations->returningUsers = $this->getGoogleAnalyticsService()
                                             ->getReturningUsers($from, $to);

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

    public function setParameters()
    {
        $today = date("Ym");
        $todaySh = date("m");
        $yestr = date("Ym", strtotime("-1 day"));
        $yestrSh = date("m", strtotime("-1 day"));

        $this->kpiPlacesMap[$todaySh] = $this->container->getParameter('place'.$today);
        $this->kpiPlacesMap[$yestrSh] = $this->container->getParameter('place'.$yestr);

        $this->kpiIncomeMap[$todaySh] = $this->container->getParameter('income'.$today);
        $this->kpiIncomeMap[$yestrSh] = $this->container->getParameter('income'.$yestr);

        $this->kpiOrdersMap[$todaySh] = $this->container->getParameter('order'.$today);
        $this->kpiOrdersMap[$yestrSh] = $this->container->getParameter('order'.$yestr);

        $this->kpiCartSizeMap[$todaySh] = $this->container->getParameter('cartsize'.$today);
        $this->kpiCartSizeMap[$yestrSh] = $this->container->getParameter('cartsize'.$yestr);

        $this->kpiDeliveryMap[$todaySh] = $this->container->getParameter('delivery'.$today);
        $this->kpiDeliveryMap[$yestrSh] = $this->container->getParameter('delivery'.$yestr);
    }
}
