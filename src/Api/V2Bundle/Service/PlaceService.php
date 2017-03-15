<?php

namespace Api\V2Bundle\Service;

use Api\BaseBundle\Exceptions\ApiException;
use Food\DishesBundle\Entity\Place;
use Food\PlacesBundle\Service\PlacesService;

class PlaceService extends PlacesService
{
    public static $_getNearCache;

    public function getPlaceByHash($hash){

        $place = $this->em()->getRepository('FoodDishesBundle:Place')->findOneBy([
            'apiHash'  => $hash,
        ]);

        if($hash == null || $place == null) {
            throw new ApiException('Place was not authorized');
        }
        
        return $place;
    }

    public function getLocationData($city, $address)
    {
        $response = [
            'found' => false,
            'lat' => 0,
            'lng' => 0,
            'street' => false,
            'house' => false,

        ];

        $addressString = $address." ,".$city." Lithuania";
        $gis = $this->container->get('food.googlegis');
        $location = $gis->getPlaceData($addressString);

        if(count($location->results) >= 1) {
            $addressParser = $gis->parseDataFromLocation($location, $addressString);
            if(isset($addressParser['lat']) && isset($addressParser['lng'])) {
                $response['lat'] = $addressParser['lat'];
                $response['lng'] = $addressParser['lng'];
            }

            $response['found'] = !$addressParser['not_found'];
            $response['street'] = $addressParser['street_found'];
            $response['house'] = $addressParser['address_found'];
        }

        return $response;
    }


    public function getPlacesByLocation(Place $place, $locationData) {

        $placeId = $place->getId();
        $ignoreSelfDelivery = false;
            $response = [];
            $cacheKey = $placeId . serialize($locationData) . (int)$ignoreSelfDelivery;
            if (!isset(self::$_getNearCache[$cacheKey])) {
                if (!empty($locationData['lat'])) {
                    $lat = str_replace(",", ".", $locationData['lat']);
                    $lon = str_replace(",", ".", $locationData['lng']);

                    $dh = date("H");
                    $dm = date("i");
                    $wd = date('w');
                    if ($wd == 0) $wd = 7;

                    $defaultZone = "SELECT MAX(ppdzd.distance) FROM `place_point_delivery_zones` ppdzd WHERE ppdzd.deleted_at IS NULL AND ppdzd.active=1 AND ppdzd.place_point IS NULL AND ppdzd.place IS NULL";
                    $maxDistance = "SELECT MAX(ppdz.distance) FROM `place_point_delivery_zones` ppdz WHERE ppdz.deleted_at IS NULL AND ppdz.active=1 AND ppdz.place_point=pp.id";

                    $subQuery = "SELECT pp.id, pp.address, pp.city, pp.delivery, pp.public,  (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) AS distance
                    FROM place_point pp, place p, place_point_work_time ppwt
                    WHERE p.id = pp.place
                      AND pp.id = ppwt.place_point
                      AND pp.active=1
                      AND pp.deleted_at IS NULL
                      AND p.active=1
                      AND pp.place = $placeId
                      AND (
                        (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) <=
                        IF(($maxDistance) IS NULL, ($defaultZone), ($maxDistance))
                        " . (!$ignoreSelfDelivery ? "" : "") . "
                    )
                      AND ppwt.week_day = " . $wd . "
                      AND (
                        (ppwt.start_hour = 0 OR ppwt.start_hour < " . $dh . " OR
                          (ppwt.start_hour <= " . $dh . " AND ppwt.start_min <= " . $dm . ")
                        ) AND
                        ((ppwt.end_hour >= " . $dh . " AND ppwt.end_min >= " . $dm . ") OR
                            ppwt.end_hour > " . $dh . " OR ppwt.end_hour = 0)
                      )
                    ORDER BY fast DESC, (6371 * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(pp.lat)) * pi()/180 / 2), 2) + COS(abs($lat) * pi()/180 ) * COS(abs(pp.lat) * pi()/180) * POWER(SIN(($lon - pp.lon) * pi()/180 / 2), 2) ))) ASC";

                    $stmt = $this->getDoctrine()->getConnection()->prepare($subQuery);
                    $stmt->execute();
                    $placeCollection = $stmt->fetchAll();

                    if($placeCollection) {

                        foreach ($placeCollection as $item) {
                            /**
                             * @var $item Place
                             */
                            $response[] = [
                                'id' => $item['id'],
                                'address' => $item['address'],
                                'distance' => $item['distance'],
                                'pickup' => (boolean)$item['public'],
                                'delivery' => (boolean)$item['delivery'],
                                'deliveryPrice' => $this->getPlacePointDeliveryPrice($item['id'], $item['distance']),
                                'minCart' => $this->getPlacePointMinCartPrice($item['id'], $item['distance']),
                            ];
                         }

                    }
                }
                self::$_getNearCache[$cacheKey] = $response;
            }

            return self::$_getNearCache[$cacheKey];
    }

    public function getPlacePointMinCartPrice($placeId, $distance)
    {
        $deliveryPrice = "SELECT cart_size FROM `place_point_delivery_zones` WHERE place_point=" . (int)$placeId . " AND active=1 AND distance >= " . (float)$distance . " ORDER BY distance ASC LIMIT 1";
        $stmt = $this->getDoctrine()->getConnection()->prepare($deliveryPrice);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function getPlacePointDeliveryPrice($placeId, $distance)
    {
        $deliveryPrice = "SELECT price FROM `place_point_delivery_zones` WHERE place_point=" . (int)$placeId . " AND active=1 AND distance >= " . (float)$distance . " ORDER BY distance ASC LIMIT 1";
        $stmt = $this->getDoctrine()->getConnection()->prepare($deliveryPrice);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

}

