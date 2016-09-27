<?php
namespace Food\AppBundle\Service;

use DateInterval;
use DatePeriod;
use DateTime;

class DriverService extends BaseService
{
    public function calculateDriversWorktimesLastMonth()
    {
        $times = array();
        $drivers = $this->getDrivers();
        $begin = new DateTime('first day of last month');
        $end = new DateTime('last day of last month');
        $begin = new DateTime('2016-07-01');
        $end = new DateTime('2016-07-31');
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($begin, $interval ,$end);
        foreach ($drivers as $driver) {
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