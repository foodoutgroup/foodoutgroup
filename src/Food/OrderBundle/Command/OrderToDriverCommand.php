<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderToDriverCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:driver:send')
            ->setDescription('Send order to driver system')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'dont send, just output orders, that should be sent'
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'limit, how much orders to send'
            )
            ->addOption(
                'debug',
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
            $miscService = $this->getContainer()->get('food.app.utils.misc');
            $em = $this->getContainer()->get('doctrine')->getManager();
            $dryRun = false;
            $limit = 50;
            $debug = false;

            // Dont send if dry-run
            if ($input->getOption('dry-run')) {
                $output->writeln('Dry run - no orders will be sent');
                $dryRun = true;
            }
            if ($input->getOption('limit')) {
                $output->writeln('Limit');
                $limit = $input->getOption('limit');
            }
            if ($input->getOption('debug')) {
                $output->writeln('Debug mode');
                $debug = true;
            }


            if (!$dryRun) {
                $fp = stream_socket_client($this->getContainer()->getParameter('driver.socket_address'), $errno, $errstr, 30);
                if (!$fp) {
                    $logger->error("$errstr ($errno)");
                    throw new \Exception("$errstr ($errno)");
                } else {
                    fwrite($fp, sprintf('{"event":"system:auth","collection":{"secure":"%s","method":"hash","type":"api"}}'."\n\n", $this->getContainer()->getParameter('driver.socket_hash')));
                }
            }

            $orderToDriverCollection = $em->getRepository('FoodOrderBundle:OrderToDriver')->getOrdersToSend();
            $i = 1;
            foreach ($orderToDriverCollection as $orderToDriver) {
                if ($limit < $i) {
                    $output->writeln('Just reached the limit');
                    break;
                }
                $order = $orderToDriver->getOrder();
                $msg = '{"event": "system:routing", "collection": [{"event": "api:order:newOrder", "params": {"address": "http://'.$this->getContainer()->getParameter('domain').'/api/v1/ordersByHash/'.$order->getOrderHash().'"}}]}'."\n\n";
                if ($debug) {
                    $output->writeln($msg);
                }
                if (!$dryRun) {
                    fwrite($fp, $msg);
                    $orderToDriver->setDateSent(new \DateTime());
                    $em->persist($orderToDriver);
                    ++$i;
                    usleep(5000);
                }
            }
            if (!$dryRun) {
                $em->flush();
                fclose($fp);
            }

            $output->writeln(sprintf('Sent %d / %d', $i, count($orderToDriverCollection)));

        } catch (\Exception $e) {
            $output->writeln('Error sending order to driver system');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}
