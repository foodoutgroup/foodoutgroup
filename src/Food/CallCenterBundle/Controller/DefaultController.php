<?php

namespace Food\CallCenterBundle\Controller;

use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\Common\Collections\ArrayCollection;
use Food\CallCenterBundle\Controller\Decorators\DefaultDecorator;

/**
 * @Route("/callcenter")
 */
class DefaultController extends Controller
{
    const SESSION_CALLCENTER_PLACE = 'callcenter_place';
    const SESSION_CALLCENTER_USER = 'callcenter_user';
    const SESSION_CALLCENTER_ADDRESS = 'callcenter_address';

    protected $cities = ['Vilnius' => 'Vilnius',
                         'Kaunas' => 'Kaunas',
                         'Klaipėda' => 'Klaipėda'];

    use DefaultDecorator;

    /**
     * @var CartServiceworkingHoursToday
     */
    private $cartService;

    /**
     * @Route("/", name="food_callcenter")
     * @Template
     */
    public function indexAction(Request $request)
    {
        $placeService = $this->get('food.places');
        $userPlace = $this->getUserPlace();
        $place = $userPlace->is_some() ? $userPlace->val() : $this->getPlaceById($this->getPlaceFromSession());
        $miscService = $this->get('food.app.utils.misc');
        $inCart = false;
        $hideDelivery = $takeAway = $enable_free_delivery_for_big_basket = $displayCartInterval = false;
        $deliveryTotal = $total_cart = $cartFromMin = $cartFromMax = $left_sum = 0;
        $basketErrors = array(
            'foodQuantityError' => false,
            'drinkQuantityError' => false,
        );

        if ($place) {
            $list = $this->getCartService()->getCartDishes($place);
            $total_cart = $this->getCartService()->getCartTotal($list/*, $place*/);
            $cartFromMin = $this->get('food.places')->getMinCartPrice($place->getId());
            $cartFromMax = $this->get('food.places')->getMaxCartPrice($place->getId());
            $enable_free_delivery_for_big_basket = $miscService->getParam('enable_free_delivery_for_big_basket');
            $left_sum = 0;

            $takeAway = ($this->container->get('session')->get('delivery_type', false) == OrderService::$deliveryPickup);

            if ($takeAway) {
                $displayCartInterval = false;
            } else {
                $placePointMap = $this->container->get('session')->get('point_data');

                $gis = $this->get('food.googlegis')->getLocationFromSession();

                if (empty($placePointMap) || !isset($placePointMap[$place->getId()])) {
                    $deliveryTotal = $place->getDeliveryPrice();
                } elseif (!$gis['not_found']) {
                    // TODO Trying to catch fatal when searching for PlacePoint
                    if (!isset($placePointMap[$place->getId()]) || empty($placePointMap[$place->getId()])) {
                        $this->container->get('logger')->error('Trying to find PlacePoint without ID in CartBundle Default controller - sideBlockAction');
                    }
                    $pointRecord = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointMap[$place->getId()]);
                    $deliveryTotal = $this->getCartService()->getDeliveryPrice(
                        $place,
                        $gis,
                        $pointRecord
                    );
                    $displayCartInterval = false;
                    $cartFromMin = $this->getCartService()->getMinimumCart(
                        $place,
                        $gis,
                        $pointRecord
                    );
                }

                // Check cart limits
                $basketDrinkLimit = $place->getBasketLimitDrinks();
                $basketFoodLimit = $place->getBasketLimitFood();
                if (!empty($basketFoodLimit) && $basketFoodLimit > 0) {
                    $foodDishCount = 0;

                    foreach ($list as $dish) {
                        $foodCat = $dish->getDishId()->getCategories();
                        if (!$foodCat[0]->getDrinks()) {
                            $foodDishCount = $foodDishCount + (1 * $dish->getQuantity());
                        }

                        if ($foodDishCount > $basketFoodLimit) {
                            $basketErrors['foodQuantityError'] = true;
                            break;
                        }
                    }
                }

                if (!empty($basketDrinkLimit) && $basketDrinkLimit > 0) {
                    $foodDishCount = 0;

                    foreach ($list as $dish) {
                        $foodCat = $dish->getDishId()->getCategories();
                        if ($foodCat[0]->getDrinks()) {
                            $foodDishCount = $foodDishCount + (1 * $dish->getQuantity());
                        }

                        if ($foodDishCount > $basketDrinkLimit) {
                            $basketErrors['drinkQuantityError'] = true;
                            break;
                        }
                    }
                }
            }
        }

