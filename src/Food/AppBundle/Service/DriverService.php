<?php
namespace Food\AppBundle\Service;

use DateInterval;
use DatePeriod;
use DateTime;

class DriverService extends BaseService
{
    public function calculateDriversWorktimes(DateTime $dateFrom, DateTime $dateTo)
    {
        $times = array();
        $drivers = $this->getDrivers();
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($dateFrom, $interval ,$dateTo);
        foreach ($drivers as $driver) {
            $times[$driver->getName()]['driver'] = $driver->getName();
            foreach($dateRange as $date) {
                $times[$driver->getName()][$date->format('Y-m-d')] =
                    $this->em->getRepository('FoodAppBundle:Driver')->getDriverWorkedTime(
                        $driver->getId(),
                        $date->format('Y-m-d')
                    );
            }
        }
        return $times;
    }

    protected function getDrivers()
    {
        return $this->em->getRepository('FoodAppBundle:Driver')->findBy(array('active' => 1));
    }
}