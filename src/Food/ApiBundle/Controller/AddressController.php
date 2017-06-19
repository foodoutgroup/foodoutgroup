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
        //$this->get('logger')->alert('Address:findAddressAction Request:', (array)$request);
        try {
            $lat = $request->get('lat');
            $lng = $request->get('lng');
            $city = $request->get('city');
            $street = $request->get('street');
            $houseNumber = $request->get('house_number');
            $lService = $response = $this->get('food.location');
            if (!empty($lat) && !empty($lng)) {
                $response = $lService->findByCords($lat, $lng);

                if($response) {
                    $lService->setFromArray($response);
                    $response['house_number'] = $response['house'];
                    $response['pedestrian'] = false;
                } else {
                    $response = [];
                }
            } elseif (!empty($city) && !empty($street) && !empty($houseNumber)) {

                $response = $lService->findByAddress($street.' '.$houseNumber.' ,'.$city);

                if($response) {
                    $lService->setFromArray($response);
                    $response['pedestrian'] = false;
                    $response['house_number'] = $response['house'];

                } else {
                    $response = [];
                }

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
            $this->get('food.error_log')->write($this->getUser(), null, null, 'api_adress_change_find', 'api_null_request');
        }


        //$this->get('logger')->alert('Address:findAddressAction Response:' . print_r($response, true));
        //$this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    public function findStreetAction(Request $request)
    {
        $startTime = microtime(true);
        //$this->get('logger')->alert('Address:findStreetAction Request:', (array)$request);
        try {
            $queryPart = $request->get('query');
            $response = array();

            if (!empty($queryPart)) {
                $streets = $this->get('food.logistics')->findStreet($queryPart, 5);

                if (!empty($streets)) {
                    foreach ($streets as $street) {
                        if ($cityObj = $street->getCityId()) {
                            $response[] = [
                                'street' => $street->getStreet(),
                                'city' => $cityObj->getTitle(),
                                'city_id' => $cityObj->getId()
                            ];
                        }
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

        //$this->get('logger')->alert('Address:findStreetAction Response:' . print_r($response, true));
        //$this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }
}
