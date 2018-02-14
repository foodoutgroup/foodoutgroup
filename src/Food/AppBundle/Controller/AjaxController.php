<?php

namespace Food\AppBundle\Controller;

use Food\AppBundle\Entity\Slug;
use Food\DishesBundle\Entity\PlaceRepository;
use Food\OrderBundle\Entity\Coupon;
use Food\OrderBundle\Service\OrderService;
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
            case 'check-event-email':
                $collection = $this->_isEmailInEvent($response->getContent(), $request->get('email'));
                break;
            case 'find-address-and-recount':
                $collection = $this->_isPlaceInRadius($response->getContent(), intval($request->get('place')));
                break;
            case 'check-coupon':
                $collection = $this->_ajaxCheckCoupon($request->get('place_id'), $request->get('coupon_code'));
                break;
            case 'delivery-price':
                $collection = $this->_ajaxGetDeliveryPrice($request->get('restaurant'), $request->get('time'));
                break;
            case 'autocomplete-address':
                $collection = $this->_autoCompleteAddress($request);
                break;
            case 'check-address':
                $collection = $this->_checkAddress($request, $request->get('place'));
                break;
            case 'get-address-by-location':
                $collection = $this->_getAddressByLocation($request);
                break;
            case 'delivery-type':
                $collection = ['success' => false];

                $this->get('session')->set('delivery_type', $request->get('type'));
                if ($request->get('redirect')) {
                    if ($request->get('address') != "") {
                        $collection = $this->_checkAddress($request, null);
                    } else {
                        $findAddress = $this->get('food.location')->findByIp($request->getClientIp());
                        try {
                            $cityId = $this->getDoctrine()->getRepository('FoodDishesBundle:PlacePoint')->findNearestCity($findAddress);
                            $collection['success'] = true;
                            $collection['url'] = $this->get('slug')->getUrl($cityId, Slug::TYPE_CITY);
                        } catch (\Exception $e) {
                            $collection['success'] = false;
                            $collection['message'] = $e->getMessage();// $this->get('translator')->trans('location.cant.be.located');
                        }
                    }

                }

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

        $pointId = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->getPlacePointNearWithWorkCheck($placeId,
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
            'data' => []
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

        if (isset($cont['data']['error']) && !empty($cont['data']['error'])) {

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

    private function _ajaxGetDeliveryPrice($restaurant, $time)
    {

        $date = date('Y-m-d ') . $time . ':00';
        $location = $this->get('food.location')->get();
        /**
         * @var $placeRepo PlaceRepository
         */
        $placeRepo = $this->getDoctrine()->getRepository("FoodDishesBundle:Place");


        $placePointId = $placeRepo->getPlacePointNear($restaurant, $location, false, $date);
        if (!empty($placePointId)) {
            $place = $placeRepo->find($restaurant);
            $placePoint = $this->getDoctrine()->getRepository("FoodDishesBundle:PlacePoint")->find($placePointId);
            $deliveryPrice = $this->container->get('food.cart')->getDeliveryPrice($place, $location, $placePoint, true);
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


        $rsp = json_decode($curl->get($this->container->getParameter('geo_provider') . '/autocomplete', [
            'input' => $term,
            'components' => 'country:' . strtoupper($this->container->getParameter('country')),
//            'language' => $request->getLocale(),
            'types' => 'geocode',
        ])->body);

        $assets = $this->get('templating.helper.assets');

        foreach ($rsp->collection as $item) {

            $label = $item->output;
            $j = null;

            foreach ($item->matched_substrings as $boldRange) {
                $str = mb_substr($item->output, $boldRange->offset, $boldRange->length, 'UTF-8');
                $label = str_replace($str, "<b>" . $str . "</b>", $label);
            }
            $imgUrl = $assets->getUrl('bundles/foodapp/images/ic_marker.png');
            $addressCollection[] = [
                'id' => $item->id,
                'label' => "<img src=\"$imgUrl\"/> " . $label,
                'value' => $item->output,
                'data' => $item->matched_substrings,
                'class' => '',
            ];
        }

        $user = $this->getUser();
        if ($user) {
            /**
             * @var $userAddress UserAddress
             */
            $userAddress = $this->getDoctrine()->getRepository('FoodUserBundle:UserAddress')->getDefault($user);
            if ($userAddress) {
                $imgUrlHome = $assets->getUrl('bundles/foodapp/images/ic_home.png');

                $add = true;
                foreach ($addressCollection as $all) {
                    if ($all['id'] == $userAddress->getAddressId()) {
                        $add = false;
                        break;
                    }
                }
                if ($add && $userAddress->getAddressId()) {
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

    private function _checkAddress(Request $request, $place = null)
    {

        $rsp = ['success' => false];

        $lService = $this->get('food.location');
        $response = $lService->findByHash($request->get("address"));

        if ($request->get("type") == 'badge') {
            $this->get('session')->set('badge', 1);
        }

        $t = $this->get('translator');


        if ($response) {
            if ($place != null) {
                $placePointMap = array();
                $placePoint = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->getPlacePointNear($place, $response, false, false);
                if (empty($placePoint)) {
                    $rsp['message'] = $t->trans('place_point_does_not_deliver');
                    $rsp['place_point_error'] = 1;
                    return $rsp;
                } else {
                    $placePointMap[$place] = $placePoint;
                    $this->container->get('session')->set('point_data', $placePointMap);
                }
            }

            $rsp['detail'] = $response;

            if (empty($response['house'])) {
                $rsp['message'] = $t->trans('error.house.not.found');
            } elseif (!is_null($response['city_id'])) {
                $rsp['success'] = true;
                $rsp['url'] = $this->get('slug')->get($response['city_id'], Slug::TYPE_CITY);
                $lService->clear()->set($response);
            } elseif ($settingRestaurantList = (int)$this->get('food.app.utils.misc')->getParam('page_restaurant_list', 0)) {
                $rsp['success'] = true;
                $rsp['url'] = $this->get('slug')->getUrl($settingRestaurantList, Slug::TYPE_PAGE);
                $lService->clear()->set($response);
            } else {
                $rsp['message'] = $t->trans('in.this.city.we.have.not.delivered.food');
            }
        } else {
            $rsp['message'] = $t->trans('address.not.found.please.contact.us');
        }

        return $rsp;
    }

    private function _getAddressByLocation(Request $request)
    {
        $rsp = ['success' => false, 'detail' => null];
        $lService = $this->get('food.location');
        $response = $lService->findByCords($request->get('lat'), $request->get('lng'));
        if ($response) {
            $rsp['success'] = true;
            $rsp['detail'] = $response;
        } else {
            $t = $this->get('translator');
            $rsp['message'] = $t->trans('cant.get.your.location');
        }

        return $rsp;
    }

    private function _isEmailInEvent($content, $email)
    {
        $reportService = $this->get('food.report');

        $response = ['success' => false, 'message' => ''];

        $result = $reportService->saveEmail($email);
        $t = $this->get('translator');
        if ($result) {
            $response['success'] = true;
        } else {
            $response['message'] = $t->trans('already.exists');
        }

        return $response;
    }

}
