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
        $dryRun = !$input->getOption('not-dry-run');
        $from = $input->getOption('from');
        $to = $input->getOption('to');
        $onlyColumn = $input->getOption('only-column');

        if (empty($from) || empty($to)) {
            throw new \Exception('Please specify --from="yyyy-mm-dd hh:mm:ss" and --to="yyyy-mm-dd hh:mm:ss"');
        }

        $nav = $this->getContainer()->get('food.nav');
        $logger = $this->getContainer()->get('logger');

        $orders = $this->getOrders($from, $to);

        foreach ($orders as $order) {
            $navInvoice = $nav->selectNavInvoice($order);
            $comparison = $nav->compareNavInvoiceWithOrder($navInvoice, $order);

            $c = \Maybe($comparison);

            if ($onlyColumn && !is_null($c['orderHas'][$onlyColumn]->val())) {
                $data = [$onlyColumn => $c['orderHas'][$onlyColumn]->val('')];

                $success = $dryRun ? false : $nav->updateNavInvoice($order, $data);

                $message = sprintf('[Order ID] = %s: Updating column\'s [%s] value from "%s" to "%s" .. %s',
                                   $order->getId(),
                                   $onlyColumn,
                                   $c['invoiceHas'][$onlyColumn]->val(''),
                                   $c['orderHas'][$onlyColumn]->val(''),
                                   $success ? 'success' : 'failure');

                $output->writeln($message);
                $logger->info($message);
            } elseif (!$onlyColumn && !empty($comparison['orderHas'])) {
                $success = $dryRun ? false : $nav->updateNavInvoice($order, $comparison['orderHas']);

                $cols = [];

                foreach ($comparison['orderHas'] as $key => $value) {
                    $cols[] = sprintf('[%s] from "%s" to "%s"',
                                      $key,
                                      $c['invoiceHas'][$key]->val(''),
                                      $comparison['orderHas'][$key]);
                }

                $message = sprintf('[Order ID] = %s: Updating columns %s .. %s',
                                    $order->getId(),
                                    implode(', ', $cols),
                                    $success ? 'success' : 'failure');

                $output->writeln($message);
                $logger->info($message);
            }
        }
    }

    protected function getOrders($from, $to)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $qb = $em->createQueryBuilder();

        $result = $qb->select('o')
                     ->from('FoodOrderBundle:Order', 'o')
                     ->where('o.order_date >= :from')
                     ->andWhere('o.order_date < :to')
                     ->andWhere($qb->expr()->isNotNull('o.sfSeries'))
                     ->andWhere($qb->expr()->isNotNull('o.sfNumber'))
                     ->andWhere('o.deliveryType = :delivery_type')
                     ->setParameters(['from' => $from,
                                      'to' => $to,
                                      'delivery_type' => 'deliver'])
                     ->getQuery()
                     ->getResult();

        return $result;
    }
}
