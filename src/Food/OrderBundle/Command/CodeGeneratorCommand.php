<?php

namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CodeGeneratorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:generate:code')
            ->setDescription('Send generated codes to order range')
            ->addOption(
                'from',
                null,
                InputOption::VALUE_REQUIRED,
                'Starting order id'
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'Last order id'
            )
            ->addOption(
                'whole',
                null,
                InputOption::VALUE_NONE,
                'Send to whole range or only completed'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $from = $input->getOption('from');
        $to = $input->getOption('to');
        $whole = $input->getOption('whole');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $orderService = $this->getContainer()->get('food.order');
        $orders = $em->getRepository('FoodOrderBundle:Order')->getOrdersInRange($from, $to);

        $output->write(sprintf("Found %d orders", count($orders)));
        $processedOrders = 0;

        foreach ($orders as $order) {
            try {
                if (!$whole && $order->getOrderStatus() != $orderService::$status_completed) {
                    continue;
                }

                if ($orderService->codeGenerator($order)) {
                    ++$processedOrders;
                }
            } catch (\Exception $e) {
                $output->writeln('Error on order: '. $order->getId());
                $output->writeln('Error: '.$e->getMessage());
                $output->writeln('Trace: '."\n".$e->getTraceAsString());
                throw $e;
            }
        }

        $output->writeln('Orders processed: '.$processedOrders);
    }
}