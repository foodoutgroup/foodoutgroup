<?php

namespace Food\OrderBundle\Command;

use Food\OrderBundle\Entity\InvoiceToSend;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class FixInvoiceNumberCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:fix:account:data')
            ->setDescription('fix problem with completed orders')
            ->addArgument(
                'ids',
                InputArgument::IS_ARRAY,
                'Who do you want to greet (separate multiple names with a space)?'
            );;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $emRepo = $this->getContainer()->get('doctrine')->getManager();
        $invoiceRepository = $emRepo->getRepository('FoodOrderBundle:InvoiceToSend');
        $orderRepository = $emRepo->getRepository('FoodOrderBundle:Order');

        $ids = $input->getArgument('ids');


        try {
            if (!empty($ids)) {

                foreach ($ids as $id) {
                    $order = $orderRepository->find($id);
                    $order->setSfSeries(null);
                    $order->setSfNumber(null);
                    $emRepo->persist($order);
                    $emRepo->flush();

                    $invoiceToSend = new InvoiceToSend();
                    $invoiceToSend->setOrder($order);
                    $invoiceToSend->setDateAdded(new \DateTime('now'));
                    $invoiceToSend->setStatus('unsent');
                    $invoiceToSend->setDeleteFromNav(0);

                    $emRepo->persist($invoiceToSend);
                    $emRepo->flush();

                }

                echo 'changed orders';
            } else {
                echo 'no orders';
            }


            $this->getContainer()->get('doctrine')->getConnection()->close();
        } catch (\Exception $e) {
            $message = 'An error happened while running Account data execution command. Error: ' . $e->getMessage();
            $output->writeln('<error>' . $message . '</error>');

            throw $e;
        }
    }
}
