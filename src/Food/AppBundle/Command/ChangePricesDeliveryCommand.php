<?php

namespace Food\AppBundle\Command;

use Food\DishesBundle\Entity\Place;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class ChangePricesDeliveryCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('import:price:change')
            ->setDescription('Change delivery price for delivery zones')
            ->addOption('amount', null, InputOption::VALUE_REQUIRED, 'amount separated by dot')
            ->addOption('action', null, InputOption::VALUE_REQUIRED, 'add or remove value for price')
            ->addOption('self_delivery', null, InputOption::VALUE_REQUIRED, 'self delivery yes - 1 no - 0')
            ->addArgument(
                'not_restaurants',
                InputArgument::IS_ARRAY,
                'restaurant ids not included'
            );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');

        $amount = $input->getOption('amount');
        $action = $input->getOption('action');
        $selfDelivery = $input->getOption('self_delivery');
        $notIncludedRestaurants = $input->getArgument('not_restaurants');

        try {

            $places = $doctrine->getRepository('FoodDishesBundle:Place')->findBy(['active' => 1, 'selfDelivery' => $selfDelivery]);

            foreach ($places as $place) {
                if ($notIncludedRestaurants) {
                    if (!in_array($place->getId(), $notIncludedRestaurants)) {
                        $this->changeDeliveryZone($place, $amount, $action);
                    }
                } else {
                    $this->changeDeliveryZone($place, $amount, $action);
                }

            }

            echo 'Jobs done! Returning to town!';


        } catch (\Exception $e) {
            $output->writeln('<fg=red>Something went horribly wrong</fg=red>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getTraceAsString()));

            throw $e;
        }

        $this->getContainer()->get('doctrine')->getConnection()->close();
    }

    public function changeDeliveryZone(Place $place, $amount, $action)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        $zones = $doctrine->getRepository('FoodDishesBundle:PlacePointDeliveryZones')->findBy(['place' => $place->getId()]);

        foreach ($zones as $zone) {
            if ($zone->getPrice()) {

                if ($action == 'add') {
                    $zone->setPrice($zone->getPrice() + $amount);
                } elseif ($action == 'remove' && $amount < $zone->getPrice()) {
                    $zone->setPrice($zone->getPrice() - $amount);
                }

                $em->persist($zone);
                $em->flush();
            }

        }

    }

}