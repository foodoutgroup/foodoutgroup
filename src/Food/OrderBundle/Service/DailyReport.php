<?php

namespace Food\OrderBundle\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\TableHelper;
use Food\AppBundle\Service\GoogleAnalyticsService;

class DailyReport extends ContainerAware
{
    protected $connection;
    protected $dailyReportEmails;
    protected $output;
    protected $tableHelper;
    protected $googleAnalyticsService;
    protected $templating;

    protected $sqlMap = [
        'income' => 'SELECT IFNULL(SUM(o.total), 0.0) AS result',
        'successful_orders' => 'SELECT IFNULL(COUNT(*), 0) AS result',
        'average_cart' => 'SELECT IFNULL(AVG(o.total), 0.0) AS result'
    ];

    public function sendDailyReport($forceEmail, $notDryRun)
    {
        if (!$notDryRun) {
            $this->getOutput()->writeln('<bg=yellow;fg=white>This is dry run. No emails will be sent.</bg=yellow;fg=white>');
        }

        $income = $this->getDailyDataFor('income');
        $successfulOrders = $this->getDailyDataFor('successful_orders');
        $averageCartSize = round($this->getDailyDataFor('average_cart'), 2);
        $averageDeliveryTime = round($this->getDailyDeliveryTime());
        $averageDeliveryTimeByRegion = $this->getDailyDeliveryTimesByRegion();

        // stuff from google analytics
        $from = date('Y-m-d', strtotime('-1 day'));
        $to = $from;

        $pageviews = $this->getGoogleAnalyticsService()
                          ->getPageviews($from, $to);
        $uniquePageviews = $this->getGoogleAnalyticsService()
                                ->getUniquePageviews($from, $to);

        // output some data
        $this->getTableHelper()->setHeaders(['Metric', 'Value']);
        $this->getTableHelper()->setRows([
            ['Income', sprintf('<fg=cyan>€ %s</fg=cyan>', $income)],
            ['Number of successful orders', sprintf('<fg=cyan>%s</fg=cyan>', $successfulOrders)],
            ['Price of average cart', sprintf('<fg=cyan>€ %s</fg=cyan>', $averageCartSize)],
            ['Average delivery time', sprintf('<fg=cyan>%s mins</fg=cyan>', $averageDeliveryTime)],
            ['Pageviews', sprintf('<fg=cyan>%s views</fg=cyan>', $pageviews)],
            ['Unique pageviews', sprintf('<fg=cyan>%s views</fg=cyan>', $uniquePageviews)]
        ]);

        $this->getOutput()->writeln('Yesterday we had:');
        $this->getTableHelper()->render($this->getOutput());

        if (!$notDryRun) {
            return [false, '<fg=green>Dry run was successful.</fg=green>'];
        }

        $title = $this->getDailyMailTitle();
        $content = $this->getDailyMailContent($income,
                                              $successfulOrders,
                                              $averageCartSize,
                                              $averageDeliveryTime,
                                              $pageviews,
                                              $uniquePageviews,
                                              $averageDeliveryTimeByRegion);

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
                DATE(o.order_date) >= SUBDATE(CURRENT_DATE, 1) AND
                DATE(o.order_date) < CURRENT_DATE AND
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
                DATE(o.order_date) >= SUBDATE(CURRENT_DATE, 1) AND
                DATE(o.order_date) < CURRENT_DATE AND
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
                       date('Y-m-d', strtotime('-1 day')));
    }

    public function getDailyMailContent($income,
                                        $successfulOrders,
                                        $averageCartSize,
                                        $averageDeliveryTime,
                                        $pageviews,
                                        $uniquePageviews,
                                        $averageDeliveryTimeByRegion)
    {
        $template = 'FoodOrderBundle:DailyReport:email.html.twig';
        $data = [
            'income' => $income,
            'successfulOrders' => $successfulOrders,
            'averageCartSize' => $averageCartSize,
            'averageDeliveryTime' => $averageDeliveryTime,
            'pageviews' => $pageviews,
            'uniquePageviews' => $uniquePageviews,
            'averageDeliveryTimeByRegion' => $averageDeliveryTimeByRegion
        ];

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
                DATE(o.order_date) >= SUBDATE(CURRENT_DATE, 1) AND
                DATE(o.order_date) < CURRENT_DATE AND
                osl.event_date IS NOT NULL
        ';

        return sprintf($query, $partialSql);
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
