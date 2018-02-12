<?php

namespace Food\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangePricesDeliveryCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('import:price:change')
            ->setDescription('Change delivery price for delivery zones')
            ->addOption('amount', null, InputOption::VALUE_REQUIRED, 'amount separated by dot')
            ->addOption('action', null, InputOption::VALUE_REQUIRED, 'add or remove value for price');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $amount = $input->getOption('amount');
        $action = $input->getOption('action');
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        try {
            $zonesRepo = $doctrine->getRepository('FoodDishesBundle:PlacePointDeliveryZones');
            $zones = $zonesRepo->findAll();

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

            echo 'Jobs done! Returning to town!';


        } catch (\Exception $e) {
            $output->writeln('<fg=red>Something went horribly wrong</fg=red>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getTraceAsString()));

            throw $e;
        }

        $this->getContainer()->get('doctrine')->getConnection()->close();
    }

}