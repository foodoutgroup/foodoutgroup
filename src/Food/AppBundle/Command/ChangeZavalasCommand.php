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
        $this->timeStart = microtime(true);

        $this
            ->setName('import:zavalas_changes')
            ->setDescription('change zavalas delivery zones')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {

            $placePoints = $this->getContainer()->get('doctrine')->getRepository('FoodDishesBundle:PlacePoint')->findAll();
            foreach ($placePoints as $placePoint){
                $deliveryZones = $placePoint->getZones();
var_dump($deliveryZones);

//                if(!empty($deliveryZones)){
//                    $lowestDistance[0] = array();
//                    $i = 0;
//                    foreach ($deliveryZones as $deliveryZone){
//                        if($i = 0){
//                            $lowestDistance[$deliveryZone->getDistance()] = $deliveryZone;
//                        }else{
//                            if(key($lowestDistance) < $deliveryZone->getDistance()){
//                                $lowestDistance[$deliveryZone->getDistance()] = $deliveryZone;
//                            }
//                        }
//                    $i++;
//                    }
//
//                }

            }
        } catch (\Exception $e) {
            $output->writeln('<fg=red>Something went horribly wrong</fg=red>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
        }

        $this->getContainer()->get('doctrine')->getConnection()->close();
    }

}