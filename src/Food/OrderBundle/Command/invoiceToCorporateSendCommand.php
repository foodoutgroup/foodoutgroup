<?php
namespace Food\OrderBundle\Command;

use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InvoiceToCorporateSendCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:invoice-corporate:send')
            ->setDescription('Send invoices to corporate user and restaurant')
            ->addOption(
                'force-email',
                null,
                InputOption::VALUE_OPTIONAL,
                'If set, the invoice will be sent to given url'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'dont send, just output orders, that should be sent'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $logger = $this->getContainer()->get('logger');
            $invoiceService = $this->getContainer()->get('food.invoice');
            $miscService = $this->getContainer()->get('food.app.utils.misc');
            $sfSeries = $this->getContainer()->getParameter('invoice.series');
            $em = $this->getContainer()->get('doctrine')->getManager();
            $forcedEmail = $input->getOption('force-email');
            if (empty($forcedEmail)) {
                $forcedEmail = null;
            }
            $dryRun = false;

            $orders = $em->getRepository('FoodOrderBundle:Order')->getCorporateOrdersForInvoice();

            $byUser = array();
            $userSfData = array();

            if (!empty($orders)) {
                foreach ($orders as $order) {
                    if (!isset($byUser[$order->getUser()->getId()])) {
                        $sfNum = $invoiceService->getUnusedSfNumber(false);
                        if (!$sfNum) {
                            try {
                                $sfNum = (int)$miscService->getParam('sf_next_number');
                                $miscService->setParam('sf_next_number', ($sfNum + 1));
                            } catch (OptimisticLockException $e) {
                                sleep(1);
                                $sfNum = (int)$miscService->getParam('sf_next_number');
                                $miscService->setParam('sf_next_number', ($sfNum + 1));
                            }
                        }
                        $userSfData[$order->getUser()->getId()] = $sfNum;

                        $order->setSfSeries($sfSeries)
                            ->setSfNumber($sfNum);

                        $byUser[$order->getUser()->getId()] = array($order);
                    } else {
                        $order->setSfSeries($sfSeries)
                            ->setSfNumber($userSfData[$order->getUser()->getId()]);

                        $byUser[$order->getUser()->getId()][] = $order;
                    }

                    if (!$dryRun) {
                        $em->persist($order);
                    }
                }

                if (!$dryRun) {
                    $em->flush();
                }
            }

            // Dont send if dry-run
            if ($input->getOption('dry-run')) {
                $output->writeln('Dry run - no invoices will be sent');
                $dryRun = true;
            }

            foreach($byUser as $userId => $userOrders) {
                try {
                    $sendingMessage = 'Sending Invoice for user '.$userId;
                    $output->writeln($sendingMessage);
                    $logger->alert($sendingMessage);

                    if (!$dryRun) {
                        $invoiceService->generateCorporateInvoice($userOrders);

                        usleep(300000);

                        $invoiceService->storeUserInvoice($userOrders[0]);

                        $emails = $invoiceService->sendCorporateInvoice($userOrders[0], $forcedEmail);

                        // create invoice in NAVISION
                        // Nuspresta kolkas nesiust. Buhalterija pati susikels
//                        $nav->sendNavInvoice($userOrders);

                        $sentMessage = 'Invoice sent to emails: '.implode(', ', $emails);
                        $output->writeln($sentMessage);
                        $logger->alert($sentMessage);

                        usleep(300000);
                    }
                } catch (\Exception $e) {
                    // TODO Log da error as we dont have a rerun

                    throw $e;
                }
            }

        } catch (\Exception $e) {
            $output->writeln('Error sending corporate orders invoice');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}
