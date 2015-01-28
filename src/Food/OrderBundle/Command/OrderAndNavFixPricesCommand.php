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

            if ($options->dryRun) {
                $output->writeln('skipped');
            } else {
                $order = $this->updateTotal($order, $data['posted_total'], $services);

                $output->writeln('success');
            }

            $navInvoice = $services->nav->selectNavInvoice($order);

            // 2. update Navision invoices
            $output->write('[Order ID] = ' . $order->getId() . ': update NAV invoice [Food Amount With VAT] from ' . number_format($navInvoice['Food Amount With VAT'], 2, '.', '') . ' to ' . $data['posted_total'] . '.. ');

            if ($options->dryRun ||
                empty($navInvoice) ||
                empty($navInvoice['Food Amount With VAT']))
            {
                $output->writeln('skipped');
            } else {
                $updateWith = ['Food Amount With VAT' => $data['posted_total']];
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
                     ->andWhere('o.total != p.total')
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

    protected function trimIfAllColumns(array $orderHas)
    {
        if (empty($orderHas)) {
            return $orderHas;
        }

        $resultData = [];

        foreach ($orderHas as $key => $value) {
            if (empty($value)) {
                continue;
            }

            $resultData[$key] = $value;
        }

        return $resultData;
    }

    protected function trimIfOnlyColumn($onlyColumn, array $data)
    {
        // nothing has changed
        if (!$onlyColumn) {
            return $data;
        }

        $data = empty($data[$onlyColumn])
                ? []
                : [$onlyColumn => $data[$onlyColumn]];

        return $data;
    }

    protected function generateMessage(array $data, $order, $success, $dryRun)
    {
        $maybeData = \Maybe($data);
        $mssqlColumnNames = array_keys($data['orderHas']);
        $cols = [];

        foreach ($mssqlColumnNames as $column) {
            $cols[] = sprintf('[%s] from "%s" to "%s"',
                              $column,
                              $maybeData['invoiceHas'][$column]->val(''),
                              $maybeData['orderHas'][$column]->val(''));
        }

        $message = sprintf('[Order ID] = %s: Updating columns %s .. %s',
                            $order->getId(),
                            implode(', ', $cols),
                            $dryRun ? 'skipped' : ($success ? 'success' : 'failure'));

        return $message;
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
