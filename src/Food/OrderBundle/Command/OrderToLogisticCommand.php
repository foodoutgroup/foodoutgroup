<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderToLogisticCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:logistic:send')
            ->setDescription('Send orders to logistic system')
            ->addOption(
                'force-order',
                null,
                InputOption::VALUE_OPTIONAL,
                'If set, only the given order will be sent. Expected input: OrderToLogistics->id'
            )
            ->addOption(
                'force-url',
                null,
                InputOption::VALUE_OPTIONAL,
                'If set, the request will be sent to the given url'
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
            $logisticsService = $this->getContainer()->get('food.logistics');
            $orderService = $this->getContainer()->get('food.order');
            $em = $this->getContainer()->get('doctrine')->getManager();
            $logger = $this->getContainer()->get('logger');
            $systemUrl = $input->getOption('force-url');
            if (empty($systemUrl)) {
                $systemUrl = $this->getContainer()->getParameter('logtime.order_url');
            }
            $dryRun = false;

            $orders = $em->getRepository('FoodOrderBundle:OrderToLogistics')->getOrdersToSend();

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
                $output->writeln('Sending XML for order '.$orderToSend->getOrder()->getId());
                $xml = $logisticsService->generateOrderXml($orderToSend->getOrder());

                $this->getContainer()->get('logger')->alert(
                  sprintf(
                      'Sending order #%d to logistics with XML'."\n".' %s',
                      $orderToSend->getOrder()->getId(),
                      $xml
                  )
                );

                if (!$dryRun) {
                    $orderToSend->setDateSent(new \DateTime("now"))
                        ->setTimesSent($orderToSend->getTimesSent()+1);
                    $response = $logisticsService->sendToLogistics($systemUrl, $xml);
                    $orderToSend->setStatus($response['status'])
                        ->setLastError($response['error']);

                    $em->persist($orderToSend);

                    // Log the fack of the send and response
                    $orderService->logOrder(
                        $orderToSend->getorder(),
                        'sent_to_logistics_api',
                        sprintf('Order sent to logistics system. Send status: %s', $response['status'])
                    );

                    if ($response['status'] != 'sent') {
                        $logisticsService->putOrderForSend($orderToSend->getOrder());
                    }

                    // Put to log so we get notified by Nagios
                    if ($response['status'] == 'error') {
                        $logger->error(
                            sprintf(
                                'Sending order #%d to logistics failed. Check DB for error',
                                $orderToSend->getOrder()->getId()
                            )
                        );
                    }

                    $output->writeln(' -- status: '.$response['status']);
                }
            }

            $em->flush();

        } catch (\Exception $e) {
            $output->writeln('Error sending order to logistics');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}