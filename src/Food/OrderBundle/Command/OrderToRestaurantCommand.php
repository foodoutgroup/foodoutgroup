<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;

class OrderToRestaurantCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:restaurant:send')
            ->setDescription('Send order to restaurant system')
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'limit, how much orders to send'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $em = $this->getContainer()->get('doctrine')->getManager();

            $orderToRestaurantCollection = $em->getRepository('FoodOrderBundle:OrderToRestaurant')->getOrdersToSend();
            foreach ($orderToRestaurantCollection as $orderToRestaurant) {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $orderToRestaurant->getOrder()->getPlacePoint()->getSyncUrl());
                curl_setopt($ch, CURLOPT_POST, 1);

                $request = [
                    'hash' => $orderToRestaurant->getOrder()->getOrderHash(),
                    'state' => $orderToRestaurant->getState(),
                    'date' => $orderToRestaurant->getDateAdded()->format("r"),
                ];

                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $server_output = curl_exec ($ch);

                curl_close ($ch);
                $orderToRestaurant->setTryCount($orderToRestaurant->getTryCount()+1);

                if ($server_output == "OK") {
                    $orderToRestaurant->setDateSent(new \DateTime());
                } elseif($orderToRestaurant->getTryCount() > 7) {
                    $orderToRestaurant->setDateFailed(new \DateTime());
                }
                $em->persist($orderToRestaurant);
                $em->flush();

            }
        } catch (\Exception $e) {

        }
    }
}
