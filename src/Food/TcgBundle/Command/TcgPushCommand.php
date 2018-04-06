<?php

namespace Food\TcgBundle\Command;

use Food\TcgBundle\Entity\TcgLog;
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

        $orders = $orderRepo->getOrdersUnconfirmed(null,false,true);

        try {
            if ($orders) {
                foreach ($orders as $order) {
                    if ($order->getPlace()->getTcgCall()) {
                        $isLate = $orderService->isLate($order);

                        if ($isLate) {
                            $loggedRecords = $tcgRepo->getByPhoneSorted($order, 'DESC');

                            $check = isset($loggedRecords[0]);

                            if (($check && $this->checkDateDifference($loggedRecords[0])) or !$check) {


                                $logRecord = $tcgService->createLog($order);
                                $response = $tcgService->sendPush($order);

                                if (key($response) == 201) {
                                    $logRecord->setSent(true);

                                } else {
                                    $logRecord->setError($response[key($response)]);
                                }

                                $tcgService->saveLog($logRecord);

                            }
                        }
                    }
                }
            }


        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        $this->getContainer()->get('doctrine')->getConnection()->close();
    }

    public function checkDateDifference(TcgLog $logRecord)
    {
        $date = clone $logRecord->getSubmittedAt();
        $date->modify('+ 4 minutes');
        $diff = date_diff(new \DateTime(), $date);

        return $diff->invert;
    }
}