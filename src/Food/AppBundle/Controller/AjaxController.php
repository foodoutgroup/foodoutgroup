<?php

namespace Food\AppBundle\Controller;

use Food\AppBundle\Entity\Slug;
use Food\OrderBundle\Entity\Coupon;
use Food\UserBundle\Entity\User;
use Food\UserBundle\Entity\UserAddress;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Food\AppBundle\Entity\ErrorLog;

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
            case 'find-address-and-recount':
                $collection = $this->_isPlaceInRadius($response->getContent(), intval($request->get('place')));
                break;
            case 'check-coupon':
                $collection = $this->_ajaxCheckCoupon($request->get('place_id'), $request->get('coupon_code'));
                break;
            case 'delivery-price':
                $collection = $this->_ajaxGetDeliveryPrice($request->get('restaurant'),$request->get('time'));
                break;
            case 'autocomplete-address':
                $collection = $this->_autoCompleteAddress($request);
                break;
            case 'check-address':
                $collection = $this->_checkAddress($request);
                break;
            case 'get-address-by-location':
                $collection = $this->_getAddressByLocation($request);
                break;
            case 'delivery-type':
                $this->get('session')->set('delivery_type', $request->get('type'));
                break;
            default:
                $collection = ['message' => 'Method not found :)'];
                break;
        }
        $response->setContent(json_encode($collection));

        return $response;
    }


    private function _isPlaceInRadius($content, $placeId)
    {
        $cont = json_decode($content);

        $pointId = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->getPlacePointNearWithWorkCheck(
            $placeId,
            $this->get('food.location')->get()
        );

        $this->get('food.places')->saveRelationPlaceToPointSingle($placeId, $pointId);
        $cont->data->{'nodelivery'} = (!empty($pointId) ? 0 : 1);

        return $cont;
    }

    /**
     * @param int $placeId
     * @param string $couponCode
     * @return array
     */
    private function _ajaxCheckCoupon($placeId, $couponCode)
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

        if(isset($cont['data']['error']) && !empty($cont['data']['error'])){

            $this->get('food.error_log')->write(
                $this->getUser(),
                $this->container->get('food.cart')->getSessionId(),
                $place,
                'checkout_coupon_page',
                $cont['data']['error']
            );
        }

        return $cont;
    }

    private function _ajaxGetDeliveryPrice($restaurant,$time)
    {
        $em = $this->container->get('doctrine')->getManager();

        $date = date('Y-m-d ').$time.':00';
        $location = $this->get('food.location')->get();
        $placeRepo = $em->getRepository("FoodDishesBundle:Place");
        $place = $placeRepo->find($restaurant);
        $placePointId = $placeRepo->getPlacePointNear($restaurant,$location,false,$date);

        if(!empty($placePointId)) {
            $placePointRepo = $em->getRepository("FoodDishesBundle:PlacePoint");
            $cartService = $this->container->get('food.cart');

            $placePoint = $placePointRepo->find($placePointId);

            $deliveryPrice = $cartService->getDeliveryPrice(
                $place,
                $location,
                $placePoint,
                true
            );

        } else {
            $deliveryPrice = '';
        }

        return $deliveryPrice;
    }

    private function _autoCompleteAddress(Request $request)
    {
        $addressCollection = [];

        $term = $request->get('term');

        $curl = new \Curl();
        try {
            $rsp = json_decode($curl->get($this->container->getParameter('geo_provider') . '/autocomplete', [
                'input' => $term,
                'components' => 'country:' . strtoupper($this->container->getParameter('country')),
                'language' => $request->getLocale(),
                'types' => 'geocode',
            ])->body);
        } catch (\Exception $e)
        {
            var_dump($e->getMessage());
            var_dump(json_last_error());
            die();
        }

        $assets =  $this->get('templating.helper.assets');

        foreach ($rsp->collection as $item) {

            $label = $item->output;
            $j = null;

            foreach ($item->matched_substrings as $boldRange) {
                $str = mb_substr($item->output, $boldRange->offset, $boldRange->length, 'UTF-8');
                $label = str_replace($str, "<b>".$str."</b>", $label);
            }
            $imgUrl = $assets->getUrl('bundles/foodapp/images/ic_marker.png');
            $addressCollection[] = [
                'id' => $item->id,
                'label' => "<img src=\"$imgUrl\"/> ".$label,
                'value' => $item->output,
                'data' => $item->matched_substrings,
                'class' => '',
            ];
        }

        $user = $this->getUser();
        if($user) {
            /**
             * @var $userAddress UserAddress
             */
            $userAddress = $this->getDoctrine()->getRepository('FoodUserBundle:UserAddress')->getDefault($user);
            if($userAddress) {
                $imgUrlHome = $assets->getUrl('bundles/foodapp/images/ic_home.png');

                $add = true;
                foreach ($addressCollection as $all) {
                    if($all['id'] == $userAddress->getAddressId()) {
                        $add = false;
                        break;
                    }
                }
                if($add && $userAddress->getAddressId()) {
                    $addressCollection[] = [
                        'id' => $userAddress->getAddressId(),
                        'label' => "<img src=\"$imgUrlHome\"/>&nbsp;&nbsp;<u>" . $userAddress->getOrigin() . "</u>",
                        'value' => $userAddress->getOrigin(),
                        'data' => null,
                        'class' => 'user-address',
                    ];
                }
            }
        }

        return $addressCollection;

    }

    private function _checkAddress(Request $request)
    {
        $rspDefault = ['success' => false];

        $locationService = $this->get('food.location');

        $curl = new \Curl();
        $rsp = json_decode($curl->get($this->container->getParameter('geo_provider').'/geocode', [
            'hash' => $request->get('address'),
            'language' => $request->getLocale(),
            'types' => 'geocode',
        ])->body, true);

        $rsp  = array_merge($rspDefault, $rsp);
        $t = $this->get('translator');

        $flat = $request->get('flat', null);
        if(strlen(trim($flat)) == 0) {
            $flat = null;
        }

        if($rsp['success']) {

            $d = $rsp['detail'];

            if(empty($d['house'])) {
                $rsp['message'] = $t->trans('error.house.not.found');
                $rsp['success'] = false;
            } else {
                $city = $this->get('food.city_service')->getCityByName($d['city']);
                if(!$city) {

                    $settingRestaurantList = (int) $this->get('food.app.utils.misc')->getParam('page_restaurant_list', 0);
                    if($settingRestaurantList) {

                        $rsp['url'] = $this->get('slug')->getUrl($settingRestaurantList, Slug::TYPE_PAGE);
                        $locationService->clear()->set($rsp['detail'], $flat);

                    } else {
                        $rsp['success'] = false;
                        $rsp['message'] = $t->trans('in.this.city.we.have.not.delivered.food');
                    }
                } else {
                    $rsp['url'] = $this->get('slug')->getUrl($city->getId(), Slug::TYPE_CITY);
                    $locationService->clear()->set($rsp['detail'], $flat);

                }
            }

        } else {
            $rsp['message'] = $t->trans('error.server.problem.'.str_replace(" ", ".", strtolower(trim($rsp['message']))));
        }

        return $rsp;
    }

    private function _getAddressByLocation(Request $request)
    {
        $rspDefault = ['success' => false];

        $curl = new \Curl();
        $rsp = json_decode($curl->get($this->container->getParameter('geo_provider').'/geocode', [
            'lat' => $request->get('lat'),
            'lng' => $request->get('lng'),
            'language' => $request->getLocale(),
            'types' => 'geocode',
        ])->body, true);

        return array_merge($rspDefault, $rsp);
    }

}
