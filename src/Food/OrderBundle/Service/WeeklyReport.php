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
        '3' => 84,
        '4' => 92,
        '5' => 100,
        '6' => 108,
        '7' => 116,
        '8' => 1024
    ];
    protected $kpiIncomeMap = [
        '1' => 6704,
        '2' => 6921,
        '3' => 7955,
        '4' => 7722,
        '5' => 8021,
        '6' => 8398,
        '7' => 8013,
        '8' => 8514,
        '9' => 13224,
        '10' => 15470,
        '11' => 17558,
        '12' => 19903
    ];
    protected $kpiOrdersMap = [
        '1' => 564,
        '2' => 583,
        '3' => 670,
        '4' => 620,
        '5' => 644,
        '6' => 674,
        '7' => 381,
        '8' => 440,
        '9' => 659,
        '10' => 774,
        '11' => 875,
        '12' => 990
    ];
    protected $kpiCartSizeMap = [
        '1' => 11.8,
        '2' => 11.8,
        '3' => 11.8,
        '4' => 12.4,
        '5' => 12.4,
        '6' => 12.4,
        '7' => 14.54,
        '8' => 13.31,
        '9' => 13.84,
        '10' => 13.82,
        '11' => 13.88,
        '12' => 13.93,
    ];
    protected $kpiDeliveryMap = [
        '1' => 60,
        '2' => 60,
        '3' => 60,
        '4' => 55,
        '5' => 55,
        '6' => 55,
        '7' => 55,
        '8' => 55,
        '9' => 55,
        '10' => 55,
        '11' => 55,
        '12' => 55
    ];

    protected $sqlMap = [
        'income' => 'SELECT IFNULL(SUM(o.total - IFNULL(o.delivery_price, 0)) / 1.21, 0.0) AS result',
        'successful_orders' => 'SELECT IFNULL(COUNT(*), 0) AS result',
        'average_cart' => 'SELECT IFNULL(AVG(o.total - IFNULL(o.delivery_price, 0)) / 1.21, 0.0) AS result'
    ];

    /**
     * @param string $forceEmail
     * @param boolean $notDryRun
     * @return array
     */
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

    /**
     * @return mixed
     */
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

    /**
     * @return mixed
     */
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

    /**
     * @return mixed
     */
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

    /**
     * @return string
     */
    public function getWeeklyMailTitle()
    {
        return sprintf('Weekly Foodout.lt report for %s to %s',
                       date('Y-m-d', strtotime(static::PHP_7_DAYS_AGO)),
                       date('Y-m-d', strtotime(static::PHP_1_DAY_AGO)));
    }

    /**
     * @param \StdClass $params
     * @return mixed
     */
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

    /**
     * @param string $forceEmail
     * @param array $weeklyReportEmails
     * @param string $title
     * @param string $content
     * @return array
     */
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

    /**
     * @param $metric
     * @return mixed
     */
    public function getWeeklyDataFor($metric)
    {
        $query = $this->getWeeklyReportQuery($metric);

        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['result'];
    }

    /**
     * @param $metric
     * @return string
     */
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

    /**
     * @return \StdClass
     */
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

    /**
     * @param Connection $connection
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param array $emails
     * @return $this
     */
    public function setWeeklyReportEmails(array $emails = [])
    {
        $this->weeklyReportEmails = $emails;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWeeklyReportEmails()
    {
        return $this->weeklyReportEmails;
    }

    /**
     * @param OutputInterface $output
     * @return $this
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param GoogleAnalyticsService $service
     * @return $this
     */
    public function setGoogleAnalyticsService(GoogleAnalyticsService $service)
    {
        $this->googleAnalyticsService = $service;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGoogleAnalyticsService()
    {
        return $this->googleAnalyticsService;
    }

    /**
     * @param $templating
     * @return $this
     */
    public function setTemplating($templating)
    {
        $this->templating = $templating;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTemplating()
    {
        return $this->templating;
    }
}
