<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Food\OrderBundle\Entity\InvoiceToSendNavOnly;

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

            foreach($orders as $orderToSend) {
                try {
                    $sendingMessage = 'Sending Invoice for order '.$orderToSend->getOrder()->getId();
                    $output->writeln($sendingMessage);
                    $logger->alert($sendingMessage);

                    if (!$dryRun) {
                        $invoiceService->generateUserInvoice($orderToSend->getOrder());

                        usleep(300000);

                        $invoiceService->storeUserInvoice($orderToSend->getOrder());

                        $emails = $invoiceService->sendUserInvoice($orderToSend->getOrder(), $forcedEmail);

                        $orderToSend->setDateSent(new \DateTime('now'))
                                    ->markSent();

                        $em->persist($orderToSend);
                        $em->flush();

                        // create invoice in NAVISION
                        $this->sendNavInvoice($orderToSend->getOrder());

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
                $success = $this->sendNavInvoice($invoice->getOrder(), $invoice);
            }
        }
    }

    protected function sendNavInvoice($order, $invoice = null)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $nav = $this->getContainer()->get('food.nav');

        // call SOAP
        $success = $nav->createInvoice($order);

        // create sent/error entry for this nav invoice to send
        if (is_null($invoice)) {
            $invoiceToSendNavOnly = new InvoiceToSendNavOnly();
            $invoiceToSendNavOnly->setOrder($order)
                                 ->setDateAdded(new \DateTime('now'))
                                 ->setDateSent(new \DateTime('now'));

            $em->persist($invoiceToSendNavOnly);
        } else {
            $invoiceToSendNavOnly = $invoice;
            $invoiceToSendNavOnly->setDateSent(new \DateTime('now'));
        }

        if ($success) {
            $invoiceToSendNavOnly->markSent();
            $em->flush();
        } else {
            $invoiceToSendNavOnly->markError();

            $newInvoice = clone $invoiceToSendNavOnly;
            $newInvoice->markUnsent();

            $em->persist($newInvoice);
            $em->flush();
        }

        return $success;
    }
}
