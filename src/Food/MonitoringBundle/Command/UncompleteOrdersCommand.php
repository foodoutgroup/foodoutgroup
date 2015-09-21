<?php
namespace Food\MonitoringBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UncompleteOrdersCommand
 * @package Food\MonitoringBundle\Command
 */
class UncompleteOrdersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('monitoring:order:uncomplete')
            ->setDescription('Check there are uncomplete orders for yesterday')
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'If set, debug information will be logged'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $monitoringService = $this->getContainer()->get('food.monitoring');

        $from = new \DateTime(
            date("Y-m-d 00:00:01", strtotime("-4 day"))
        );
        $to = new \DateTime(
            date("Y-m-d 23:59:59", strtotime("-1 day"))
        );
        $critical = false;

        try {
            $unfinishedOrders = $monitoringService->getUnfinishedOrdersForRange($from, $to);
            $this->getContainer()->get('doctrine')->getConnection()->close();
            $ordersCount = count($unfinishedOrders);

            if ($ordersCount > 0) {
                $orderIds = array();

                foreach($unfinishedOrders as $order) {
                    $orderIds[] = $order->getId();
                }

                $text = sprintf(
                    '<error>ERROR: %d uncomplete orders! Ids: %s</error>',
                    $ordersCount,
                    implode(', ', $orderIds)
                );
                $critical = true;
            } else {
                $text = '<info>OK: all orders are completed successfuly</info>';
            }
        } catch (\Exception $e) {
            $text = '<error>ERROR: Error in unfinished orders check: '.$e->getMessage().'</error>';
            $output->writeln($text);

            throw $e;
        }

        $output->writeln($text);

        if ($critical) {
            return 2;
        }

        return 0;
    }
}