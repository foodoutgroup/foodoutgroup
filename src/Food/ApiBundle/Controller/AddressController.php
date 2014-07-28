<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AddressController extends Controller
{

    public function findAddressAction(Request $request)
    {
        $returner = $this->get('food.googlegis')->findAddressByCoords(
            $request->get('lat'),
            $request->get('lng')
        );
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
