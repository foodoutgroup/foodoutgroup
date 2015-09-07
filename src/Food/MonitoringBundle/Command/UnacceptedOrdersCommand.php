<?php
namespace Food\MonitoringBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UnacceptedOrdersCommand
 * @package Food\MonitoringBundle\Command
 */
class UnacceptedOrdersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('monitoring:order:unaccepted')
            ->setDescription('Check there are unaccepted orders')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $monitoringService = $this->getContainer()->get('food.monitoring');
        $critical = false;

        try {
            $unacceptedOrders = $monitoringService->getUnacceptedOrders();
            $this->getContainer()->get('doctrine')->getConnection()->close();
            $ordersCount = count($unacceptedOrders);

            if ($ordersCount > 0) {
                $orderIds = array();

                foreach($unacceptedOrders as $order) {
                    $orderIds[] = $order->getId();
                }

                $text = sprintf(
                    '<error>ERROR: %d unaccepted orders! Ids: %s</error>',
                    $ordersCount,
                    implode(', ', $orderIds)
                );
                $critical = true;
            } else {
                $text = '<info>OK: all orders are accepted</info>';
            }
        } catch (\Exception $e) {
            $text = '<error>ERROR: Error in unaccepted orders check: '.$e->getMessage().'</error>';
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