        $noMinimumCart = false;
        $current_user = $this->getUserFromSession(true);
        //$current_user = $this->container->get('security.context')->getToken()->getUser();
        if (!empty($current_user) && is_object($current_user)) {
            $noMinimumCart = $current_user->getNoMinimumCart();
            $this->get('food.user')->getDiscount($current_user);
        }

        $applyDiscount = $freeDelivery = $discountInSum = false;
        $discountSize = null;
        $discountSum = null;

        // Business client discount
        if (!empty($current_user) && is_object($current_user) && $current_user->getIsBussinesClient()) {
            if (!$takeAway && !$place->getSelfDelivery()) {
                $applyDiscount = true;
                $discountSize = $this->get('food.user')->getDiscount($current_user);
                $discountSum = $this->getCartService()->getTotalDiscount($list, $discountSize);

                $total_cart -= $discountSum;
            }
        }
        // If coupon in use
        elseif (!empty($couponCode)) {
            $coupon = $this->get('food.order')->getCouponByCode($couponCode);

            if ($coupon) {
                $applyDiscount = true;
                $freeDelivery = $coupon->getFreeDelivery();

                if (!$freeDelivery) {
                    $discountSize = $coupon->getDiscount();
                    if (!empty($discountSize)) {
                        $discountSum = $this->getCartService()->getTotalDiscount($list,$coupon->getDiscount());
                    } else {
                        $discountSize = null;
                        $discountInSum = true;
                        $discountSum = $coupon->getDiscountSum();
                    }

                    $total_cart = $total_cart - $discountSum;
                    if ($total_cart < 0) {
                        if ($coupon->getFullOrderCovers()) {
                            $deliveryTotal = $deliveryTotal + $total_cart;
                            if ($deliveryTotal < 0) {
                                $deliveryTotal = 0;
                            }
                        }
                        $total_cart = 0;
                    }
                }
            }
        }

        $locData = $this->get('food.googlegis')->getLocationFromSession();
        $places = $userPlace->is_none() ? $this->getPlaces($locData, []) : [$userPlace->val()];
        if (empty($place) && count($places) > 1) {
            if (is_array($places) && !is_object($places)) {
                $place = $places[0];
            } else {
                $place = $places->first();
            }
            $place = $place['place'];
        }

        $list = $this->get('food.cart')->getCartDishes($place);
        $totalCart = $this->get('food.cart')->getCartTotal($list);

        $places = new ArrayCollection($places);
        if (is_null($place) && count($places) > 1 && !is_null($locData)) {
            if (is_array($places) && !is_object($places)) {
                $place = $places[0];
            } else {
                $place = $places->first();
            }
            $place = $place['place'];
        }
        // location form
        $locationForm = $this->getLocationForm();
        if (!empty($locData) && empty($locData['not_found'])) {
            $locationForm->get('city')->setData($locData['city']);
            $locationForm->get('street')->setData($locData['street']);
            $locationForm->get('house')->setData($locData['street_nr']);
        }

        $session = $this->get('session');
        $session->set('isCallcenter', true);

