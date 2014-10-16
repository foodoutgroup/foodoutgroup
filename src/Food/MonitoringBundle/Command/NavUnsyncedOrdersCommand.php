<?php
namespace Food\MonitoringBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class NavUnsyncedOrdersCommand
 * @package Food\MonitoringBundle\Command
 */
class NavUnsyncedOrdersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('monitoring:nav:unsynced')
            ->setDescription('Check there are unsynced orders')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $critical = false;

        try {
            $em = $this->getContainer()->get('doctrine')->getManager();
            $orders = $em->getRepository('FoodOrderBundle:Order')->getCurrentNavOrders();
            $navService = $this->getContainer()->get('food.nav');

            echo "\nOrder count: ".count($orders)."\n";
            if (!empty($order) && count($orders) > 0) {
                $navOrders = $navService->getRecentNavOrders($orders);
                $ordersCount = count($orders);
                $navOrdersCount = count($navOrders);

                if ($ordersCount != $navOrdersCount) {
                    $unSyncedOrderIds = array();

                    foreach($orders as $order) {
                        if (!isset($navOrders[$order->getId()])) {
                            $orderIds[] = $order->getId();
                        }
                    }

                    $text = sprintf(
                        '<error>ERROR: %d unsynced orders in Nav! Ids: %s</error>',
                        ($ordersCount-$navOrdersCount),
                        implode(', ', $unSyncedOrderIds)
                    );
                    $critical = true;
                }
            }

            if (!$critical) {
                $text = '<info>OK: all orders are synced</info>';
            }
        } catch (\Exception $e) {
            $text = '<error>ERROR: Error in unsynced nav orders check: '.$e->getMessage().'</error>';
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
