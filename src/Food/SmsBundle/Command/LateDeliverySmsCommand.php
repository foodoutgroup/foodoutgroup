<?php
namespace Food\SmsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Send SMS to the user when order is near being late :(
 *
 * Class LateDeliverySmsCommand
 * @package Food\SmsBundle\Command
 */
class LateDeliverySmsCommand extends ContainerAwareCommand
{
    private $timeStart;
    private $maxChecks = 5;

    protected function configure()
    {
        $this->timeStart = microtime(true);

        $this
            ->setName('order:send_late_order')
            ->setDescription('Send messages')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'No messages will be sent. Just pure raw mega slim output'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = 0;

        $messagingService = $this->getContainer()->get('food.messages');
        $orderService = $this->getContainer()->get('food.order');
        $miscService = $this->getContainer()->get('food.app.utils.misc');

        try {
            $lateTimeToDelivery = $miscService->getParam('late_time_to_delivery');
            $possibleDeliveryDelay = $miscService->getParam('possible_delivery_delay');

            $orders = $orderService->getOrdersToBeLate($lateTimeToDelivery);

            if (!empty($orders)) {
                $count = count($orders);

                if ($count > 0) {
                    foreach ($orders as $order) {
                        $theOrder = $orderService->getOrderById($order['id']);
                        if (!$input->getOption('dry-run')) {
                            $messagingService->informLateOrder($theOrder, $possibleDeliveryDelay);

                            $theOrder->setLateOrderInformed(true);

                            $orderService->saveOrder();
                        } else {
                            $output->writeln(
                                sprintf(
                                    '<info>Message for order #%d would be sent. Should be delivered at %s</info>',
                                    $theOrder->getId(),
                                    $theOrder->getDeliveryTime()->format("Y-m-d H:i")
                                )
                            );
                        }
                    }
                }
            }

            $timeSpent = microtime(true) - $this->timeStart;
            $output->writeln(sprintf('<info>%d messages sent in %0.2f seconds</info>', $count, $timeSpent));

        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>Sorry, lazy programmer left a bug :(</error>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
        } catch (\Exception $e) {
            $output->writeln('<error>Mayday mayday, an error knocked the process down.</error>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
        }
    }

    /**
     * @return int
     */
    public function getMaxChecks()
    {
        return $this->maxChecks;
    }

    /**
     * @param int $checks
     */
    public function setMaxChecks($checks=1)
    {
        $this->maxChecks = $checks;
    }
}