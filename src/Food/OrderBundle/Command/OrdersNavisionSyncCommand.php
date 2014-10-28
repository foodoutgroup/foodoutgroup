<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrdersNavisionSyncCommand extends ContainerAwareCommand
{
    const COMMAND = 'orders:navision:sync';

    protected function configure()
    {
        $this
            ->setName(static::COMMAND)
            ->setDescription('Synchronize accounting data from orders with Navision')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Do not execute real synchronization, just output operations'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // 
    }
}