        return [
            'location_form' => $locationForm->createView(),
            'places' => $places->toArray(),
            'place_form' => $this->getPlacesForm($places->toArray())->createView(),
            'place' => $place,
            'list' => $list,
            'inCart' => (int)$inCart,
            'hide_delivery' => $hideDelivery,
            'applyDiscount' => $applyDiscount,
            'discountSize' => $discountSize,
            'discountSum' => $discountSum,
            'total_cart' => $totalCart,
            'total_with_delivery' => ($freeDelivery ? $total_cart : ($total_cart + $deliveryTotal)),
            'selected_place' => $this->getPlaceFromSession() ? true : false,
            'location' => $this->get('food.googlegis')->getLocationFromSession(),
            'isCallcenter' => true,
            'location_data' => $locData,
            'userAllAddress' => $placeService->getCurrentUserAddresses(),
            'freeDelivery' => false,
            'total_delivery' => $deliveryTotal,
            'discountInSum' => false,
            'noMinimumCart' => $noMinimumCart,
            'cart_from_min' => sprintf('%.2f',$cartFromMin),
            'cart_from_max' => $cartFromMax,
            'basket_errors' => $basketErrors,
            'takeAway' => ($takeAway ? true : false),
            'enable_free_delivery_for_big_basket' => $enable_free_delivery_for_big_basket,
            'left_sum' => $left_sum,
            'display_cart_interval' => $displayCartInterval,
            'order' => null,
            'dataToLoad' => $request->request->all(),
        ];
    }

    /**
     * @Route("/load-menu/{placeId}", name="food_callcenter_load_menu", options={"expose"=true})
     * @Template
     */
    public function loadMenuAction($placeId)
    {
        $place = $this->getPlaceById($placeId);
        //$dishes = $this->getDishesByPlace($place);
        $categoryList = $this->get('food.places')->getActiveCategories($place);

        // save selected place into session
        $this->putPlaceIntoSession($placeId);

        return [
            'place' => $place,
            //'items' => $dishes,
            'categoryList' => $categoryList,
            'isCallcenter' => true
        ];
    }

    /**
     * @Route("/reset", name="food_callcenter_reset", options={"expose"=true})
     * @Template
     */
    public function resetAction()
    {
        $this->reset();

        return [];
    }

    /**
     * @Route("/checkout", name="food_callcenter_checkout", options={"expose"=true})
     * @Template("FoodCartBundle:Default:form.html.twig")
     */
    public function checkoutAction(Request $request)
    {
        $user = $this->getUserFromSession(true);
        $address = $this->getAddressFromSession(true);
        $placeId = $this->getPlaceFromSession();
        $placeService = $this->get('food.places');

        if (!$placeId) return new Response('');

        $place = $placeService->getPlace($placeId);

        // Data preparation for form
        $placePointMap = $this->container->get('session')->get('point_data');
        if (!empty($placePointMap) && isset($placePointMap[$placeId]) && !empty($placePointMap[$placeId])) {
            $pointRecord = $this->container->get('doctrine')->getRepository('FoodDishesBundle:PlacePoint')->find($placePointMap[$placeId]);
        } else {
            $pointRecord = null;
        }

        $workingHoursForInterval = [];
        $workingDaysCount = 4;
        for ($i = 0; $i <= $workingDaysCount; $i++) {
            $workingHoursForInterval[date("Y-m-d", strtotime("+" . $i . " day"))] = $placeService->getFullRangeWorkTimes($place, $pointRecord, "+" . $i . " day");
        }

        // PreLoad UserAddress Begin
        $address = null;
        $session = $request->getSession();
        $locationData = $session->get('locationData');
        $current_user = $user;//$this->container->get('security.context')->getToken()->getUser();

        if (!empty($locationData) && !empty($current_user) && is_object($current_user)) {
            $address = $placeService->getCurrentUserAddress($locationData['city'], $locationData['address']);
        }

        if (empty($address) && !empty($current_user) && is_object($current_user)) {
            $defaultUserAddress = $current_user->getCurrentDefaultAddress();
            if (!empty($defaultUserAddress)) {
                $loc_city = $defaultUserAddress->getCity();
                $loc_address = $defaultUserAddress->getAddress();
                $address = $placeService->getCurrentUserAddress($loc_city, $loc_address);
            }
        }
        // PreLoad UserAddress End

        $takeAway = ($this->container->get('session')->get('delivery_type', false) == OrderService::$deliveryPickup);

         if(!isset($coupon)) {
            $coupon = false;
        }

        return [
            'place' => $place,
            'formHasErrors' => false,
            'inCart' => false,
            'order' => null,
            'takeAway' => ($takeAway ? true : false),
            'location' => $this->get('food.googlegis')->getLocationFromSession(),
            'dataToLoad' => $request->request->all(),
            'isCallcenter' => true,
            'submitted' => false,
            'user' => $user,
            'address' => $address,
            'userAllAddress' => $placeService->getCurrentUserAddresses(),
            'userAddress' => $address,
            'require_lastname' => false,
            'workingHoursForInterval' => $workingHoursForInterval,
            'workingDaysCount' => $workingDaysCount,
        ];
    }

    /**
     * @Route("/retrieve-location", name="food_callcenter_retrieve_location", options={"expose"=true})
     * @Template
     */
    public function retrieveLocationAction()
    {
        $location = $this->get('food.googlegis')->getLocationFromSession();
        $data = ['city' => \Maybe($location['city'])->val(''),
                 'address_orig' => \Maybe($location['address_orig'])->val('')];

        return new Response(json_encode($data));
    }

    /**
     * @Route("/get-places-by-location", name="food_callcenter_get_places_by_location", options={"expose"=true})
     * @Template
     */
    public function getPlacesByLocationAction()
    {
        $result = [];
        $recommended = false;
        $userPlace = $this->getUserPlace();
        $place = $userPlace->is_some() ? $userPlace->val() : $this->getPlaceById($this->getPlaceFromSession());
        $filters = [];
        $placeId = 0;

        // get places from location
        if ($userPlace->is_some()) {
            $filters['id'] = $userPlace->getId()->val(0);
        }

        // get location data
        $locData = $this->get('food.googlegis')->getLocationFromSession();

        $places = $this->getPlaces($locData, $filters);
        $places = new ArrayCollection($places);

        // format places
        foreach ($places as $value) {
            $result[] = ['id' => $value['place_id'],
                         'text' => $value['place']->getName()];
        }

        if (is_null($place) && count($places) > 1 && !is_null($locData)) {
            $place = $places->first();
            $placeId = $place['place_id'];
        } else {
            $placeId = $place->getId();
        }

        return new JsonResponse(['places' => $result, 'location' => $locData, 'place' => $placeId]);
    }

    /**
     * @Route("/get-location", name="food_callcenter_get_location", options={"expose"=true})
     * @Template
     */
    public function getLocationAction(Request $request)
    {
        $city = $request->get('city');
        $street = $request->get('street');
        $houseNumber = $request->get('house_number');

        if (!empty($city) && empty($street) && empty($houseNumber)) {
            $this->get('food.googlegis')->setCityOnlyToSession($city);
            $locData = $this->get('food.googlegis')->getLocationFromSession();
            return new JsonResponse($locData);
        }

        $locData = $this->get('food.googlegis')->getLocationFromSession();
        return new JsonResponse($locData);
    }

    /**
     * @Route("/get-address-by-phone/{phone}", name="food_callcenter_get_address_by_phone", options={"expose"=true})
     * @Template
     */
    public function getAddressAction($phone)
    {
        $usersByPhone = $this->container->get('doctrine')->getRepository('FoodUserBundle:User')->findByPhone($phone);
        $addresses = array();

        if (count($usersByPhone)) {
            foreach ($usersByPhone as $userByPhone) {
                foreach ($userByPhone->getAddress() as $addrRow) {
                    $addr = $addrRow->getAddress();
                    $streetAddr = "";
                    $houseNumber = "";
                    if (preg_match("/(\d+\w*\s*-\s*\d+)/i", $addr, $matches)) {
                        $addressSplt = explode("-", $matches[1]);
                        $tmp = $addressSplt[0];
                        if ($tmp == intval($tmp)) {
                            $streetAddr = strstr($addr, $matches[1], true);
                            $houseNumber = $tmp;
                        }
                    } else {
                        $addressSplt = explode(" ", $addr);
                        $houseNumber = end($addressSplt);
                        unset($addressSplt[sizeof($addressSplt) - 1]);
                        $streetAddr = implode(" ", $addressSplt);
                    }
                    $addresses[trim($addrRow->getCity().$streetAddr.$houseNumber)] = array(
                        'userId' => $addrRow->getUser()->getId(),
                        'addressId' => $addrRow->getId(),
                        'city' => $addrRow->getCity(),
                        'address' => $addrRow->getAddress(),
                        'street' => $streetAddr,
                        'house' => $houseNumber
                    );
                }
            }
        }

        return array(
            'user_found' => (boolean) count($usersByPhone),
            'addresses' => $addresses
        );
    }

    /**
     * @Route("/set-user/{userId}", name="food_callcenter_set_user", options={"expose"=true})
     */
    public function setUserAction($userId)
    {
        $this->putUserIntoSession($userId);
        return new JsonResponse(array(
            'success' => true
        ));
    }
    /**
     * @Route("/set-address/{addressId}", name="food_callcenter_set_address", options={"expose"=true})
     */
    public function setAddressAction($addressId)
    {
        $this->putAddressIntoSession($addressId);
        return new JsonResponse(array(
            'success' => true
        ));
    }

    /**
     * @param \Food\CartBundle\Service\CartService $cartService
     */
    public function setCartService($cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * @return \Food\CartBundle\Service\CartService
     */
    public function getCartService()
    {
        if (empty($this->cartService)) {
            $this->setCartService($this->get('food.cart'));
        }
        return $this->cartService;
    }
}
