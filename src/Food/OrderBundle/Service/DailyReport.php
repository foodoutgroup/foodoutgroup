<?php

namespace Food\OrderBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\TableHelper;

class DailyReport extends ContainerAware
{
    protected $connection;
    protected $dailyReportEmails;
    protected $output;
    protected $tableHelper;

    protected $sqlMap = [
        'income' => 'SELECT IFNULL(SUM(o.total), 0.0) AS result',
        'successful_orders' => 'SELECT IFNULL(COUNT(*), 0) AS result',
        'average_cart' => 'SELECT IFNULL(AVG(o.total), 0.0) AS result',
        'average_delivery' => 'SELECT AVG(TIMESTAMPDIFF(MINUTE, o.submitted_for_payment, o.delivery_time)) AS result'
    ];

    public function sendDailyReport($forceEmail, $notDryRun)
    {
        if (!$notDryRun) {
            $this->getOutput()->writeln('<bg=yellow;fg=white>This is dry run. No emails will be sent.</bg=yellow;fg=white>');
        }

        $income = $this->getDailyDataFor('income');
        $successfulOrders = $this->getDailyDataFor('successful_orders');
        $averageCartSize = round($this->getDailyDataFor('average_cart'), 2);
        $averageDeliveryTime = round($this->getDailyDataFor('average_delivery'));

        // output some data
        $this->getTableHelper()->setHeaders(['Metric', 'Value']);
        $this->getTableHelper()->setRows([
            ['Income', sprintf('<fg=cyan>€ %s</fg=cyan>', $income)],
            ['Number of successful orders', sprintf('<fg=cyan>%s</fg=cyan>', $successfulOrders)],
            ['Price of average cart', sprintf('<fg=cyan>€ %s</fg=cyan>', $averageCartSize)],
            ['Average delivery time', sprintf('<fg=cyan>%s mins</fg=cyan>', $averageDeliveryTime)]
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
                                              $averageDeliveryTime);

        return $this->sendDailyMails($forceEmail,
                                     $this->getDailyReportEmails(),
                                     $title,
                                     $content);
    }

    public function getDailyDataFor($metric)
    {
        $query = $this->getDailyReportQuery($this->sqlMap[$metric]);

        $stmt = $this->getConnection()->prepare($query);
        $stmt->bindValue(1, 'completed');
        $stmt->bindValue(2, 'complete');
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
                                        $averageDeliveryTime)
    {
        return sprintf("Pajamos: € %s\nSėkmingi užsakymai: %s\nVidutinė krepšelio suma: € %s\nVidutinis pristatymo laikas: %s min.",
                       $income,
                       $successfulOrders,
                       $averageCartSize,
                       $averageDeliveryTime);
    }

    public function sendDailyMails($forceEmail,
                                   $dailyReportEmails,
                                   $title,
                                   $content)
    {
        $mailSent = true;
        $emails = !empty($forceEmail) ? [$forceEmail] : $dailyReportEmails;

        foreach ($emails as $email) {
            $mailSent = @mail($email, $title, $content) && $mailSent;
        }

        return $mailSent
               ? [false, '<fg=green>Successfully sent mails.</fg=green>']
               : [true, '<fg=red>There was at least one error sending mails.</fg=red>'];
    }

    public function getDailyReportQuery($partialSql)
    {
        $query = '
            %s
            FROM orders o
            WHERE
                o.order_status = ? AND
                o.payment_status = ? AND
                DATE(o.order_date) >= SUBDATE(CURRENT_DATE, 1) AND
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
}
