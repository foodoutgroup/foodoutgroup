<?php

namespace Api\V2Bundle\Controller;

use Api\BaseBundle\Exceptions\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PlaceController extends Controller
{

    public function getAction($placeHash, Request $request){
        $return = ['success' => false];

        try {
            $ps = $this->get('api.v2.place');
            $place = $ps->getPlaceByHash($placeHash);

            $return['place'] = [
                'minCard' => $place->getCartMinimum(),
                'delivery' =>  [
                    'price' => $place->getDeliveryPrice(),
                    'time' => $place->getDeliveryTime(),
                    ],
                'payment' => [
                    'card' => $place->getCardOnDelivery(),
                    'cash' => true,
                ]
            ];


            $return['success'] = true;
        } catch (ApiException $e) {
            $return['message'] = $e->getMessage();
        }

        return  new JsonResponse($return);

    }

}
