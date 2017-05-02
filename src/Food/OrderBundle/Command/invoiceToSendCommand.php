<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Food\OrderBundle\Service\OrderService;

class InvoiceToSendCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:invoice:send')
            ->setDescription('Send invoices to user and restaurant')
            ->addOption(
                'force-order',
                null,
                InputOption::VALUE_OPTIONAL,
                'If set, only the given order will be sent. Expected input: InvoiceToSend->id'
            )
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
            $nav = $this->getContainer()->get('food.nav');
            $em = $this->getContainer()->get('doctrine')->getManager();
            $forcedEmail = $input->getOption('force-email');
            if (empty($forcedEmail)) {
                $forcedEmail = null;
            }
            $dryRun = false;

            $orders = $em->getRepository('FoodOrderBundle:InvoiceToSend')->getInvoiceToSend();
            /**
             * TODO:
             *  - force order
             */

            // Dont send if dry-run
            if ($input->getOption('dry-run')) {
                $output->writeln('Dry run - no orders will be sent');
                $dryRun = true;
            }

            foreach ($orders as $orderToSend) {
                try {
                    $sendingMessage = 'Sending Invoice for order '.$orderToSend->getOrder()->getId();
                    $output->writeln($sendingMessage);
                    $logger->alert($sendingMessage);

                    if (!$dryRun) {
                        $order = $orderToSend->getOrder();
                        $invoiceService->generateUserInvoice($order);

                        usleep(300000);

                        $invoiceService->storeUserInvoice($order);

                        $emails = array();
                        if ($order->getPaymentMethod() != 'postpaid'
                            && OrderService::$status_completed == $order->getOrderStatus()
                            && (!$order->getUser()->getNoInvoice() || $forcedEmail)) {
                            $emails = $invoiceService->sendUserInvoice($order, $forcedEmail);
                        }

                        $orderToSend->setDateSent(new \DateTime('now'))
                                    ->markSent();

                        $em->persist($orderToSend);
                        $em->flush();

                        // Remove from nav if this is a reimport
                        if ($orderToSend->getDeleteFromNav()) {
                            $nav->deleteInvoiceFromNav($order->getSfSeries().$order->getSfNumber());
                        }

                        // create invoice in NAVISION
                        $nav->sendNavInvoice($orderToSend->getOrder());

                        $sentMessage = 'Invoice sent to emails: '.implode(', ', $emails);
                        $output->writeln($sentMessage);
                        $logger->alert($sentMessage);

                        usleep(300000);
                    }
                } catch (\Exception $e) {
                    // mark error (for historical reasons)
                    // but please _DO NOT_ mark it unsent!
                    $orderToSend->markError()
                                ->setLastError($e->getMessage());

                    $em->persist($orderToSend);
                    $em->flush();

                    $invoiceService->addInvoiceToSend($orderToSend->getOrder());

                    throw $e;
                }
            }

        } catch (\Exception $e) {
            $output->writeln('Error sending order invoice');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }

        // now try to resend unsent NAV invoices
        if (!$dryRun) {
            $invoicesToSendNavOnly = $em->getRepository('FoodOrderBundle:InvoiceToSendNavOnly')
                                        ->getInvoiceToSendNavOnly();

            foreach ($invoicesToSendNavOnly as $invoice) {
                $nav->deleteInvoiceFromNav($invoice->getOrder()->getSfSeries().$invoice->getOrder()->getSfNumber());
                $success = $nav->sendNavInvoice($invoice->getOrder(), $invoice);
            }
        }
    }
}
