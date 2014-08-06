<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderReminderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:unaccepted_reminder')
            ->setDescription('Send reminders for unaccepted orders')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $orderService = $this->getContainer()->get('food.order');
            $repo = $this->getContainer()->get('doctrine')->getRepository('FoodOrderBundle:Order');

            $orders = $repo->getForgottenOrders();

            if ($orders) {
                foreach($orders as $forgottenOrder) {
                    $order = $orderService->getOrderById($forgottenOrder['id']);
                    $order->setReminded(true);
                    $orderService->saveOrder();

                    $orderService->informPlace(true);

                    $output->writeln('Reminder message sent to: '.$order->getPlaceName().' for order #'.$order->getId());
                }
            }
        } catch (\Exception $e) {
            $output->writeln('Error generating report');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}
