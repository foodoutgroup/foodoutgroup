<?php
namespace Food\OrderBundle\Command;

use Doctrine\ORM\OptimisticLockException;
use Food\AppBundle\Entity\Driver;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderSyncPlacePointCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->timeStart = microtime(true);

        $this
            ->setName('order:sync_place_point')
            ->setDescription('Sync place_point addresses')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'No statuses data will be updated'
            )
            ->addOption(
                'days-back',
                null,
                InputOption::VALUE_OPTIONAL,
                'How many days back'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dry-run');
        if ($dryRun) {
            $output->writeln('Dry-run. No updates will be performed');
        }
        try {
            $em = $this->getContainer()->get('doctrine')->getManager();
            $navService = $this->getContainer()->get('food.nav');
            $daysBack = $input->getOption('days-back');
            $ordersWithoutData = $navService->getOrdersWithoutPlacePointData($daysBack);
            foreach ($ordersWithoutData as $orderId) {
                $navService->updateOrderPlacePointInfo($orderId, !$dryRun);
            }
        } catch (OptimisticLockException $e) {
            $output->writeln('Failed saving changes for order one order - Order row was locked. Will try the next run');
        } catch (\Exception $e) {
            $output->writeln('Error syncing place points');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}