<?php
namespace Food\MonitoringBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UncompleteOrdersCommand
 * @package Food\MonitoringBundle\Command
 */
class NavServerAccessableCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('monitoring:nav:accessable')
            ->setDescription('Check if Navision SQL server is accessable')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $monitoringService = $this->getContainer()->get('food.monitoring');
        $critical = false;

        try {
            $orders = $monitoringService->getFewOrdersFromNav();

            $ordersCount = count($orders);

            if ($ordersCount == 0) {
                $text = '<error>ERROR: Navision returned zero orders - check connection!</error>';
                $critical = true;
            } else {
                $text = '<info>OK: Navision is accessable</info>';
            }
        } catch (\Exception $e) {
            $text = '<error>ERROR: Error in Navision accessability check: '.$e->getMessage().'</error>';
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