<?php

namespace Food\TcgBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TcgPushCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('tcg:push')
            ->setDescription('call unconfirmed order after 3 mins');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

                $orderRepo = $this->getContainer()->get('doctrine')->getRepository('FoodOrderBundle:Order');
        $tcgRepo = $this->getContainer()->get('doctrine')->getRepository('FoodTcgBundle:TcgLog');
        $tcgService = $this->getContainer()->get('food.tcg');
        $orderService = $this->getContainer()->get('food.order');

        $orders = $orderRepo->getOrdersUnconfirmed();

        try {
            if ($orders) {
                foreach ($orders as $order) {
                    $isLate = $orderService->isLate($order);
                    if($isLate){
                        $orderLogs = $tcgRepo->findBy(['orderId'=>$order->getId()]);
                        if(!$orderLogs){
                            $logRecord = $tcgService->createLog($order);
                            
                        }
                    }
                }
            }


        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        $this->getContainer()->get('doctrine')->getConnection()->close();


    }
}