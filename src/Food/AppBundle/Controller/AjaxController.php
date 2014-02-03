<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class AjaxController extends Controller
{
    /**
     * @param $action
     * @return Response
     */
    public function ajaxAction($action)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        switch($action) {
            case 'find-address':
                $this->_ajaxActFindAddress($response,$this->getRequest()->get('city'), $this->getRequest()->get('address'));
                break;
            default:
                $response->setContent(json_encode(array(
                    'message' => 'Method not found :)',
                )));
                break;
        }

        return $response;
    }

    private function _ajaxActFindAddress($response, $city, $address)
    {
        $locations = $this->get('food.gis')->getCoordsOfPlace($address.', '.$city)->locations;
        $respData = array();
        if (!empty($locations)) {
            foreach ($locations as $loc) {
                $respData = array(
                    'name' => $loc->name,
                    'y' => $loc->feature->geometry->y,
                    'x' => $loc->feature->geometry->x
                );
            }
        }
        $response->setContent(json_encode(array(
            'data' => $respData
        )));
    }
}