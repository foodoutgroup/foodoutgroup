<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AddressController extends Controller
{

    public function findAddressAction(Request $request)
    {
        $lat = $request->get('lat');
        $lng = $request->get('lng');
        $city = $request->get('city');
        $street = $request->get('street');
        $houseNumber = $request->get('house_number');
        if (!empty($lat) && !empty($lng)) {
            $returner = $this->get('food.googlegis')->findAddressByCoords($lat, $lng);
        } elseif (!empty($city) && !empty($street) && !empty($houseNumber)) {
            $returner = $this->get('food.googlegis')->findAddressByCoordsByStuff(
                $city, $street, $houseNumber
            );
        } else {
            $returner = array();
        }
        return new JsonResponse($returner);
    }

    public function findStreetAction(Request $request)
    {
        $queryPart = $request->get('query');
        $response = array();

        if (!empty($queryPart)) {
            $streets = $this->get('food.logistics')->findStreet($queryPart, 5);

            if (!empty($streets)) {
                foreach ($streets as $street) {
                    $response[] = array(
                        'street' => $street->getStreet(),
                        'city' => $street->getCity(),
                    );
                }
            }
        }

        return new JsonResponse($response);
    }
}
