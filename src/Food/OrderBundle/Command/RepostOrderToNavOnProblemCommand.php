<?php

namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RepostOrderToNavOnProblemCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:nav:problems:reput')
            ->setDescription('Put order with nav_problems to Navision if it does not exist there')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Dont execute real thing'
            )
            ->addOption(
                'date',
                null,
                InputOption::VALUE_OPTIONAL,
                'Date (yyyy-mm-dd)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $orderRepository = $em->getRepository('FoodOrderBundle:Order');
        $navService = $this->getContainer()->get('food.nav');
        $orderService = $this->getContainer()->get('food.order');
        $logger = $this->getContainer()->get('logger');

        // options
        $dryRun = $input->getOption('dry-run');
        $dateGiven = $input->getOption('date');

        if (empty($dateGiven)) {
            $dateStart = new \DateTime("now");
            $dateStart->sub(new \DateInterval("PT2H"));
            $dateEnd = new \DateTime("-5 minute");
        } else {
            $dateStart = new \DateTime($dateGiven);
            $dateStart->setTime('0', '0', '1');
            $dateEnd = new \DateTime($dateGiven);
            $dateEnd->setTime('23', '59', '59');
        }

        $message = 'RepostOrderToNavOnProblems started with date range: '.$dateStart->format('Y-m-d H:i:s').' - '
            .$dateEnd->format('Y-m-d H:i:s');
        $logger->alert($message);
        $output->writeln($message);


        if ($dryRun) {
            $output->writeln('This is dry-run. No real actions will be ran');
        }

        $orders = $orderRepository->getNavProblems($dateStart, $dateEnd);

        $oCount = count($orders);
        $message = 'Found '.$oCount.' orders with nav problems';
        $logger->alert($message);
        $output->writeln($message);

        try {
            if (!empty($orders)) {
                foreach ($orders as $order) {
                    // Check if order exists in webOrderHeader and in webOrderLines
                    $message = 'Checking order #' . $order->getId();
                    $logger->alert($message);
                    $output->writeln($message);

                    if ($navService->isMissingFromWebHeader($order)) {
                        $message = 'Order #' . $order->getId().' is missing in NAV';
                        $logger->alert($message);
                        $output->writeln($message);

                        if (!$dryRun) {
                            $message = 'Trying to reput order #' . $order->getId();
                            $logger->alert($message);
                            $output->writeln($message);

                            $orderService->logOrder($order, 'NAV_re_put_order', 'Trying to reput order to NAV');
                            $navService->putTheOrderToTheNAV($order);
                            sleep(2);
                            $orderService->logOrder($order, 'NAV_update_prices', 'NAV update prices from Reput command');
                            $navService->updatePricesNAV($order);
                            sleep(2);
                            $orderService->logOrder($order, 'NAV_process_order', 'NAV process order from Reput command');
                            $navService->processOrderNAV($order);

                            $orderService->setOrder($order);
                            $orderService->statusNew('repostToNavOnMissingInNav');
                            $orderService->saveOrder();
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $message = 'An error happened while running RepostOrderToNavOnProblems command. Error: '.$e->getMessage();
            $logger->error($message);
            $output->writeln('<error>'.$message.'</error>');

            throw $e;
        }
    }
}
