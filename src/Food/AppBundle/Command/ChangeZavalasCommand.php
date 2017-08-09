<?php

namespace Food\AppBundle\Command;

use Food\AppBundle\Service\MailService;
use Food\DishesBundle\Entity\Place;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeZavalasCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('import:zavalas_changes')
            ->setDescription('change zavalas delivery zones');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {
            $changedRecords = 0;
            $em = $this->getContainer()->get('doctrine')->getManager()->getConnection();
            $placePoints = $this->getContainer()->get('doctrine')->getRepository('FoodDishesBundle:PlacePoint')->findAll();
            foreach ($placePoints as $placePoint) {
                $query = "SELECT * from place_point_delivery_zones where place_point = 1409 ORDER BY distance asc";

                $stmt = $em->prepare($query);
                $stmt->execute();
                $deliveryZones = $stmt->fetchAll();

                if ($deliveryZones) {
                    $i = 0;
                    foreach ($deliveryZones as $deliveryZone) {
                        if ($i == 0) {
                            $update = "UPDATE place_point_delivery_zones SET active_on_zaval = 1 WHERE id = '" . $deliveryZone['id'] . "'";
                        } else {
                            $update = "UPDATE place_point_delivery_zones SET active_on_zaval = 0 WHERE id = '" . $deliveryZone['id'] . "'";
                        }
                        $i++;
                        $stmt = $em->prepare($update);
                        $stmt->execute();
                        $changedRecords++;
                    }
                }
            }
            $output->writeln('Changed delivery zones '.$changedRecords.' ');
        } catch (\Exception $e) {
            $output->writeln('<fg=red>Something went horribly wrong</fg=red>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
        }

        $this->getContainer()->get('doctrine')->getConnection()->close();
    }

}