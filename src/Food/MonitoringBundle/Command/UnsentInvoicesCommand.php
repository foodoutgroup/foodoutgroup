<?php
namespace Food\MonitoringBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UnsentInvoicesCommand
 * @package Food\MonitoringBundle\Command
 */
class UnsentInvoicesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('monitoring:order:unsent_invoice')
            ->setDescription('Check if all invoices are generated and sent')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $monitoringService = $this->getContainer()->get('food.monitoring');
        $critical = false;

        try {
            $unsentInvoices = $monitoringService->getUnsentInvoices();
            $invoiceCount = count($unsentInvoices);

            if ($invoiceCount > 0) {
                $orderIds = array();

                foreach($unsentInvoices as $invoice) {
                    $orderIds[] = $invoice
                        ->getOrder()
                        ->getId();
                }

                $text = sprintf(
                    '<error>ERROR: %d unsent invoices! Order ID`s that are unsent: %s</error>',
                    $invoiceCount,
                    implode(', ', $orderIds)
                );
                $critical = true;
            } else {
                $text = '<info>OK: all invoices are generated and sent</info>';
            }
        } catch (\Exception $e) {
            $text = '<error>ERROR: Error in unsent invoice check: '.$e->getMessage().'</error>';
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