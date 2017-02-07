<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Food\OrderBundle\Controller\Decorators\SwedbankBanklinkGateway\ReturnDecorator;

class SwedbankCheckCommand extends ContainerAwareCommand
{
    use ReturnDecorator;

    protected function configure()
    {
        $this
            ->setName('order:swedbank:check')
            ->setDescription('Check swedbank wait orders that should be paid')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Pure output, nothing to be done'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $processedOrders = 0;
            $em = $this->getContainer()->get('doctrine')->getManager();
            $orderRepository = $em->getRepository('FoodOrderBundle:Order');
            $banklinkLogRepository = $em->getRepository('FoodOrderBundle:BanklinkLog');
            $orders = $orderRepository->getUnpaidOrders();

            foreach ($orders as $order) {
                if ('swedbank-gateway' == $order['payment_method']) {
                    $logs = $banklinkLogRepository->findByOrderId($order['id']);
                    foreach ($logs as $log) {
                        $simpleXml = simplexml_load_string($log->getXml());
                        if (is_object($simpleXml->APMTxn->Purchase)) {
                            $DPGReferenceId = reset($simpleXml->APMTxn->Purchase->attributes()['DPGReferenceId']);
                            $TransactionId = reset($simpleXml->APMTxn->Purchase->attributes()['TransactionId']);
                            if (!empty($DPGReferenceId) && !empty($TransactionId)) {
                                $request = new Request(['DPGReferenceId' => $DPGReferenceId, 'TransactionId' => $TransactionId]);

                                $url = 'http://' . $this->getContainer()->getParameter('domain') . '/payments/swedbank/gateway/success?_locale=lt&DPGReferenceId='.$DPGReferenceId.'&TransactionId='.$TransactionId;

                                file_get_contents($url);

                                $processedOrders++;

                                break;
                            }
                        }
                    }
                }
            }

            $output->writeln('Orders processed: '.$processedOrders);
        } catch (\Exception $e) {
            $output->writeln('Error when closing order');
            $output->writeln('Error: '.$e->getMessage());
            $output->writeln('Trace: '."\n".$e->getTraceAsString());
            throw $e;
        }
    }
}
