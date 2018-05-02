<?php

namespace Food\AppBundle\Command;

use Food\AppBundle\Service\MailService;
use Food\DishesBundle\Entity\Place;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeSelfDeliveryCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('import:self_delivery')
            ->setDescription('change payment method by checkbox near reastaurant (disableOnlinePayment)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        try {
            $places = $this->getContainer()->get('doctrine')->getRepository('FoodDishesBundle:Place')->findAll();

            foreach ($places as $place){
                if($place->getSelfDelivery()){
                    $place->setDisabledOnlinePayment(true);
                    $em->persist($place);
                    $em->flush();
                }
            }

        } catch (\Exception $e) {
            $output->writeln('<fg=red>Something went horribly wrong</fg=red>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
        }

        $this->getContainer()->get('doctrine')->getConnection()->close();
    }

}