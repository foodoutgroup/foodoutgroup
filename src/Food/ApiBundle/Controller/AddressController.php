<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Food\AppBundle\Entity\ErrorLog;
use Food\UserBundle\Entity\User;

class AddressController extends Controller
{

    public function findAddressAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Address:findAddressAction Request:', (array)$request);

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
            $this->get('logger')->error('Address:findAddressAction Error:' . $e->getMessage());
            $this->get('logger')->error('Address:findAddressAction Trace:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        if (empty($response)) {

            $this->get('food.error_log_service')->saveErrorLog(
                $this->container->get('request')->getClientIp(),
                $this->getUser(),
                null,
                null,
                new \DateTime('now'),
                $request->getUri(),
                'api_adress_change_find',
                'api_null_request',
                serialize($request)
            );
        }


        $this->get('logger')->alert('Address:findAddressAction Response:' . print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    public function findStreetAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Address:findStreetAction Request:', (array)$request);
        try {
            $queryPart = $request->get('query');
            $response = array();

            if (!empty($queryPart)) {
                $streets = $this->get('food.logistics')->findStreet($queryPart, 5);

                if (!empty($streets)) {
                    foreach ($streets as $street) {

                        $item = [
                            'street' => $street->getStreet(),
                            'city' => $street->getCity(),
                        ];

                        if($cityObj = $street->getCityId()) {
                            $item['city'] = $cityObj->getTitle();
                            $item['city_id'] = $cityObj->getId();
                        }
                        $response[] = $item;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->get('logger')->error('Address:findStreetAction Error:' . $e->getMessage());
            $this->get('logger')->error('Address:findStreetAction Trace:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Address:findStreetAction Response:' . print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }
}
