<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DriversToLogisticCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:logistic:drivers-send')
            ->setDescription('Send drivers to logistic system')
            ->addOption(
                'force-url',
                null,
                InputOption::VALUE_OPTIONAL,
                'If set, the request will be sent to the given url'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'dont send, just output orders, that should be sent'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $logisticsService = $this->getContainer()->get('food.logistics');
            $em = $this->getContainer()->get('doctrine')->getManager();
            $logger = $this->getContainer()->get('logger');
            $systemUrl = $input->getOption('force-url');
            if (empty($systemUrl)) {
                $systemUrl = $this->getContainer()->getParameter('logtime.driver_url');
            }
            $dryRun = false;

            $drivers = $em->getRepository('FoodAppBundle:Driver')->findAll();
            $deletedDrivers = $em->getRepository('FoodAppBundle:Driver')->findRecentlyDeleted();

            // Dont send if dry-run
            if ($input->getOption('dry-run')) {
                $output->writeln('Dry run - no drivers will be sent');
                $dryRun = true;
            }

            $output->writeln('Generating and sending XML for '.count($drivers).' drivers');
            $xml = $logisticsService->generateDriverXml($drivers, $deletedDrivers);

            if (!$dryRun) {
                $response = $logisticsService->sendToLogistics($systemUrl, $xml);

                // Put to log so we get notified by Nagios
                if ($response['status'] == 'error') {
                    $errorMsg = 'Sending driver xml to logistics failed. Error: '.$response['error'];
                    $logger->error($errorMsg);
                    $output->writeln('<error>'.$errorMsg.'</error>');
                } else {
                    $output->writeln('<info>Drivers successfuly sent</info>');
                }
            }

        } catch (\Exception $e) {
            $output->writeln('Error sending drivers to logistics');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}