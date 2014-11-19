<?php
namespace Food\MonitoringBundle\Command;

use Food\OrderBundle\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class NavOrderPriceMonitorCommand
 * ATTENTION This command is not running from Nagios. It's a cron as it is CRITICALY important
 *
 * @package Food\MonitoringBundle\Command
 */
class NavOrderPriceMonitorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('monitoring:nav:price')
            ->setDescription('Monitor price equality on both ends')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'dont send email, just output orders'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Setup the main info
        $criticalOrders = array();

        // Dont send if dry-run
        if ($input->getOption('dry-run')) {
            $output->writeln('Dry run - email wont be sent. Only pure output for Your pleasure');
            $dryRun = true;
        } else {
            $dryRun = false;
        }

        // Do da math
        try {

            $em = $this->getContainer()->get('doctrine')->getManager();
            /**
             * @var $orders Order[]
             */
            $orders = $em->getRepository('FoodOrderBundle:Order')->getCurrentNavOrders('-5 minute');
            $navService = $this->getContainer()->get('food.nav');

            if (!empty($orders) && count($orders) > 0) {
                $navOrders = $navService->getRecentNavOrderSums($orders);


                foreach($orders as $order) {
                    // dont panic if it is not here - unsynced monitor will take care of that
                    if (isset($navOrders[$order->getId()])) {
                        if ((float)$order->getTotal() != (float)$navOrders[$order->getId()]['total']) {
                            $criticalOrders[] = array(
                                'orderId' => $order->getId(),
                                'navOrderId' => $navOrders[$order->getId()]['Order No_'],
                                'localTotal' => $order->getTotal(),
                                'navTotal' => $navOrders[$order->getId()]['total'],
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $text = 'Klaida lyginant kainas su Navision: '.$e->getMessage();
            $output->writeln('<error>'.$text.'</error>');

            if (!$dryRun) {
                $this->informPeople($text);
            }

            throw $e;
        }

        // Jei sikna - siunciam zinia
        if (!empty($criticalOrders)) {
            $text = 'Uzsakymai, kuriu kainos nesutampa tarp Foodout ir Navision:'."\n\n";

            foreach($criticalOrders as $order) {
                $text .= sprintf(
                        'Foodout uzsakymo id: %d ; Navision uzsakymo id: %d ; Suma Foodoute: %01.2f ; Suma Navision: %01.2f',
                        $order['orderId'],
                        $order['navOrderId'],
                        $order['localTotal'],
                        $order['navTotal']
                    )
                    ."\n";
            }
            $output->writeln($text);

            if (!$dryRun) {
                $this->informPeople($text);
            }
        } else {
            $output->writeln('Visi uzsakymai sutampa');
        }

        return 0;
    }

    /**
     * @param string $messageText
     */
    private function informPeople($messageText)
    {
        // Jei kazkas pridirbs nesamoniu - dedam skersa ant jo ir nespaminam zmoniu
        if (empty($messageText)) {
            return;
        }

        $mailer = $this->getContainer()->get('mailer');
        $orderService = $this->getContainer()->get('food.order');
        $notifyEmails = $this->getContainer()->getParameter('order.notify_emails');
        $domain = $this->getContainer()->getParameter('domain');

        $message = \Swift_Message::newInstance()
            ->setSubject($this->getContainer()->getParameter('title').': '.'nesutampa Foodout ir Navision uzsakymo kainos')
            ->setFrom('info@'.$domain)
        ;

        $orderService->addEmailsToMessage($message, $notifyEmails);

        $message->setBody($messageText);
        $mailer->send($message);
    }
}
