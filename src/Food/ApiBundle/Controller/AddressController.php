<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AddressController extends Controller
{

    public function findAddressAction(Request $request)
    {
        $this->get('logger')->debug('findAddressAction Request:', (array) $request);
        try {
            $lat = $request->get('lat');
            $lng = $request->get('lng');
            $city = $request->get('city');
            $street = $request->get('street');
            $houseNumber = $request->get('house_number');
            if (!empty($lat) && !empty($lng)) {
                $response = $this->get('food.googlegis')->findAddressByCoords($lat, $lng);
            } elseif (!empty($city) && !empty($street) && !empty($houseNumber)) {
                $response = $this->get('food.googlegis')->findAddressByCoordsByStuff(
                    $city, $street, $houseNumber
                );
            } else {
                $response = [];
            }
        } catch (\Exception $e) {
            $this->get('logger')->error('findAddressAction Error:' . $e->getMessage());
            $this->get('logger')->error('findAddressAction Trace:' . $e->getTraceAsString());

            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->debug('findAddressAction Response:', print_r($response, true));
        return new JsonResponse($response);
    }

    public function findStreetAction(Request $request)
    {
        $this->get('logger')->debug('findStreetAction Request:', (array) $request);
        try {
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
        } catch (\Exception $e) {
            $this->get('logger')->error('findStreetAction Error:' . $e->getMessage());
            $this->get('logger')->error('findStreetAction Trace:' . $e->getTraceAsString());

            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->debug('findStreetAction Response:', print_r($response, true));
        return new JsonResponse($response);
    }
}
