<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            $invoiceService = $this->getContainer()->get('food.invoice');
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
                    $output->writeln('Sending Invoice for order '.$orderToSend->getOrder()->getId());

                    if (!$dryRun) {
                        $invoiceService->generateUserInvoice($orderToSend->getOrder());
                        $invoiceService->storeUserInvoice($orderToSend->getOrder());
                        $emails = $invoiceService->sendUserInvoice($orderToSend->getOrder(), $forcedEmail);

                        $orderToSend->setDateSent(new \DateTime('now'))
                            ->markSent();

                        $em->persist($orderToSend);
                          $em->flush();

                        $output->writeln('Invoice sent to emails: '.implode(', ', $emails));
                    }
                } catch (\Exception $e) {
                    $orderToSend->markError()
                        ->setLastError($e->getMessage());

                    $em->persist($orderToSend);
                    $em->flush();

                    throw $e;
                }
            }

            // Insert empty line for clarity
//            $output->writeln('');
//            sleep(20);
//            $output->writeln('Removing generated invoice PDF files from local storage...');
//
//            if (!$dryRun) {
//                foreach ($orders as $orderToSend) {
//                    $output->writeln('Removing local invoice copy for order '.$orderToSend->getOrder()->getId());
//                    $invoiceService->removeUserInvoice($orderToSend->getOrder());
//                }
//            }

        } catch (\Exception $e) {
            $output->writeln('Error sending order invoice');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}