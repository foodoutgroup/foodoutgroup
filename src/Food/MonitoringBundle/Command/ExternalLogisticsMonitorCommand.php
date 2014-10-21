<?php
namespace Food\MonitoringBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExternalLogisticsMonitorCommand
 * @package Food\MonitoringBundle\Command
 */
class ExternalLogisticsMonitorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('monitoring:logistics:logtime')
            ->setDescription('Check if orders are exported to logistics system')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $monitoringService = $this->getContainer()->get('food.monitoring');
        $critical = false;

        try {
            $logisticProblems = $monitoringService->getLogisticsSyncProblems();

            if ($logisticProblems['unsent'] > 0 || $logisticProblems['error']['count'] > 0) {
                $critical = true;
                $text = '<error>ERROR: ';

                if ($logisticProblems['unsent'] > 0) {
                    $text .= sprintf(
                        '%d unsent orders to logistics! ',
                        $logisticProblems['unsent']
                    );
                }

                if ($logisticProblems['error']['count'] > 0) {
                    $errorText =$logisticProblems['error']['lastError'];
                    if (mb_strlen($logisticProblems['error']['lastError']) > 20) {
                        $errorText = mb_substr($logisticProblems['error']['lastError'], 0, 20);
                    }

                    $text .= sprintf(
                        '%d errors when sent to logistics. Last error "%s"!',
                        $logisticProblems['error']['count'],
                        $errorText
                    );
                }

                $text .= '</error>';
            } else {
                $text = '<info>OK: all orders are sent to logistics platform</info>';
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
