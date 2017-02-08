<?php

namespace Food\PlacesBundle\Service;

use Food\AppBundle\Service\BaseService;
use Food\DishesBundle\Entity\PlacePoint;
use Food\DishesBundle\Entity\PlacePointWorkTime;

class PlacePointService extends BaseService
{

    protected $placeService;
    /**
     * @param PlacePoint $placePoint
     * @return string
     */
    public function generatePlacePointHash($placePoint)
    {
        if (empty($placePoint) || !($placePoint instanceof PlacePoint)) {
            throw new \InvalidArgumentException();
        }

        $hash = md5($placePoint->getPlace()->getId() . $placePoint->getId() . microtime());

        return $hash;
    }

    /**
     * @param PlacePoint $placePoint
     */
    public function updatePlacePointWorktime($placePoint)
    {
        foreach ($placePoint->getWorkTimes() as $workTime) {
            $this->em->remove($workTime);
        }
        $this->em->flush();

        for ($i = 1; $i <= 7; $i++) {
            $workTime = $placePoint->{'getWd' . $i}();
            $workTime = preg_replace('~\s*-\s*~', '-', $workTime);
            $intervals = explode(' ', $workTime);
            foreach ($intervals as $interval) {
                if ($times = $this->placeService->parseIntervalToTimes($interval)) {
                    list($startHour, $startMin, $endHour, $endMin) = $times;
                } else {
                    continue;
                }

                // if start time is later thant end time, then we should split it
                if ($endHour < $startHour || $endHour == $startHour && $endMin < $startMin) {
                    $ppwt = new PlacePointWorkTime();
                    $ppwt->setPlacePoint($placePoint)
                        ->setWeekDay($i)
                        ->setStartHour($startHour)
                        ->setStartMin($startMin)
                        ->setEndHour(24)
                        ->setEndMin(0)
                    ;

                    $this->em->persist($ppwt);

                    // 00:00 - 00:00 must be excluded
                    if ($endHour != 0 || $endMin != 0) {
                        $ppwt = new PlacePointWorkTime();
                        $ppwt->setPlacePoint($placePoint)
                            ->setWeekDay($i < 7 ? $i + 1 : 1)
                            ->setStartHour(0)
                            ->setStartMin(0)
                            ->setEndHour($endHour)
                            ->setEndMin($endMin)
                        ;

                        $this->em->persist($ppwt);
                    }
                } else {
                    $ppwt = new PlacePointWorkTime();
                    $ppwt->setPlacePoint($placePoint)
                        ->setWeekDay($i)
                        ->setStartHour($startHour)
                        ->setStartMin($startMin)
                        ->setEndHour($endHour)
                        ->setEndMin($endMin)
                    ;

                    $this->em->persist($ppwt);
                }
            }
        }

        $this->em->flush();
    }

    /**
     * @param mixed $placeService
     */
    public function setPlaceService($placeService)
    {
        $this->placeService = $placeService;
    }
}