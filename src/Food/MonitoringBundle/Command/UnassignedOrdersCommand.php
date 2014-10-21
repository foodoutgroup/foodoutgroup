<?php
namespace Food\MonitoringBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UnassignedOrdersCommand
 * @package Food\MonitoringBundle\Command
 */
class UnassignedOrdersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('monitoring:order:unassigned')
            ->setDescription('Check if there are unassigned orders')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $monitoringService = $this->getContainer()->get('food.monitoring');
        $critical = false;

        try {
            $unassignedOrders = $monitoringService->getUnassignedOrders();
            $ordersCount = count($unassignedOrders);

            if ($ordersCount > 0) {
                $orderIds = array();

                foreach($unassignedOrders as $order) {
                    $orderIds[] = $order->getId();
                }

                $text = sprintf(
                    '<error>ERROR: %d unassigned orders! Ids: %s</error>',
                    $ordersCount,
                    implode(', ', $orderIds)
                );
                $critical = true;
            } else {
                $text = '<info>OK: all orders are assigned</info>';
            }
        } catch (\Exception $e) {
            $text = '<error>ERROR: Error in unassigned orders check: '.$e->getMessage().'</error>';
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