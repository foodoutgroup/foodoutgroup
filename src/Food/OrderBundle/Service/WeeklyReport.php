<?php

namespace Food\OrderBundle\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\TableHelper;
use Food\AppBundle\Service\GoogleAnalyticsService;

class WeeklyReport extends ContainerAware
{
    protected $connection;
    protected $weeklyReportEmails;
    protected $output;
    protected $tableHelper;
    protected $googleAnalyticsService;

    public $sqlMap = [
        'income' => 'SELECT IFNULL(SUM(o.total), 0.0) AS result',
        'successful_orders' => 'SELECT IFNULL(COUNT(*), 0) AS result',
        'average_cart' => 'SELECT IFNULL(AVG(o.total), 0.0) AS result',
        'average_delivery' => 'SELECT AVG(TIMESTAMPDIFF(MINUTE, o.submitted_for_payment, o.delivery_time)) AS result'
    ];

    public function sendWeeklyReport($forceEmail, $notDryRun)
    {
        if (!$notDryRun) {
            $this->getOutput()->writeln('<bg=yellow;fg=white>This is dry run. No emails will be sent.</bg=yellow;fg=white>');
        }

        $places = $this->getNumberOfPlacesFromLastWeek();
        $income = $this->getWeeklyDataFor('income');
        $successfulOrders = $this->getWeeklyDataFor('successful_orders');
        $averageCartSize = round($this->getWeeklyDataFor('average_cart'), 2);
        $averageDeliveryTime = round($this->getWeeklyDataFor('average_delivery'));

        // stuff from google analytics
        $from = date('Y-m-d', strtotime('-7 day'));
        $to = date('Y-m-d', strtotime('-1 day'));

        $pageviews = $this->getGoogleAnalyticsService()
                          ->getPageviews($from, $to);
        $uniquePageviews = $this->getGoogleAnalyticsService()
                                ->getUniquePageviews($from, $to);

        // output some data
        $this->getTableHelper()->setHeaders(['Metric', 'Value']);
        $this->getTableHelper()->setRows([
            ['Number of places', sprintf('<fg=cyan>%s</fg=cyan>', $places)],
            ['Income', sprintf('<fg=cyan>€ %s</fg=cyan>', $income)],
            ['Number of successful orders', sprintf('<fg=cyan>%s</fg=cyan>', $successfulOrders)],
            ['Price of average cart', sprintf('<fg=cyan>€ %s</fg=cyan>', $averageCartSize)],
            ['Average delivery time', sprintf('<fg=cyan>%s mins</fg=cyan>', $averageDeliveryTime)],
            ['Pageviews', sprintf('<fg=cyan>%s views</fg=cyan>', $pageviews)],
            ['Unique pageviews', sprintf('<fg=cyan>%s views</fg=cyan>', $uniquePageviews)]
        ]);

        $this->getOutput()->writeln('Last week we had:');
        $this->getTableHelper()->render($this->getOutput());

        if (!$notDryRun) {
            return [false, '<fg=green>Dry run was successful.</fg=green>'];
        }

        $title = $this->getWeeklyMailTitle();
        $content = $this->getWeeklyMailContent($places,
                                               $income,
                                               $successfulOrders,
                                               $averageCartSize,
                                               $averageDeliveryTime,
                                               $pageviews,
                                               $uniquePageviews);

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

    public function getWeeklyMailTitle()
    {
        return sprintf('Weekly Foodout.lt report for %s to %s',
                       date('Y-m-d', strtotime('-7 day')),
                       date('Y-m-d', strtotime('-1 day')));
    }

    public function getWeeklyMailContent($places,
                                         $income,
                                         $successfulOrders,
                                         $averageCartSize,
                                         $averageDeliveryTime,
                                         $pageviews,
                                         $uniquePageviews)
    {
        return sprintf("Restoranų skaičius: %s
Pajamos: € %s
Sėkmingi užsakymai: %s
Vidutinė krepšelio suma: € %s
Vidutinis pristatymo laikas: %s min.
Lankytojų skaičius: %s
Unikalių lankytojų skaičius: %s",
                       $places,
                       $income,
                       $successfulOrders,
                       $averageCartSize,
                       $averageDeliveryTime,
                       $pageviews,
                       $uniquePageviews);
    }

    public function sendWeeklyMails($forceEmail,
                                    $weeklyReportEmails,
                                    $title,
                                    $content)
    {
        $mailSent = true;
        $emails = !empty($forceEmail) ? [$forceEmail] : $weeklyReportEmails;

        foreach ($emails as $email) {
            $headers = 'Content-Type: text/plain;charset=utf-8';
            $mailSent = @mail($email, $title, $content, $headers) && $mailSent;
        }

        return $mailSent
               ? [false, '<fg=green>Successfully sent mails.</fg=green>']
               : [true, '<fg=red>There was at least one error sending mails.</fg=red>'];
    }

    public function getWeeklyDataFor($metric)
    {
        $query = $this->getWeeklyReportQuery($this->sqlMap[$metric]);

        $stmt = $this->getConnection()->prepare($query);
        $stmt->bindValue(1, 'completed');
        $stmt->bindValue(2, 'complete');
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['result'];
    }

    public function getWeeklyReportQuery($partialSql)
    {
        $query = '
            %s
            FROM orders o
            WHERE
                o.order_status = ? AND
                o.payment_status = ? AND
                DATE(o.order_date) >= SUBDATE(CURRENT_DATE, 7) AND
                DATE(o.order_date) < CURRENT_DATE
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
}
