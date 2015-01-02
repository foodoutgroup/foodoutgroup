<?php

namespace Food\MonitoringBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NavSoapAccessibleCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('monitoring:soap:accessible')
            ->setDescription('Check if Navision SOAP web services are alive.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // services
        $nav = $this->getContainer()->get('food.nav');

        // check if web services are alive
        list($critical, $text) = $nav->areWebServicesAlive();

        // finally
        $output->writeln($text);

        if ($critical) {
            return 2;
        }

        return 0;
    }
}
