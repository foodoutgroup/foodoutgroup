<?php

namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Food\OrderBundle\Entity\PostedDeliveryOrders;

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

        $this->fillPostedOrders($options->from, $options->to, $services);
        $orderData = $this->getOrders($options->from, $options->to, $services);

        foreach ($orderData as $data) {
            $order = $this->findOrder($data['id'], $services);

            // 1. update orders
            $output->write('[Order ID] = ' . $order->getId() . ': updating total from ' . $order->getTotal() . ' to ' . $data['posted_total'] . ', delivery total from ' . $order->getDeliveryPrice() . ' to ' . $data['delivery'] . '.. ');

            $skipTotal = $order->getTotal() == $data['posted_total'];
            $skipDeliveryTotal = $order->getDeliveryPrice() == $data['delivery'];
            $skipPaymentMethod = !(2 == $data['tender_type']);

            if ($options->dryRun || ($skipTotal && $skipDeliveryTotal && $skipPaymentMethod)) {
                $output->writeln('skipped');
            } else {
                $updateData = new \StdClass();
                $updateData->total = $data['posted_total'];
                $updateData->deliveryTotal = $data['delivery'];

                if (2 == $data['tender_type'] && 'local.card' != $order->getPaymentMethod()) {
                    $updateData->paymentMethod = 'local.card';
                }

                $order = $this->update($order, $updateData, $services);

                $output->writeln('success');
            }

            $navInvoice = $services->nav->selectNavInvoice($order);

            if (empty($navInvoice)) {
                $output->writeln('[Order ID] = ' . $order->getId() . ': has no Navision invoice');

                continue;
            }

            // 2. update Navision invoices
            $foodAmount = $this->calculateFoodTotal($data['posted_total'], $order->getDeliveryPrice(), $order->getAdminFee());
            $deliveryAmount = number_format($data['delivery'], 2, '.', '');

            $output->write('[Order ID] = ' . $order->getId() . ': update NAV invoice [Food Amount With VAT] from ' . number_format($navInvoice['Food Amount With VAT'], 2, '.', '') . ' to ' . $foodAmount . ', [Delivery Amount With VAT] from ' . number_format($navInvoice['Delivery Amount With VAT'], 2, '.', '') . ' to ' . $deliveryAmount . '.. ');

            $skipTotal = number_format($navInvoice['Food Amount With VAT'], 2, '.', '') == $foodAmount;
            $skipDeliveryTotal = number_format($navInvoice['Delivery Amount With VAT'], 2, '.', '') == $deliveryAmount;
            $skipPaymentMethod = $navInvoice['Payment Method Type'] == $order->getPaymentMethod();

            if ($options->dryRun ||
                empty($navInvoice) ||
                empty($navInvoice['Food Amount With VAT']) ||
                ($skipTotal && $skipDeliveryTotal && $skipPaymentMethod))
            {
                $output->writeln('skipped');
            } else {
                $updateWith = ['Food Amount With VAT' => $foodAmount,
                               'Delivery Amount With VAT' => $deliveryAmount];

                if (!$skipPaymentMethod && 2 == $data['tender_type']) {
                    $updateWith['Payment Method Type'] = 'local.card';
                    $updateWith['Payment Method Code'] = 'CC';
                }

                $success = $services->nav->updateNavInvoice($order, $updateWith);

                $output->writeln(!empty($success) ? 'success' : 'failure');
            }
        }

        $output->writeln('End of operation.');
    }

    protected function getOrders($from, $to, \StdClass $services)
    {
        $qb = $services->em->createQueryBuilder();

        $params = ['from' => $from, 'to' => $to, 'city' => 'Vilnius']; //todo MULTI-L Kodėl tik Vilnius?

        $result = $qb->select('o.id, p.total AS posted_total, p.delivery, p.tender_type')
                     ->from('FoodOrderBundle:Order', 'o')
                     ->innerJoin('FoodOrderBundle:PostedDeliveryOrders', 'p', 'WITH', 'p.no = o.navDeliveryOrder')
                     ->where('o.order_date >= :from')
                     ->andWhere('o.order_date <= :to')
                     ->andWhere($qb->expr()->isNotNull('o.sfSeries'))
                     ->andWhere($qb->expr()->isNotNull('o.sfNumber'))
                     ->andWhere('o.place_point_city = :city')
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

    protected function update($order, \StdClass $data, \StdClass $services)
    {
        $order->setTotal($data->total);
        $order->setDeliveryPrice($data->deliveryTotal);

        if (!empty($data->paymentMethod)) {
            $order->setPaymentMethod($data->paymentMethod);
        }

        $services->em->flush();

        return $order;
    }

    protected function calculateFoodTotal($postedTotal, $deliveryPrice, $adminFee = 0)
    {
        if (!is_numeric($postedTotal) || !is_numeric($deliveryPrice) || !is_numeric($adminFee)) {
            throw new \InvalidArgumentException('$postedTotal or $deliveryPrice or $adminFee is not numeric, but it should be.');
        }

        if (0.0 == $postedTotal) {
            return '0.01';
        }

        return number_format($postedTotal - $deliveryPrice - $adminFee, 2, '.', '');
    }

    protected function fillPostedOrders($from, $to, \StdClass $services)
    {
        $postedOrdersFromNav = $services->nav->getPostedOrders($from, $to);

        $result = $services->em->transactional(function($em) use ($postedOrdersFromNav) {
            foreach ($postedOrdersFromNav as $value) {
                $entities = $em->getRepository('FoodOrderBundle:PostedDeliveryOrders')->findBy(['no' => $value['order_no']]);
                $entities = new ArrayCollection($entities);

                $entity = $entities->first();

                if (empty($entity)) {
                    $entity = new PostedDeliveryOrders();
                }

                $deliveryTotal = 2 == $value['tender_type']
                                 ? $value['delivery_cc_amount']
                                 : $value['delivery_total'];

                $entity->setNo($value['order_no']);
                $entity->setOrderDate(new \DateTime($value['order_date']));
                $entity->setTotal($value['total']);
                $entity->setDelivery($deliveryTotal);
                $entity->setTenderType($value['tender_type']);

                $entity = $em->merge($entity);
                $em->persist($entity);
            }
        });

        return $result;
    }
}
