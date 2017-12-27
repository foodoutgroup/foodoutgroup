<?php

namespace Food\AppBundle\Command;

use Food\AppBundle\Service\MailService;
use Food\DishesBundle\Entity\Place;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RevertWorkingTimesCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('import:working_times')
            ->setDescription('change working times of restaurants');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {
            $em = $this->getContainer()->get('doctrine')->getManager()->getConnection();

            $query = "SELECT * from place_point where active = 1";
            $stmt = $em->prepare($query);
            $stmt->execute();
            $currentPlacePoints = $stmt->fetchAll();


            $query = "SELECT * from place_point_restore where active = 1";
            $stmt = $em->prepare($query);
            $stmt->execute();
            $oldPlacePoints = $stmt->fetchAll();


            $query = "SELECT * from place_point_work_time";
            $stmt = $em->prepare($query);
            $stmt->execute();
            $currentWorkTimes = $stmt->fetchAll();



            $query = "SELECT * from place_point_work_time_restore";
            $stmt = $em->prepare($query);
            $stmt->execute();
            $oldWorkTimes = $stmt->fetchAll();

            $oldPlacePointArray = array();
            foreach ($oldPlacePoints as $oldPlacePoint) {
                $oldPlacePointArray[$oldPlacePoint['id']] = $oldPlacePoint;
            }

            $oldPlacePoints = $oldPlacePointArray;

            foreach ($currentPlacePoints as $currentPlacePoint) {
                if (isset($oldPlacePoints[$currentPlacePoint['id']])) {


                    $oldRecord = $oldPlacePoints[$currentPlacePoint['id']];
                    $query = "UPDATE place_point set wd1 = '" . $oldRecord['wd1'] . "' , 
                                                     wd2 = '" . $oldRecord['wd2'] . "' ,
                                                     wd3 = '" . $oldRecord['wd3'] . "' ,
                                                     wd4 = '" . $oldRecord['wd4'] . "' ,
                                                     wd5 = '" . $oldRecord['wd5'] . "' ,
                                                     wd6 = '" . $oldRecord['wd6'] . "' ,
                                                     wd7 = '" . $oldRecord['wd7'] . "' 
                                                     where id = " . $currentPlacePoint['id'];

                    $stmt = $em->prepare($query);
                    $stmt->execute();
                }

            }

            $tmpOldWorkTimes = array();



            foreach ($oldWorkTimes as $oldWorkTime){
                $tmpOldWorkTimes[$oldWorkTime['id']] = $oldWorkTime;
            }

            $oldWorkTimes = $tmpOldWorkTimes;

            foreach ($currentWorkTimes as $currentWorkTime) {
                if (isset($oldWorkTimes[$currentWorkTime['id']])) {
                    $oldRecord = $oldWorkTimes[$currentWorkTime['id']];

                    $query = "UPDATE place_point_work_time set start_hour = '" . $oldRecord['start_hour'] . "' ,
                                                     start_min = '" . $oldRecord['start_min'] . "' ,
                                                     end_min = '" . $oldRecord['end_min'] . "' ,
                                                     end_hour = '" . $oldRecord['end_hour'] . "'

                                                     where id = " . $currentWorkTime['id'];
                    $stmt = $em->prepare($query);
                    $stmt->execute();
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