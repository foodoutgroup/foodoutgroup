<?php

namespace Food\OrderBundle\Service\OrderService;

trait DailyReportDecorator
{
    public function sendDailyReport($forceEmail, $notDryRun, $output, $table)
    {
        if (!$notDryRun) {
            $output->writeln('<bg=yellow;fg=white>This is dry run. No emails will be sent.</bg=yellow;fg=white>');
        }

        $income = $this->getIncomeFromYesterday();
        $successfulOrders = $this->getSuccessfulOrdersFromYesterday();
        $averageCartSize = $this->getAverageCartSizeFromYesterday();
        $averageDeliveryTime = $this->getAverageDeliveryTimeFromYesterday();

        // output some data
        $table->setHeaders(['Metric', 'Value']);
        $table->setRows([
            ['Income', sprintf('<fg=cyan>€ %s</fg=cyan>', $income)],
            ['Number of successful orders', sprintf('<fg=cyan>%s</fg=cyan>', $successfulOrders)],
            ['Price of average cart', sprintf('<fg=cyan>€ %s</fg=cyan>', $averageCartSize)],
            ['Average delivery time', sprintf('<fg=cyan>%s mins</fg=cyan>', $averageDeliveryTime)]
        ]);

        $output->writeln('Yesterday we had:');
        $table->render($output);

        if ($notDryRun) {
            // receivers
            $dailyReportEmails = $this->container
                                      ->getParameter('daily_report_emails');

            // content
            $title = $this->getDailyMailTitle();
            $content = $this->getDailyMailContent($income,
                                                  $successfulOrders,
                                                  $averageCartSize,
                                                  $averageDeliveryTime);

            return $this->sendDailyMails($forceEmail,
                                         $dailyReportEmails,
                                         $title,
                                         $content);
        }

        return [false, '<fg=green>Dry run was successful.</fg=green>'];
    }

    protected function getIncomeFromYesterday()
    {
        // services
        $em = $this->getEm();
        $conn = $em->getConnection();

        $query = '
            SELECT IFNULL(SUM(o.total), 0.0) AS total
            FROM orders o
            WHERE
                o.order_status = ? AND
                o.payment_status = ? AND
                DATE(o.order_date) >= SUBDATE(CURRENT_DATE, 1) AND
                DATE(o.order_date) < CURRENT_DATE
        ';

        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, 'completed');
        $stmt->bindValue(2, 'complete');
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['total'];
    }

    protected function getSuccessfulOrdersFromYesterday()
    {
        // services
        $em = $this->getEm();
        $conn = $em->getConnection();

        $query = '
            SELECT IFNULL(COUNT(*), 0) AS amount
            FROM orders o
            WHERE
                o.order_status = ? AND
                o.payment_status = ? AND
                DATE(o.order_date) >= SUBDATE(CURRENT_DATE, 1) AND
                DATE(o.order_date) < CURRENT_DATE
        ';

        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, 'completed');
        $stmt->bindValue(2, 'complete');
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['amount'];
    }

    protected function getAverageCartSizeFromYesterday()
    {
        // services
        $em = $this->getEm();
        $conn = $em->getConnection();

        $query = '
            SELECT IFNULL(AVG(o.total), 0.0) AS average_total
            FROM orders o
            WHERE
                o.order_status = ? AND
                o.payment_status = ? AND
                DATE(o.order_date) >= SUBDATE(CURRENT_DATE, 1) AND
                DATE(o.order_date) < CURRENT_DATE
        ';

        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, 'completed');
        $stmt->bindValue(2, 'complete');
        $stmt->execute();

        $result = $stmt->fetch();

        return number_format($result['average_total'], 2, '.', '');
    }

    protected function getAverageDeliveryTimeFromYesterday()
    {
        $em = $this->getEm();
        $conn = $em->getConnection();

        $query = '
            SELECT AVG(TIMESTAMPDIFF(MINUTE, o.submitted_for_payment, o.delivery_time)) AS average_minutes
            FROM orders o
            WHERE
                o.order_status = ? AND
                o.payment_status = ? AND
                DATE(o.order_date) >= SUBDATE(CURRENT_DATE, 1) AND
                DATE(o.order_date) < CURRENT_DATE
        ';

        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, 'completed');
        $stmt->bindValue(2, 'complete');
        $stmt->execute();

        $result = $stmt->fetch();

        return number_format($result['average_minutes'], 0, '.', '');
    }

    protected function getDailyMailTitle()
    {
        return sprintf('Daily Foodout.lt report for %s',
                       date('Y-m-d', strtotime('-1 day')));
    }

    protected function getDailyMailContent($income,
                                           $successfulOrders,
                                           $averageCartSize,
                                           $averageDeliveryTime)
    {
        return sprintf("Pajamos: € %s\nSėkmingi užsakymai: %s\nVidutinė krepšelio suma: € %s\nVidutinis pristaytmo laikas: %s min.",
                       $income,
                       $successfulOrders,
                       $averageCartSize,
                       $averageDeliveryTime);
    }

    protected function sendDailyMails($forceEmail,
                                      $dailyReportEmails,
                                      $title,
                                      $content)
    {
        $mailSent = true;

        if ($forceEmail) {
            $mailSent = @mail($forceEmail, $title, $content) && $mailSent;

            if ($mailSent) {
                return [false, '<fg=green>Successfully sent mail.</fg=green>'];
            } else {
                return [true, '<fg=red>There was error sending mail.</fg=red>'];
            }
        }

        foreach ($dailyReportEmails as $email) {
            $mailSent = @mail($email, $title, $content) && $mailSent;
        }

        if ($mailSent) {
            return [false, '<fg=green>Successfully sent mails.</fg=green>'];
        } else {
            return [true, '<fg=red>There was at least one error sending mails.</fg=red>'];
        }
    }
}
