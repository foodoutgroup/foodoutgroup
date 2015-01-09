<?php

namespace Food\OrderBundle\Service\OrderService;

trait WeeklyReportDecorator
{
    public function sendWeeklyReport($forceEmail, $notDryRun, $output, $table)
    {
        if (!$notDryRun) {
            $output->writeln('<bg=yellow;fg=white>This is dry run. No emails will be sent.</bg=yellow;fg=white>');
        }

        $places = $this->getNumberOfPlacesFromLastWeek();
        $income = $this->getIncomeFromLastWeek();
        $successfulOrders = $this->getSuccessfulOrdersFromLastWeek();
        $averageCartSize = $this->getAverageCartSizeFromLastWeek();
        $averageDeliveryTime = $this->getAverageDeliveryTimeFromLastWeek();

        // output some data
        $table->setHeaders(['Metric', 'Value']);
        $table->setRows([
            ['Number of places', sprintf('<fg=cyan>%s</fg=cyan>', $places)],
            ['Income', sprintf('<fg=cyan>€ %s</fg=cyan>', $income)],
            ['Number of successful orders', sprintf('<fg=cyan>%s</fg=cyan>', $successfulOrders)],
            ['Price of average cart', sprintf('<fg=cyan>€ %s</fg=cyan>', $averageCartSize)],
            ['Average delivery time', sprintf('<fg=cyan>%s mins</fg=cyan>', $averageDeliveryTime)]
        ]);

        $output->writeln('Yesterday we had:');
        $table->render($output);

        if ($notDryRun) {
            // receivers
            $weeklyReportEmails = $this->container
                                       ->getParameter('weekly_report_emails');

            // content
            $title = $this->getWeeklyMailTitle();
            $content = $this->getWeeklyMailContent($places,
                                                   $income,
                                                   $successfulOrders,
                                                   $averageCartSize,
                                                   $averageDeliveryTime);

            return $this->sendWeeklyMails($forceEmail,
                                          $weeklyReportEmails,
                                          $title,
                                          $content);
        }

        return [false, '<fg=green>Dry run was successful.</fg=green>'];
    }

    protected function getNumberOfPlacesFromLastWeek()
    {
        // services
        $em = $this->getEm();
        $conn = $em->getConnection();

        $query = '
            SELECT IFNULL(COUNT(*), 0) AS total
            FROM place p
            WHERE
                p.active = 1 AND
                p.deleted_at IS NULL
        ';

        $stmt = $conn->prepare($query);
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['total'];
    }

    protected function getIncomeFromLastWeek()
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
                DATE(o.order_date) >= SUBDATE(CURRENT_DATE, 7) AND
                DATE(o.order_date) < CURRENT_DATE
        ';

        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, 'completed');
        $stmt->bindValue(2, 'complete');
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['total'];
    }

    protected function getSuccessfulOrdersFromLastWeek()
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
                DATE(o.order_date) >= SUBDATE(CURRENT_DATE, 7) AND
                DATE(o.order_date) < CURRENT_DATE
        ';

        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, 'completed');
        $stmt->bindValue(2, 'complete');
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['amount'];
    }

    protected function getAverageCartSizeFromLastWeek()
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
                DATE(o.order_date) >= SUBDATE(CURRENT_DATE, 7) AND
                DATE(o.order_date) < CURRENT_DATE
        ';

        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, 'completed');
        $stmt->bindValue(2, 'complete');
        $stmt->execute();

        $result = $stmt->fetch();

        return number_format($result['average_total'], 2, '.', '');
    }

    protected function getAverageDeliveryTimeFromLastWeek()
    {
        $em = $this->getEm();
        $conn = $em->getConnection();

        $query = '
            SELECT AVG(TIMESTAMPDIFF(MINUTE, o.submitted_for_payment, o.delivery_time)) AS average_minutes
            FROM orders o
            WHERE
                o.order_status = ? AND
                o.payment_status = ? AND
                DATE(o.order_date) >= SUBDATE(CURRENT_DATE, 7) AND
                DATE(o.order_date) < CURRENT_DATE
        ';

        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, 'completed');
        $stmt->bindValue(2, 'complete');
        $stmt->execute();

        $result = $stmt->fetch();

        return number_format($result['average_minutes'], 0, '.', '');
    }

    protected function getWeeklyMailTitle()
    {
        return sprintf('Weekly Foodout.lt report for %s to %s',
                       date('Y-m-d', strtotime('-7 day')),
                       date('Y-m-d', strtotime('-1 day')));
    }

    protected function getWeeklyMailContent($places,
                                            $income,
                                            $successfulOrders,
                                            $averageCartSize,
                                            $averageDeliveryTime)
    {
        return sprintf("Restoranų skaičius: %s\nPajamos: € %s\nSėkmingi užsakymai: %s\nVidutinė krepšelio suma: € %s\nVidutinis pristaytmo laikas: %s min.",
                       $places,
                       $income,
                       $successfulOrders,
                       $averageCartSize,
                       $averageDeliveryTime);
    }

    protected function sendWeeklyMails($forceEmail,
                                       $weeklyReportEmails,
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

        foreach ($weeklyReportEmails as $email) {
            $mailSent = @mail($email, $title, $content) && $mailSent;
        }

        if ($mailSent) {
            return [false, '<fg=green>Successfully sent mails.</fg=green>'];
        } else {
            return [true, '<fg=red>There was at least one error sending mails.</fg=red>'];
        }
    }
}
