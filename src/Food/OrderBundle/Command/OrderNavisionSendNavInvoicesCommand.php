<?php

namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderNavisionSendNavInvoicesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:nav:invoice:send')
            ->setDescription('Send Navision invoices')
            ->addOption(
                'not-dry-run',
                null,
                InputOption::VALUE_NONE,
                'Execute real thing'
            )
            ->addOption(
                'date',
                null,
                InputOption::VALUE_REQUIRED,
                'Date (yyyy-mm-dd)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $orderRepository = $em->getRepository('FoodOrderBundle:Order');
        $nav = $this->getContainer()->get('food.nav');
        $connection = $em->getConnection();

        // options
        $dryRun = !$input->getOption('not-dry-run');
        $date = $input->getOption('date');

        if (empty($date)) {
            throw new \Exception('Please specify --date="yyyy-mm-dd"');
        }

        $query = '
            SELECT o.id
            FROM orders o
            WHERE
                DATE(o.order_date) = ? AND
                o.order_status = \'completed\' AND
                o.payment_status = \'complete\' AND
                o.delivery_type = \'deliver\' AND
                o.sf_series IS NOT NULL AND
                o.sf_number IS NOT NULL
        ';

        $stmt = $connection->prepare($query);
        $stmt->bindValue(1, $date);
        $stmt->execute();

        $orderIds = $stmt->fetchAll();

        $output->writeln('Got ' . count($orderIds) . ' orders.');

        foreach ($orderIds as $orderId) {
            $order = $orderRepository->find($orderId);

            if (!$order) {
                continue;
            }

            $output->write('Found order ' . $order->getId() . '.. ');

            if ($dryRun) {
                $output->writeln('skipping.');
                continue;
            }

            $output->write('sending navision invoice.. ');

            $success = $nav->sendNavInvoice($order);

            $output->writeln($success ? 'success.' : 'failure.');
        }

        $output->writeln('Finished my job.');
    }
}
