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
        $this->get('logger')->alert('Address:findAddressAction Request:', (array) $request);
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

        if(empty($response)){
            $user = $this->getUser();
            $userIp = ($this->container->get('request')->getClientIp());
            $error = new ErrorLog();

            $error->setIp($userIp);
            //~ $error->setCart(null);
            $error->setCreatedBy($user);
            $error->setPlace(null);
            $error->setCreatedAt(new \DateTime('now'));
            $error->setUrl($request->getUri());
            $error->setSource('api_adress_change_find');
            $error->setDescription('api_null_request');
            $error->setDebug(serialize($request));

            $em = $this->container->get('doctrine')->getManager();
            $em->persist($error);
            $em->flush();
        }


        $this->get('logger')->alert('Address:findAddressAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    public function findStreetAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Address:findStreetAction Request:', (array) $request);
        try {
            $queryPart = $request->get('query');
            $response = array();

            if (!empty($queryPart)) {
                $streets = $this->get('food.logistics')->findStreet($queryPart, 5);

                if (!empty($streets)) {
                    foreach ($streets as $street) {
                        $response[] = array(
                            'street' => $street->getStreet(),
                            'city' => $street->getCityId()->getTitle(),
                        );
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

        $this->get('logger')->alert('Address:findStreetAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }
}
