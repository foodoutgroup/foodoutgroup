<?php
namespace Food\OrderBundle\Command;

use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NavSyncCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:nav:sync')
            ->setDescription('Sync navision order status')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'No statuses will be updated. Just pure output'
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
            $orderService = $this->getContainer()->get('food.order');
            $navService = $this->getContainer()->get('food.nav');

            $orders = $em->getRepository('FoodOrderBundle:Order')->getCurrentNavOrders(null, false, false);

            if (!empty($orders) && count($orders) > 0) {
                $navOrders = $navService->getRecentNavOrders($orders);
                $ordersFromNav = $navService->getImportedOrdersStatus($orders);

                $navOrders = $navOrders + $ordersFromNav;

                foreach ($navOrders as $orderId => $orderData) {
                    $order = $orderService->getOrderById($orderId);
                    if (!$order instanceof Order) {
                        throw new \Exception('Order from nav not found in local system. Local ID: '.$orderId.' Nav ID:'.$orderData['Order No_']);
                    }

                    // Localiu orderiu, kurie paskirti - neukeiciam pagal nava... localus tvarkomi lokaliai
                    if ($order->getOrderStatus() == OrderService::$status_assiged && !$order->getOrderFromNav()) {
                        continue;
                    }

                    $output->writeln(sprintf(
                        'Syncing order #%d. Local status: %s. Nav status: %s',
                        $orderId,
                        $order->getOrderStatus(),
                        $orderData['Delivery Status']
                    ));

                    // check if place of order changed and do something about it
                    $maybeOrderData = \Maybe($orderData);

                    $orderPlaceChanged = $navService->didOrderPlaceChange($maybeOrderData['Order No_']->val(''));

                    if (!empty($orderPlaceChanged)) {
                        // use $orderPlaceChanged['Store No_'] to set new place for $order
                        // for now we will have only debug code
                        @mail('jonas.s@foodout.lt', 'nav moved place debug', var_export($orderPlaceChanged, true), 'FROM: info@foodout.lt');
                    }

                    // Only update if not a dry-run
                    if (!$dryRun) {
                        $navService->changeOrderStatusByNav($order, $orderData);

                        if ($orderData['Delivery Status'] > 6 && !empty($orderData['Driver ID'])) {
                            $navService->setDriverFromNav($order, $orderData['Driver ID']);
                        }

                        if ($order->getOrderFromNav() && isset($orderData['Total Sum']) && !empty($orderData['Total Sum'])) {
                            if ($order->getTotal() != sprintf('%0.2f', $orderData['Total Sum'])) {
                                $order->setTotal($orderData['Total Sum']);
                            }
                        }

                        // Keep connection alive
                        if (!$em->isOpen()) {
                            $em = $em->create(
                                $em->getConnection(), $em->getConfiguration());
                        }

                        $em->persist($order);
                    }
                }

                // Save all modified orders if not a dry run
                if (!$dryRun) {
                    $em->flush();
                }
            }
        } catch (\Exception $e) {
            $output->writeln('Error syncing orders with Nav');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}
