<?php

namespace Food\ReportBundle\Command;

use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRfmCommand extends ContainerAwareCommand
{
    private $_timeStart;

    protected function configure()
    {
        $this->_timeStart = microtime(true);
        $this
            ->setName('report:update:rfm')
            ->setDescription('Update report rfm table')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('logger');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $connection = $em->getConnection();
        // < 1 men - 5, 1-3 men - 3, 3-6 men - 2, > 6 men - 1, niekada - 0
        $recencyQ = 'IF(MAX(o.order_date) IS NULL, 0, IF(MAX(o.order_date) < DATE_SUB(NOW(), INTERVAL 6 MONTH), 1, IF(MAX(o.order_date) < DATE_SUB(NOW(), INTERVAL 3 MONTH), 2, IF(MAX(o.order_date) < DATE_SUB(NOW(), INTERVAL 1 MONTH), 3, 5))))';

        // > 6 - 5, 4-6 - 3, 2-3 - 2, 1 - 1, nera - 0
        $frequencyQ = 'IF(COUNT(o.id) > 6, 5, IF(COUNT(o.id) >= 4, 3, IF(COUNT(o.id) >= 2, 2, IF(COUNT(o.id) = 1, 1, 0))))';

        // > 25 - 5, 20-25 - 3, 15-25 - 2, 10-15 - 1, maziau arba nera - 0
        $monetaryQ = 'IF(AVG(o.total) > 25, 5, IF(AVG(o.total) >= 20, 3, IF(AVG(o.total) >= 15, 2, IF(AVG(o.total) >= 10, 1, 0))))';
        $query = "INSERT INTO `report_rfm` (user_id, email, phone, firstname, lastname, is_business_client, 
                                            company_name, first_order_date, last_order_date, 
                                            recency_score, 
                                            frequency_score, 
                                            monetary_score, 
                                            total_rfm_score) 
                  SELECT fu.id, fu.email, fu.phone, fu.firstname, fu.lastname, fu.is_bussines_client, 
                         fu.company_name, MIN(o.order_date), MAX(o.order_date), 
                         ".$recencyQ.", 
                         ".$frequencyQ.", c
                         ".$monetaryQ.", 
                         ".$recencyQ." + ".$frequencyQ." + ".$monetaryQ." 
                    FROM fos_user fu
                    LEFT JOIN orders o ON o.user_id = fu.id AND o.order_status = '".OrderService::$status_completed."'
                  GROUP BY fu.id";

        try {
            $connection->executeUpdate("TRUNCATE report_rfm");

            $connection->executeUpdate($query);


            
//            $timeSpent = microtime(true) - $this->_timeStart;
//            $output->writeln(sprintf('<info>%d messages sent in %0.2f seconds</info>', $count, $timeSpent));
            // Log performance data
//            $logger->alert(sprintf(
//                '[Performance] %s %d in %0.2f seconds',
//                $this->getDescription(),
//                $count,
//                $timeSpent
//            ));
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>Sorry, lazy programmer left a bug :(</error>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
        } catch (\Exception $e) {
            $output->writeln('<error>Mayday mayday, an error knocked the process down.</error>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
        }
    }
}