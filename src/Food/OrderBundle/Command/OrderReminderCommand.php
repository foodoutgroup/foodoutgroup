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
            $placeService = $this->getContainer()->get('food.places');

            foreach($orderService->getForgottenOrders() as $order) {
                $isZavalOn = $placeService->getZavalTime($order->getPlace());
                if (!$isZavalOn) {
                    $orderService->setOrder($order);
                    $order->setReminded(new \DateTime());
                    $orderService->saveOrder();

                    $orderService->informPlace(true);

                    $output->writeln('Reminder message sent to: ' . $order->getPlaceName() . ' for order #' . $order->getId());
                }
            }

            // Close DB connection. Dont leave crap
            $this->getContainer()->get('doctrine')->getConnection()->close();
        } catch (\Exception $e) {
            $output->writeln('Error generating report');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}
