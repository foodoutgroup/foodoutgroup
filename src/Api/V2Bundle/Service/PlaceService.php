<?php

namespace Api\V2Bundle\Service;

use Api\BaseBundle\Exceptions\ApiException;
use Food\PlacesBundle\Service\PlacesService;

class PlaceService extends PlacesService
{
    public function getPlaceByHash($hash){

        $place = $this->em()->getRepository('FoodDishesBundle:Place')->findOneBy([
            'apiHash'  => $hash,
        ]);

        if($hash == null || $place == null) {
            throw new ApiException('Place was not authorized');
        }

        return $place;
    }

}

