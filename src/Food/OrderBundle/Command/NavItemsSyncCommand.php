<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NavItemsSyncCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:nav:sync_items')
            ->setDescription('Sync items names')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $navService = $this->getContainer()->get('food.nav');
            $navService->syncDisDescription();

        } catch (\Exception $e) {
            $output->writeln('Error generating report');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}
