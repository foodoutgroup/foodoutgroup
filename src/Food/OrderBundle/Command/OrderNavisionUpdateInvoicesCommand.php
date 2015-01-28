<?php

namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderNavisionUpdateInvoicesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:nav:invoice:update')
            ->setDescription('Update Navision invoices')
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
            ->addOption(
                'only-column',
                null,
                InputOption::VALUE_OPTIONAL,
                'Only update this MSSQL column'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $this->getOptions($input);
        $services = $this->getServices();

        $this->checkOptions($options);

        $orderIds = $this->getOrders($options->from, $options->to, $services);

        foreach ($orderIds as $id) {
            $order = $this->findOrder($id, $services);

            $navInvoice = $services->nav->selectNavInvoice($order);
            $comparison = $services->nav->compareNavInvoiceWithOrder($navInvoice, $order);
            $comparison['orderHas'] = $this->trimIfAllColumns($comparison['orderHas']);
            $comparison['orderHas'] = $this->trimIfOnlyColumn($options->onlyColumn, $comparison['orderHas']);

            if (empty($comparison['orderHas'])) {
                continue;
            }

            if ($options->dryRun) {
                $success = false;
            } else {
                $success = $services->nav->updateNavInvoice($order, $comparison['orderHas']);
            }

            $message = $this->generateMessage($comparison, $order, $success, $options->dryRun);

            $output->writeln($message);
        }
    }

    protected function getOrders($from, $to, \StdClass $services)
    {
        $qb = $services->em->createQueryBuilder();

        $params = ['from' => $from, 'to' => $to, 'delivery_type' => 'deliver'];

        $result = $qb->select('o.id')
                     ->from('FoodOrderBundle:Order', 'o')
                     ->where('o.order_date >= :from')
                     ->andWhere('o.order_date <= :to')
                     ->andWhere($qb->expr()->isNotNull('o.sfSeries'))
                     ->andWhere($qb->expr()->isNotNull('o.sfNumber'))
                     ->andWhere('o.deliveryType = :delivery_type')
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
        $options->onlyColumn = $input->getOption('only-column');

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
}
