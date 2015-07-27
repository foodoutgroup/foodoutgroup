<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CloseOrdersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:close')
            ->setDescription('Close hung orders that should be delivered')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Orders wont be closed. Just pure output'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $em = $this->getContainer()->get('doctrine')->getManager();
            $orderService = $this->getContainer()->get('food.order');
            $orders = $em->getRepository('FoodOrderBundle:Order')->getUnclosedOrders();

            $output->write(sprintf("Found %d unclosed orders", count($orders)));
            $processedOrders = 0;

            foreach ($orders as $order) {
                $output->writeln(
                    sprintf('Order id: #%d Calculated delivery time: %s', $order['id'], $order['delivery_time'])
                );
                if (!$input->getOption('dry-run')) {
                    $order = $orderService->getOrderById($order['id']);

                    if (!$order) {
                        $this->getContainer()->get('logger')->error('CloseOrdersCommand could not open order #'.$order['id']);
                        continue;
                    }

                    $orderService->logOrder($order, 'auto_close', 'Order auto close command closed order');
                    $orderService->statusCompleted('auto_close_order_command');

                    $em->persist($order);

                    // log order data (if we have listeners)
                    $orderService->markOrderForNav($order);
                }
                $processedOrders++;
            }

            if ($processedOrders > 0) {
                $em->flush();
            }

            $output->writeln('Orders processed: '.$processedOrders);
        } catch (\Exception $e) {
            $output->writeln('Error when closing order');
            $output->writeln('Error: '.$e->getMessage());
            $output->writeln('Trace: '."\n".$e->getTraceAsString());
            throw $e;
        }
    }
}
