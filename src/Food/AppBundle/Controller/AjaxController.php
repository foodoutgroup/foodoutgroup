<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class AjaxController extends Controller
{
    /**
     * @param $action
     * @param Request $request
     * @return Response
     */
    public function ajaxAction($action, Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        switch($action) {
            case 'find-address':
                $this->_ajaxActFindAddress($response,$request->get('city'), $request->get('address'));
                break;
            case 'find-address-and-recount':
                $this->_ajaxActFindAddress($response,$request->get('city'), $request->get('address'));
                $this->_isPlaceInRadius($response, intval($request->get('place')));
                break;
            case 'check-coupon':
                $this->_ajaxCheckCoupon($response, $request->get('place_id'), $request->get('coupon_code'));
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
    private function _ajaxActFindAddress(Response $response, $city, $address)
    {
        $location = $this->get('food.googlegis')->getPlaceData($address.', '.$city);
        $locationInfo = $this->get('food.googlegis')->groupData($location, $address, $city);

        $respData = array(
            'success' => 0,
            'message' => $this->get('translator')->trans('index.address_not_found'),
            'adr' => 0,
            'str' => 0
        );
        if ((!$locationInfo['not_found'] || $locationInfo['street_found']) && $locationInfo['lng'] > 20 && $locationInfo['lat'] > 50) {
            $respData['success'] = 1;
            unset($respData['message']);
        }
        if (!$locationInfo['not_found']) {
            $respData['adr'] = 1;
        }
        if (!$locationInfo['street_found']) {
            $respData['str'] = 1;
        }
        $response->setContent(json_encode(array(
            'data' => $respData
        )));
    }

    /**
     * @param Response $response
     * @param integer $placeId
     *
     * @todo dieve atleisk uz mano kaltes del json_encode - reik swiceri pakeisti kad contentas encodinamas priesh pati response grazinima
     */
    private function _isPlaceInRadius(Response $response, $placeId)
    {
        $cont = json_decode($response->getContent());

        $pointId = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->getPlacePointNear(
            $placeId,
            $this->get('food.googlegis')->getLocationFromSession()
        );
        $this->get('food.places')->saveRelationPlaceToPointSingle($placeId, $pointId);
        $cont->data->{'nodelivery'} = (!empty($pointId) ? 0: 1);
        $response->setContent(json_encode($cont));
    }

    private function _ajaxCheckCoupon(Response $response, $placeId, $couponCode)
    {
        $trans = $this->get('translator');
        $cont = array(
            'status' => true,
            'data' => array()
        );

        $coupon = $this->get('food.order')->getCouponByCode($couponCode);

        if (!$coupon) {
            $cont['status'] = false;
            $cont['data']['error'] = $trans->trans('general.coupon.not_active');
        } else if ($coupon->getPlace() && $coupon->getPlace()->getId() != $placeId) {
            $cont['status'] = false;
            $cont['data']['error'] = $trans->trans(
                'general.coupon.wrong_place',
                array('%place_name%' => $coupon->getPlace()->getName())
            );
        } else {
            $cont['data'] = $coupon->__toArray();
        }


        $response->setContent(json_encode($cont));
    }
}