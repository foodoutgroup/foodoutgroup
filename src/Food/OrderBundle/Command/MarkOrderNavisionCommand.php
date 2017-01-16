<?php

namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MarkOrderNavisionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:nav:mark')
            ->setDescription('Mark order range to navision')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Execute real thing'
            )
            ->addOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Date (yyyy-mm-dd hh:mm:ss)'
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_OPTIONAL,
                'Date (yyyy-mm-dd hh:mm:ss)'
            )
            ->addOption(
                'id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Order id'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $this->getOptions($input);
        $services = $this->getServices();

        $orderIds = $this->getOrders($options, $services);

        if ($options->dryRun) {
            $output->writeln('Dry run - nothing will be marked');
        }

        foreach ($orderIds as $id) {
            $order = $this->findOrder($id, $services);

            if (!$options->dryRun) {
                $services->order->markOrderForNav($order);
            }

            $output->writeln('Order id: ' . $id['id']);
        }
    }

    protected function getOrders($options, \StdClass $services)
    {
        $qb = $services->em->createQueryBuilder();

        $params = [];

        if (!empty($options->from)) {
            $params['from'] = $options->from;
            $qb->andWhere('o.order_date >= :from');
        }
        if (!empty($options->to)) {
            $params['to'] = $options->to;
            $qb->andWhere('o.order_date <= :to');
        }
        if (!empty($options->id)) {
            $params['id'] = $options->id;
            $qb->andWhere('o.id = :id');
        }

        $qb->select('o.id')
             ->from('FoodOrderBundle:Order', 'o')
             ->orderBy('o.id')
             ->setParameters($params);

        $result = $qb->getQuery()
                     ->getResult();

        return $result;
    }

    protected function getOptions(InputInterface $input)
    {
        $options = new \StdClass();

        $options->dryRun = $input->getOption('dry-run');
        $options->from = $input->getOption('from');
        $options->to = $input->getOption('to');
        $options->id = $input->getOption('id');

        if (empty($options->from) && empty($options->to) && empty($options->id)) {
            $message = 'Please specify at least one parameter: from, to, id';

            throw new \Exception($message);
        }

        return $options;
    }

    protected function getServices()
    {
        $container = $this->getContainer();

        $services = new \StdClass();

        $services->order = $container->get('food.order');
        $services->nav = $container->get('food.nav');
        $services->em = $container->get('doctrine.orm.entity_manager');

        // critical in batch sql operations! disabling will prevent memory leaks.
        $services->em->getConnection()->getConfiguration()->setSQLLogger(null);

        return $services;
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
