<?php

namespace Food\PlacesBundle\Service;

use Food\AppBundle\Service\BaseService;
use Food\DishesBundle\Entity\PlacePoint;

class PlacePointService extends BaseService
{

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
}