<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Food\OrderBundle\Command\Decorators\OrderNavisionFullSyncDecorator;

class OrderNavisionFullSyncCommand extends ContainerAwareCommand
{
    use OrderNavisionFullSyncDecorator;

    const COMMAND = 'order:navision:full_sync';
    const NOT_DRY_RUN = 'not-dry-run';

    protected function configure()
    {
        $this
            ->setName(static::COMMAND)
            ->setDescription('Synchronize accounting data from orders with Navision')
            ->addOption(
                static::NOT_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Execute real synchronization, not just output operations'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $notDryRun = $input->getOption(static::NOT_DRY_RUN);

        // process
        $success = $this->sync($notDryRun, $output);

        // output result
        $output->writeln(sprintf('Order synchronization %s.',
                                 $success ? '<fg=green>succeeded</fg=green>' :
                                            '<fg=red>failed</fg=red>'));
    }
}
