<?php

namespace Food\AppBundle\Controller;

use Food\OrderBundle\Entity\Coupon;
use Food\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class AjaxController extends Controller
{
    /**
     * @param         $action
     * @param Request $request
     *
     * @return Response
     */
    public function ajaxAction($action, Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        switch ($action) {
            case 'find-street':
                $this->_ajaxFindStreet($response, $request->get('city'), $request->get('street'));
                break;
            case 'find-street-house':
                $this->_ajaxFindStreetHouse($response, $request->get('city'), $request->get('street'), $request->get('house'));
                break;
            case 'find-address':
                $this->_ajaxActFindAddress($response, $request->get('city'), $request->get('address'), $request);
                break;
            case 'find-address-and-recount':
                $this->_ajaxActFindAddress($response, $request->get('city'), $request->get('address'), $request);
                $this->_isPlaceInRadius($response, intval($request->get('place')));
                break;
            case 'check-coupon':
                $this->_ajaxCheckCoupon($response, $request->get('place_id'), $request->get('coupon_code'));
                break;
            default:
                $response->setContent(json_encode([
                    'message' => 'Method not found :)',
                ]));
                break;
        }

        return $response;
    }

    /**
     * @param Response $response
     * @param string   $city
     * @param string   $address
     * @param Request  $request
     */
    private function _ajaxActFindAddress(Response $response, $city, $address, Request $request)
    {

        $cityService = $this->get('food.city_service');

        if(!$city = $cityService->getCityById($city)){
            $city = $cityService->getDefaultCity();
        }


        $locationInfo = $this->get('food.googlegis')->groupData($address, $city->getTitle());

        $respData = [
            'success' => 0,
            'message' => $this->get('translator')->trans('index.address_not_found'),
            'adr'     => 0,
            'str'     => 0,
            'url'    => $this->get('slug')->getUrl($city->getId(), 'city'),
        ];




        if ((!$locationInfo['not_found'] || $locationInfo['street_found']) && $locationInfo['lng'] > 20 && $locationInfo['lat'] > 50) {
            $respData['success'] = 1;
            unset($respData['message']);
        }
        if (!$locationInfo['address_found']) {
            $respData['adr'] = 1;
        }
        if (!$locationInfo['street_found']) {
            $respData['str'] = 1;
        }


        if (!empty($respData) && $respData['success'] == 1 && $respData['adr'] == 1) {
            $session = $request->getSession();
            $session->set('locationData', ['address' => $address, 'city' => $city->getId(),'city_ids'=>'1']);
        }

        // Only City Selected
        $city_only = $request->get('city_only');
        if ($city && empty($address) && !empty($city_only)) {
            $this->get('food.googlegis')->setCityOnlyToSession($city->getId());
            $response->setContent(json_encode([
                'data' => [
                    'success' => 1,
                    'adr'     => 1,
                    'str'     => 0,
                    'url'    => $this->get('slug')->getUrl($city->getId(), 'city'),
                ]
            ]));
        } else {
            $response->setContent(json_encode([
                'data' => $respData
            ]));
        }
    }


    public function _ajaxFindStreet(Response $response, $city, $street)
    {
        $respData = [];
        $street = mb_strtoupper($street, 'utf-8');
        if ($city == "Rīga") {
            $city = "Ryga";
        }
        /*
        $street = str_replace("S", "[S|Š]", $street);
        $street = str_replace("A", "[A|Ą]", $street);
        $street = str_replace("C", "[C|Č]", $street);
        $street = str_replace("E", "[E|Ę|Ė]", $street);
        $street = str_replace("I", "[I|Į|Y]", $street);
        $street = str_replace("U", "[U|Ų|Ū]", $street);
        $street = str_replace("Z", "[Z|Ž]", $street);
        */
        $conn = $this->get('database_connection');

        // protect
        $street = strip_tags($street);
        $street = str_replace(['%', '_'], '', $street); // PDO doesn't do dis

        // query
        $sql = 'SELECT DISTINCT(street_name), `name`
                FROM nav_streets
                WHERE delivery_region = ? AND
                      street_name LIKE ?
                LIMIT 5';

        // get streets
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $city);
        $stmt->bindValue(2, "%$street%");
        $stmt->execute();
        $streets = $stmt->fetchAll();
        $gs = $this->get('food.googlegis');

        foreach ($streets as $key => &$streetRow) {
            if (empty($street['name'])) {
                $gdata = $gs->groupData($streetRow['street_name'], $city);
                if (isset($gdata['street_short']) && !empty($gdata['street_short'])) {
                    $streetRow['name'] = $gdata['street_short'];
                } else {

                }
                $sql = "UPDATE nav_streets SET `name`='" . $streetRow['name'] . "' WHERE delivery_region='" . $city . "' AND street_name='" . $streetRow['street_name'] . "'";
                $conn->query($sql);
            }
        }

        foreach ($streets as $str) {
            if (!empty($str['name']) && $str['name'] != "NULL") {
                $respData[] = ['value' => $str['name']];
            }
        }
        $response->setContent(json_encode($respData));
    }

    public function _ajaxFindStreetHouse(Response $response, $city, $street, $house)
    {
        $conn = $this->get('database_connection');

        $respData = [];

        // protect
        $street = mb_strtoupper($street, 'utf-8');
        $house = str_replace(['%', '_'], '', $house); // PDO doesn't do dis
        $house = htmlentities(addslashes(strip_tags($house)));
        $street = htmlentities(addslashes(strip_tags($street)));
        $city = htmlentities(addslashes(strip_tags($city)));

        $cityService = $this->get('food.city_service');

        if(!$city = $cityService->getCityById($city)){
            $city = $cityService->getDefaultCity();
        }


        // query
        $sql = 'SELECT DISTINCT(number_from)
                FROM nav_streets
                WHERE delivery_region = ? AND
                      street_name = ? AND
                      number_from LIKE ?';

        // get streets
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $city->getTitle());
        $stmt->bindValue(2, $street);
        $stmt->bindValue(3, "$house%");
        $stmt->execute();
        $streets = $stmt->fetchAll();

        foreach ($streets as $str) {
            $respData[] = ['value' => $str['number_from']];
        }
        $response->setContent(json_encode($respData));
    }

    /**
     * @param Response $response
     * @param integer  $placeId
     *
     * @todo dieve atleisk uz mano kaltes del json_encode - reik swiceri pakeisti kad contentas encodinamas priesh pati
     *     response grazinima
     */
    private function _isPlaceInRadius(Response $response, $placeId)
    {
        $cont = json_decode($response->getContent());

        $pointId = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->getPlacePointNear(
            $placeId,
            $this->get('food.googlegis')->getLocationFromSession()
        )
        ;

        $this->get('food.places')->saveRelationPlaceToPointSingle($placeId, $pointId);
        $cont->data->{'nodelivery'} = (!empty($pointId) ? 0 : 1);

        $response->setContent(json_encode($cont));
    }

    /**
     * @param Response $response
     * @param int      $placeId
     * @param string   $couponCode
     */
    private function _ajaxCheckCoupon(Response $response, $placeId, $couponCode)
    {
        $trans = $this->get('translator');
        $cont = [
            'status' => true,
            'data'   => []
        ];

        $orderService = $this->get('food.order');

        $coupon = $orderService->getCouponByCode($couponCode);
        $place = $this->container->get('food.places')->getPlace($placeId);

        $enableDiscount = !$place->getOnlyAlcohol();
        $list = $this->get('food.cart')->getCartDishes($place);
        foreach ($list as $dish) {
            if ($this->get('food.cart')->isAlcohol($dish->getDishId())) {
                $enableDiscount = false;
                break;
            }
        }

        if (!$coupon) {
            $cont['status'] = false;
            $cont['data']['error'] = $trans->trans('general.coupon.not_active');
        } elseif (!$coupon->isAllowedForWeb()) {
            $cont['status'] = false;
            $cont['data']['error'] = $trans->trans('general.coupon.only_api');
        } else if (!$orderService->validateCouponForPlace($coupon, $place)) {
            $cont['status'] = false;
            $cont['data']['error'] = $trans->trans(
                'general.coupon.wrong_place'
            );
        } else if (!$enableDiscount) {
            $cont['status'] = false;
            $cont['data']['error'] = $trans->trans('general.coupon.cannot_apply_for_alco');
        }

        // If everything is ok - do additional tests
        if ($cont['status'] == true) {
            // Only for navision restaurants
            if ($coupon->getOnlyNav() && !$place->getNavision()) {
                $cont['status'] = false;
                $cont['data']['error'] = $trans->trans('general.coupon.only_cili');
            }

            // Only for non self delivery restaurants
            if ($coupon->getNoSelfDelivery() && $place->getSelfDelivery()) {
                $cont['status'] = false;
                $cont['data']['error'] = $trans->trans('general.coupon.wrong_place');
            }

            // Coupon is still valid
            if ($coupon->getEnableValidateDate()) {
                $now = date('Y-m-d H:i:s');
                if ($coupon->getValidFrom() && $coupon->getValidFrom()->format('Y-m-d H:i:s') > $now) {
                    $cont['status'] = false;
                    $cont['data']['error'] = $trans->trans('general.coupon.coupon_too_early');
                }

                if ($coupon->getValidTo() && $coupon->getValidTo()->format('Y-m-d H:i:s') < $now) {
                    $cont['status'] = false;
                    $cont['data']['error'] = $trans->trans('general.coupon.coupon_expired');
                }
            }

            if ($coupon->getValidHourlyFrom() && $coupon->getValidHourlyFrom() > new \DateTime()) {
                $cont['status'] = false;
                $cont['data']['error'] = $trans->trans('general.coupon.coupon_too_early');
            }
            if ($coupon->getValidHourlyTo() && $coupon->getValidHourlyTo() < new \DateTime()) {
                $cont['status'] = false;
                $cont['data']['error'] = $trans->trans('general.coupon.coupon_expired');
            }

            $user = $this->container->get('security.context')->getToken()->getUser();

            if ($user instanceof User && $user->getIsBussinesClient() && $coupon->getB2b() == Coupon::B2B_NO) {
                $cont['status'] = false;
                $cont['data']['error'] = $trans->trans('general.coupon.not_for_business');
            }

            if ($coupon->getB2b() == Coupon::B2B_YES
                && (!($user instanceof User) || $user instanceof User && !$user->getIsBussinesClient())
            ) {
                $cont['status'] = false;
                $cont['data']['error'] = $trans->trans('general.coupon.only_for_business');
            }

            if ($user instanceof User && $orderService->isCouponUsed($coupon, $user)) {
                $cont['status'] = false;
                $cont['data']['error'] = $trans->trans('general.coupon.not_active');
            }
        }

        if ($cont['status'] == true) {
            $cont['data'] = $coupon->__toArray();
        }


        $response->setContent(json_encode($cont));
    }
}
