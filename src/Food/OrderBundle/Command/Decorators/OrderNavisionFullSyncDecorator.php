<?php

namespace Food\OrderBundle\Command\Decorators;

use Doctrine\DBAL\LockMode;
use Symfony\Component\Console\Output\OutputInterface;

trait OrderNavisionFullSyncDecorator
{
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

        try {
            foreach ($unsyncedEntities as $entity) {
                $em->lock($entity, LockMode::OPTIMISTIC);

                $success = false;
                $data = $navService->getOrderDataForNavLocally(
                    $entity->getOrderId());

                if ($notDryRun) {
                    $timestamp = $entity->getSyncTimestamp();
                    if (empty($timestamp)) {
                        $success = $navService->insertOrder($data);
                    } else {
                        $success = $navService->updateOrder($data);
                    }
                }

                if ($success) {
                    $entity->setIsSynced(true)
                           ->setSyncTimestamp(new \DateTime());
                } else {
                    $failedSyncCount++;
                }
            }

            if ($notDryRun) {
                $output->writeln('Flushing changes in entities to local database');
                $em->flush();
            }
        } catch (\Exception $e) {
            $output->writeln(sprintf('Got exception: %s', $e->getMessage()));
            return false;
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
                    ->find($id);
    }
}
