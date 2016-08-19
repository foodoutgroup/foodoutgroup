<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PreorderStartCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:preorder:start')
            ->setDescription('Start preorders as normal orders')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Orders wont be started'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $em = $this->getContainer()->get('doctrine')->getManager();
            $orderService = $this->getContainer()->get('food.order');


            $orders = $em->getRepository('FoodOrderBundle:Order')->getPreOrders();

            $output->write(sprintf("Found %d pre-orders", count($orders)));
            $processedOrders = 0;

            foreach ($orders as $order) {
                $output->writeln(
                    sprintf('Order id: #%d Calculated delivery time: %s', $order['id'], $order['delivery_time'])
                );
                if (!$input->getOption('dry-run')) {
                    $order = $orderService->getOrderById($order['id']);

                    if (!$order) {
                        $this->getContainer()->get('logger')->error('PreOrder starter could not open order #'.$order['id']);
                        continue;
                    }

                    if ($order->getPlace()->getNavision()) {
                        $this->getContainer()->get('logger')->alert('PreOrder starter skipped order #'.$order['id'].' Nav orders should not be here. They are already posted');
                        continue;
                    }

                    $orderService->logOrder($order, 'pre-order', 'PreOrder started by cron as it was 1 hour left till delivery');
                    $orderService->statusNew('preOrder-start');

                    $em->persist($order);

                    if (!$order->getPlace()->getNavision()) {
                        $orderService->informPlace();
                    }
                }
                $processedOrders++;
            }

            if ($processedOrders > 0) {
                $em->flush();
                $this->getContainer()->get('doctrine')->getConnection()->close();
            }

            $output->writeln('Orders processed: '.$processedOrders);
        } catch (\Exception $e) {
            $output->writeln('Error when starting pre-order');
            $output->writeln('Error: '.$e->getMessage());
            $output->writeln('Trace: '."\n".$e->getTraceAsString());
            throw $e;
        }
    }
}