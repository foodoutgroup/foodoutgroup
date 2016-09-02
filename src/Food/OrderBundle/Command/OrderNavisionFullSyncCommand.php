<?php
namespace Food\OrderBundle\Command;

use Doctrine\DBAL\LockMode;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderNavisionFullSyncCommand extends ContainerAwareCommand
{
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

    protected function sync($notDryRun = false, OutputInterface $output)
    {
        mb_internal_encoding('utf-8');

        // services
        $container = $this->getContainer();
        $navService = $container->get('food.nav');
        $em = $container->get('doctrine.orm.entity_manager');

        // get unsynced orders
        $unsyncedEntities = $navService->getUnsyncedOrderData();
        $unsyncCount = count(\Maybe($unsyncedEntities)->val([]));
        $failedSyncCount = 0;


        $output->writeln(
            sprintf('Got <fg=magenta>%d</fg=magenta> unsynced entities.',
                $unsyncCount));

        foreach ($unsyncedEntities as $entity) {
            try {
                $em->lock($entity, LockMode::OPTIMISTIC);

                $success = false;
                $data = $navService->getOrderDataForNavLocally(
                    $entity->getOrderId());

                if ($notDryRun) {
                    $timestamp = $entity->getSyncTimestamp();
                    $orderExists = $navService->orderExists($data->id);

                    if (empty($timestamp) && !$orderExists) {
                        $success = $navService->insertOrder($data);
                    } else {
                        $success = $navService->updateOrder($data);
                    }
                }

                if ($success) {
                    $entity->setIsSynced(true)
                        ->setSyncTimestamp(new \DateTime())
                    ;

                    if ($notDryRun) {
                        $output->writeln('Flushing changes in entity to local database, id: ' . $data->id);
                        $em->flush();
                    }
                } else {
                    $failedSyncCount++;
                }
            } catch (\Exception $e) {
                $failedSyncCount++;
                $output->writeln(sprintf('Got exception: %s', $e->getMessage()));
            }
        }

        $output->writeln(sprintf('Number of synced entities is <fg=magenta>%d</fg=magenta>.',
            $unsyncCount - $failedSyncCount));

        return true;
    }

    protected function findOrder($id)
    {
        return $this->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository('FoodOrderBundle:Order')
            ->find($id)
            ;
    }
}
