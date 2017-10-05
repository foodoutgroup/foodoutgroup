<?php

namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixAccountOrderDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:fix:account:data')
            ->setDescription('fix problem with completed orders')
            ->addOption(
                'date_from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Date (yyyy-mm-dd)'
            )
            ->addOption(
                'date_to',
                null,
                InputOption::VALUE_OPTIONAL,
                'Date (yyyy-mm-dd)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $dateFrom = $input->getOption('date_from');
        $dateTo = $input->getOption('date_to');
        $emRepo = $this->getContainer()->get('doctrine')->getManager();
        $orderRepository = $emRepo->getRepository('FoodOrderBundle:Order');
        $orderAccDataRepository = $emRepo->getRepository('FoodOrderBundle:OrderAccData');

        $em = $this->getContainer()->get('doctrine')->getManager()->getConnection();
        $query = "SELECT * from order_acc_data WHERE date <= '".$dateTo."' and date >= '".$dateFrom."' and is_delivered = 0";


        $stmt = $em->prepare($query);
        $stmt->execute();
        $orderAccData = $stmt->fetchAll();


        try {
            if (!empty($orderAccData)) {

                foreach ($orderAccData as $orderData) {

                    $order = $orderRepository->findOneBy(['id'=>$orderData['order_id']]);
                    if(($order->getOrderStatus() == 'completed' || $order->getOrderStatus() == 'canceled_produced' || $order->getOrderStatus() == 'finished') && $order->getPaymentStatus() == 'complete' ){
                        $accRecord = $orderAccDataRepository->find($orderData['id']);
                        $accRecord->setIsDelivered(1);
                        $accRecord->setIsSynced(0);
                        $accRecord->setSyncTimestamp(null);
                        $emRepo->persist($accRecord);
                        $emRepo->flush();
                        echo $orderData['order_id'];
                        echo '<br>';
                    }


                }
            }

            $this->getContainer()->get('doctrine')->getConnection()->close();
        } catch (\Exception $e) {
            $message = 'An error happened while running Account data execution command. Error: '.$e->getMessage();
            $output->writeln('<error>'.$message.'</error>');

            throw $e;
        }
    }
}
