<?php

namespace Food\AppBundle\Controller;

use Sonata\Doctrine\Types\JsonType;
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

    /**
     * @param Response $response
     * @param string $city
     * @param string $address
     */
    private function _ajaxActFindAddress($response, $city, $address)
    {
        $location = $this->get('food.googlegis')->getPlaceData($address.', '.$city);
        $locationInfo = $this->get('food.googlegis')->groupData($location);

        $respData = array(
            'success' => 0,
            'message' => $this->get('translator')->trans('index.address_not_found')
        );
        if (!$locationInfo['not_found'] && $locationInfo['lng'] > 20 && $locationInfo['lat'] > 50) {
            $respData['success'] = 1;
            unset($respData['message']);
        }

        $response->setContent(json_encode(array(
            'data' => $respData
        )));
    }
}