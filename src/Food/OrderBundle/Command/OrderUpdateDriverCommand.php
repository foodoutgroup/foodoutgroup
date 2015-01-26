<?php

namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderUpdateDriverCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:update:driver')
            ->setDescription('Update drivers for orders')
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

        $orders = $this->getOrders($options->from, $options->to, $services);

        foreach ($orders as $order) {
            $code = $services->nav->getNavDriverCode($order['navDeliveryOrder'], $services);

            if (empty($code)) {
                continue;
            }

            $orderEntity = $this->findOrder($order['id'], $services);
            $driver = $this->findDriver($code, $services);

            if (empty($order)) {
                $output->writeln('Could not find order ' . $order['id']);
                continue;
            }

            if (empty($driver)) {
                $output->writeln('Could not find driver with nav code "' . $code . '"');
                continue;
            }

            if ($code != $order['navDriverCode']) {
                $output->write('Adjusting nav_driver_code for local order ' . $order['id'] . '.. ');

                // update orders nav_driver_code
                if ($options->dryRun) {
                    $output->writeln('skipped');
                } else {
                    $orderEntity->setNavDriverCode($code);
                    $output->writeln('success');
                }
            }

            $output->write('Setting driver ' . $driver->getId() . ' for order ' . $orderEntity->getId() . '.. ');

            if ($options->dryRun) {
                $output->writeln('skipped');
            } else {
                $orderEntity->setDriver($driver);
                $services->em->flush();

                $output->writeln('success');
            }
        }
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

        $services->em = $container->get('doctrine.orm.entity_manager');
        $services->nav = $container->get('food.nav');

        return $services;
    }

    protected function checkOptions(\StdClass $options)
    {
        if (empty($options->from) || empty($options->to)) {
            $message = 'Please specify --from="yyyy-mm-dd hh:mm:ss" and --to="yyyy-mm-dd hh:mm:ss"';

            throw new \Exception($message);
        }
    }

    protected function getOrders($from, $to, \StdClass $services)
    {
        $qb = $services->em->createQueryBuilder();

        $params = ['from' => $from,
                   'to' => $to,
                   'delivery_type' => 'deliver',
                   'order_from_nav' => 1];

        $result = $qb->select('o.id, o.navDeliveryOrder, o.navDriverCode')
                     ->from('FoodOrderBundle:Order', 'o')
                     ->where('o.order_date >= :from')
                     ->andWhere('o.order_date < :to')
                     ->andWhere($qb->expr()->isNotNull('o.sfSeries'))
                     ->andWhere($qb->expr()->isNotNull('o.sfNumber'))
                     ->andWhere($qb->expr()->isNull('o.driver'))
                     ->andWhere('o.orderFromNav = :order_from_nav')
                     ->andWhere('o.deliveryType = :delivery_type')
                     ->andWhere('o.navDriverCode != \'\'')
                     ->andWhere($qb->expr()->isNotNull('o.navDriverCode'))
                     ->setParameters($params)
                     ->getQuery()
                     ->getResult();

        return $result;
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

    protected function findDriver($code, \StdClass $services)
    {
        if (empty($code)) {
            return null;
        }

        $driver = $services->em
                          ->getRepository('FoodAppBundle:Driver')
                          ->findOneBy(['extId' => $code]);

        return $driver;
    }
}
