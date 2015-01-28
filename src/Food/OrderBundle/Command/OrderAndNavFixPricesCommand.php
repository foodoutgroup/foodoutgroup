<?php

namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderAndNavFixPricesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:nav:fix_prices')
            ->setDescription('Compare orders table to nav_posted_delivery_orders and fix prices. Also update Navision invoices.')
            ->addOption(
                'not-dry-run',
                null,
                InputOption::VALUE_NONE,
                'Execute real thing'
            )
            ->addOption(
                'from',
                null,
                InputOption::VALUE_REQUIRED,
                'Date (yyyy-mm-dd hh:mm:ss)'
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'Date (yyyy-mm-dd hh:mm:ss)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $this->getOptions($input);
        $services = $this->getServices();

        $this->checkOptions($options);

        $orderData = $this->getOrders($options->from, $options->to, $services);

        foreach ($orderData as $data) {
            $order = $this->findOrder($data['id'], $services);

            // 1. update orders
            $output->write('[Order ID] = ' . $order->getId() . ': updating total from ' . $order->getTotal() . ' to ' . $data['posted_total'] . '.. ');

            if ($options->dryRun || $order->getTotal() == $data['posted_total']) {
                $output->writeln('skipped');
            } else {
                $order = $this->updateTotal($order, $data['posted_total'], $services);

                $output->writeln('success');
            }

            $navInvoice = $services->nav->selectNavInvoice($order);

            // 2. update Navision invoices
            $foodAmount = number_format($data['posted_total'] - $order->getDeliveryPrice(), 2, '.', '');
            $output->write('[Order ID] = ' . $order->getId() . ': update NAV invoice [Food Amount With VAT] from ' . number_format($navInvoice['Food Amount With VAT'], 2, '.', '') . ' to ' . $foodAmount . '.. ');

            if ($options->dryRun ||
                empty($navInvoice) ||
                empty($navInvoice['Food Amount With VAT']) ||
                number_format($navInvoice['Food Amount With VAT'], 2, '.', '') == $foodAmount)
            {
                $output->writeln('skipped');
            } else {
                $updateWith = ['Food Amount With VAT' => $foodAmount];
                $success = $services->nav->updateNavInvoice($order, $updateWith);

                $output->writeln(!empty($success) ? 'success' : 'failure');
            }
        }

        $output->writeln('End of operation.');
    }

    protected function getOrders($from, $to, \StdClass $services)
    {
        $qb = $services->em->createQueryBuilder();

        $params = ['from' => $from, 'to' => $to];

        $result = $qb->select('o.id, p.total AS posted_total')
                     ->from('FoodOrderBundle:Order', 'o')
                     ->innerJoin('FoodOrderBundle:PostedDeliveryOrders', 'p', 'WITH', 'p.no = o.navDeliveryOrder')
                     ->where('o.order_date >= :from')
                     ->andWhere('o.order_date <= :to')
                     ->andWhere($qb->expr()->isNotNull('o.sfSeries'))
                     ->andWhere($qb->expr()->isNotNull('o.sfNumber'))
                     ->andWhere('p.total != 0')
                     ->setParameters($params)
                     ->getQuery()
                     ->getResult();

        return $result;
    }

    protected function getOptions(InputInterface $input)
    {
        $options = new \StdClass();

        $options->dryRun = !$input->getOption('not-dry-run');
        $options->from = $input->getOption('from');
        $options->to = $input->getOption('to');

        return $options;
    }

    protected function getServices()
    {
        $container = $this->getContainer();

        $services = new \StdClass();

        $services->nav = $container->get('food.nav');
        $services->em = $container->get('doctrine.orm.entity_manager');

        // critical in batch sql operations! disabling will prevent memory leaks.
        $services->em->getConnection()->getConfiguration()->setSQLLogger(null);

        return $services;
    }

    protected function checkOptions(\StdClass $options)
    {
        if (empty($options->from) || empty($options->to)) {
            $message = 'Please specify --from="yyyy-mm-dd hh:mm:ss" and --to="yyyy-mm-dd hh:mm:ss"';

            throw new \Exception($message);
        }
    }

    protected function findOrder($id, \StdClass $services)
    {
        if (empty($id)) {
            return null;
        }

        $order = $services->em
                          ->getRepository('FoodOrderBundle:Order')
                          ->find($id);

        return $order;
    }

    protected function updateTotal($order, $newTotal, \StdClass $services)
    {
        $order->setTotal($newTotal);

        $services->em->flush();

        return $order;
    }
}
