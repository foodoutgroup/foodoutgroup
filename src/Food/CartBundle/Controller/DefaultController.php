<?php

namespace Food\CartBundle\Controller;

use Food\CartBundle\Service\CartService;
use Food\DishesBundle\Entity\Place;
use Food\OrderBundle\Entity\Order;
use Food\UserBundle\Entity\User;
use FOS\UserBundle\Model\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @var CartService
     */
    private $cartService;

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

    /**
     * Daz proxy for ajax requests :)
     *
     * @param string $action
     * @param Request $request
     * @return Response
     */
    public function actionAction($action, Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $jsonResponseData = array();

        switch($action) {
            case 'add':
                $this->_actonAddItem($jsonResponseData, $request);
                break;
            case 'add-option':
                break;
            case 'remove':
                $this->_actonRemoveItem($jsonResponseData, $request);
                break;
            case 'remove-option':
                break;
            case 'set_delivery':
                $this->_actionSetDelivery($request);
                break;
            case 'refresh':
                break;
        }
        /*
        $jsonResponseData['items'] = $this->getCartService()->getCartDishesForJson(
            $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find(
                $this->getRequest()->get('place')
            )
        );
        */

        $place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find(
            $request->get('place', 0)
        );

        // Somehow we've lost the place.. dont crash.. better show nothing
        if (!$place) {
            return new Response('');
        }

        $jsonResponseData['block'] = $this->sideBlockAction(
            $place,
            true,
            $request->get('in_cart', false),
            null,
            $request->get('take_away', false),
            $request->get('coupon_code', null)
        );

        $response->setContent(json_encode($jsonResponseData));
        return $response;
    }

    /**
     * @param $responseData
     * @param Request $request
     */
    private function _actonAddItem(&$responseData, $request)
    {
        if (intval($request->get('counter')) > 0) {
            $this->getCartService()->addDishBySizeId(
                $request->get('dish-size'),
                intval($request->get('counter')),
                $request->get('options'),
                $request->get('option')
            );
        }
    }

    /**
     * @param $responseData
     * @param Request $request
     */
    private function _actonRemoveItem(&$responseData, $request)
    {
        $this->getCartService()->removeDishByIds(
            $request->get('dish_id'),
            $request->get('cart_id'),
            $request->get('place')
        );
    }

    /**
     * @param Request $request
     */
    private function _actionSetDelivery($request)
    {
        $this->container->get('session')->set(
            'cart_delivery_'.$request->get('place'), $request->get('take_away', false)
        );
    }

    /**
     * @param $dishId
     * @param $dishSize
     * @param int $dishQuantity
     * @param int[] $options
     */
    public function addDishToCartAction($dishId, $dishSize, $dishQuantity=0, $options=array())
    {
        $this->getCartService()->addDishByIds($dishId, $dishSize, $dishQuantity, $options);
    }

    public function removeOpionAction($dishId, $optionId)
    {
        $this->getCartService()->removeOptionById($dishId, $optionId);
    }

    public function indexAction($placeId, $takeAway = null, Request $request)
    {
        $orderService = $this->get('food.order');
        $placeService = $this->get('food.places');
        $miscUtils = $this->get('food.app.utils.misc');
        $googleGisService = $this->container->get('food.googlegis');

        $country = $this->container->getParameter('country');

        /**
         * @var UserManager $fosUserManager
         */
        $fosUserManager = $this->get('fos_user.user_manager');

        $orderHash = $request->get('hash');
        $order = null;

        if (!empty($orderHash)) {
            $order = $orderService->getOrderByHash($orderHash);
            $place = $order->getPlace();
            $takeAway = ($order->getDeliveryType() == 'pickup');
        } else {
            $place = $placeService->getPlace($placeId);
        }

        // Form submitted
        $formHasErrors = false;
        $formErrors = array();
        $dataToLoad = array();

        // TODO refactor this nonsense... if is if is if is bullshit...
        // Validate only if post happened
        if ($request->getMethod() == 'POST') {
            $this->get('food.order')->validateDaGiantForm(
                $place,
                $request,
                $formHasErrors,
                $formErrors,
                ($takeAway ? true : false),
                ($takeAway ? $request->get('place_point'): null)
            );
        }

        // Empty dish protection
        if (empty($order)) {
            $dishes = $this->getCartService()->getCartDishes($place);
        } else {
            $dishes = $order->getDetails();
        }
        if (count($dishes) < 1) {
            $formErrors[] = 'order.form.errors.emptycart';
            $formHasErrors = true;
        }

        if ($formHasErrors) {
            $dataToLoad = $request->request->all();
        }

        // PreLoad UserAddress Begin
        $address = null;
        $session = $request->getSession();
        $locationData = $session->get('locationData');
        $current_user = $this->container->get('security.context')->getToken()->getUser();

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

        if ($request->getMethod() == 'POST' && !$formHasErrors) {
            // Jei vede kupona - uzsikraunam
            $couponCode = $request->get('coupon_code');
            if (!empty($couponCode)) {
                $coupon = $orderService->getCouponByCode($couponCode);
            } else {
                $coupon = null;
            }

            // Jeigu atsiima pats - dedam gamybos taska, kuri jis pats pasirinko, o ne mes Pauliaus magic find funkcijoje
            if ($takeAway) {
                $placePointId = $request->get('place_point');
                $placePoint = $placeService->getPlacePointData($placePointId);
            } else {
                $placePoint = null;
            }

            if (empty($order)) {
                $userEmail = $request->get('customer-email');
                $userPhone = $request->get('customer-phone');
                $userFirstName = $request->get('customer-firstname');
                $userLastName = $request->get('customer-lastname', null);
                if (!empty($userPhone)) {
                    $formatedPhone = $miscUtils->formatPhone($userPhone, $country);

                    if (!empty($formatedPhone)) {
                        $userPhone = $formatedPhone;
                    }
                }
                $userData = array(
                    'email' => $userEmail,
                    'phone' => $userPhone,
                    'firstname' => $userFirstName,
                    'lastname' => $userLastName,
                );

                $user = $fosUserManager->findUserByEmail($userEmail);

                // If bussines user - load it from here please
                try {
                    $tmpUser = $this->container->get('security.context')->getToken()->getUser();

                    if ($tmpUser instanceof User && $tmpUser->getId() && $tmpUser->getIsBussinesClient()) {
                        $user = $tmpUser;
                    }
                } catch (\Exception $e) {
                    // do nothing for now. Todo logging
                }

                if (empty($user) || !$user->getId()) {
                    /**
                     * @var User $user
                     */
                    $user = $fosUserManager->createUser();
                    $user->setUsername($userEmail);
                    $user->setEmail($userEmail);
                    $user->setFullyRegistered(false);
                    $user->setFirstname($userFirstName);
                    $user->setLastname($userLastName);
                    if (!empty($userPhone)) {
                        $user->setPhone($userPhone);
                    }

                    // TODO gal cia normaliai generuosim desra-sasyskos-random krap ir siusim useriui emailu ir dar iloginsim
                    $user->setPlainPassword('new-user');
                    $user->addRole('ROLE_USER');
                    $user->setEnabled(true);

                    $fosUserManager->updateUser($user);
                }

                $selfDelivery = ($request->get('delivery-type') == "pickup" ? true : false);

                $orderService->createOrderFromCart($placeId, $request->getLocale(), $user, $placePoint, $selfDelivery, $coupon, $userData);
                $orderService->logOrder(null, 'create', 'Order created from cart', $orderService->getOrder());
            } else {
                $orderService->setOrder($order);
                if ($takeAway) {
                    $orderService->getOrder()->setPlacePoint($placePoint);
                    $orderService->getOrder()->setPlacePointCity($placePoint->getCity());
                    $orderService->getOrder()->setPlacePointAddress($placePoint->getAddress());
                }
                $orderService->logOrder(null, 'retry', 'Canceled order billing retry by user', $orderService->getOrder());

                $user = $order->getUser();
                $userPhone = $user->getPhone();
            }

            if ($userPhone != $user->getPhone() && !$user->getIsBussinesClient()) {
                $formatedPhone = $miscUtils->formatPhone($userPhone, $country);

                if (!empty($formatedPhone)) {
                    $user->setPhone($formatedPhone);
                } else {
                    $user->setPhone($userPhone);
                }
                $fosUserManager->updateUser($user);
            }

            $paymentMethod = $request->get('payment-type');
            $deliveryType = $request->get('delivery-type');
            $customerComment = $request->get('customer-comment');
            $orderService->setPaymentMethod($paymentMethod);
            $orderService->setDeliveryType($deliveryType);
            $orderService->setLocale($request->getLocale());
            if (!empty($customerComment)) {
                $orderService->getOrder()->setComment($customerComment);
            }
            $orderService->setPaymentStatus($orderService::$paymentStatusWait);

            // I R big bussines
            if ($request->get('company') == 'on') {
                $orderService->getOrder()
                    ->setCompany(true)
                    ->setCompanyName($request->get('company_name'))
                    ->setCompanyCode($request->get('company_code'))
                    ->setVatCode($request->get('vat_code'))
                    ->setCompanyAddress($request->get('company_address'));
            }

            if ($user->getIsBussinesClient()) {
                $orderService->getOrder()
                    ->setIsCorporateClient(true)
                    ->setDivisionCode($request->get('company_division_code'))
                    ->setCompany(true)
                    ->setCompanyName($user->getCompanyName())
                    ->setCompanyCode($user->getCompanyCode())
                    ->setVatCode($user->getVatCode())
                    ->setCompanyAddress($user->getCompanyAddress());
            }

            // Update order with recent address information. but only if we need to deliver
            if ($deliveryType == $orderService::$deliveryDeliver) {
                $locationData = $googleGisService->getLocationFromSession();
                $em = $this->getDoctrine()->getManager();
                $address = $orderService->createAddressMagic(
                    $user,
                    $locationData['city'],
                    $locationData['address_orig'],
                    (string)$locationData['lat'],
                    (string)$locationData['lng'],
                    $customerComment
                );
                $orderService->getOrder()->setAddressId($address);
                // Set user default address
                if (!$user->getDefaultAddress()) {
                    $em->persist($address);
                    $user->addAddress($address);
                }
            }
            $orderService->saveOrder();

            $billingUrl = $orderService->billOrder();
            if (!empty($billingUrl)) {
                return new RedirectResponse($billingUrl);
            }
            // TODO Crap happened?
        }

        return $this->render(
            'FoodCartBundle:Default:index.html.twig',
            array(
                'order' => $order,
                'formHasErrors' => $formHasErrors,
                'formErrors' => $formErrors,
                'place' => $place,
                'takeAway' => ($takeAway ? true : false),
                'location' => $this->get('food.googlegis')->getLocationFromSession(),
                'dataToLoad' => $dataToLoad,
                'userAddress' => $address,
                'userAllAddress' => $placeService->getCurrentUserAddresses(),
                'submitted' => $request->isMethod('POST'),
                'testNordea' => $request->query->get('test_nordea')
            )
        );
    }

    /**
     * Side cart block
     *
     * @param Place $place
     * @param bool $renderView
     * @param bool $inCart
     * @param null|Order $order
     * @param null|bool $takeAway
     * @param null|string $couponCode
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sideBlockAction(Place $place, $renderView = false, $inCart = false, $order = null, $takeAway = null, $couponCode = null)
    {
        $list = $this->getCartService()->getCartDishes($place);
        $total_cart = $this->getCartService()->getCartTotal($list/*, $place*/);
        $cartMinimum = $place->getCartMinimum();
        $cartFromMin = $this->get('food.places')->getMinCartPrice($place->getId());
        $cartFromMax = $this->get('food.places')->getMaxCartPrice($place->getId());
        $isTodayNoOneWantsToWork = $this->get('food.order')->isTodayNoOneWantsToWork($place);
        $displayCartInterval = true;
        $deliveryTotal = 0;

        $sessionTakeAway = $this->container->get('session')->get('cart_delivery_'.$place->getId(), null);
        if ($sessionTakeAway !== null) {
            $takeAway = $sessionTakeAway;
            // TODO think about it
        }

        if (!$takeAway) {
            $placePointMap = $this->container->get('session')->get('point_data');

            if (empty($placePointMap) || !isset($placePointMap[$place->getId()])) {
                $deliveryTotal = $place->getDeliveryPrice();
            } else {
                // TODO Trying to catch fatal when searching for PlacePoint
                if (!isset($placePointMap[$place->getId()]) || empty($placePointMap[$place->getId()])) {
                    $this->container->get('logger')->error('Trying to find PlacePoint without ID in CartBundle Default controller - sideBlockAction');
                }
                $pointRecord = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointMap[$place->getId()]);
                $deliveryTotal = $this->getCartService()->getDeliveryPrice(
                    $place,
                    $this->get('food.googlegis')->getLocationFromSession(),
                    $pointRecord
                );
                $displayCartInterval = false;
                $cartMinimum = $this->getCartService()->getMinimumCart(
                    $place,
                    $this->get('food.googlegis')->getLocationFromSession(),
                    $pointRecord
                );
            }
        }

        // If coupon in use
        $applyDiscount = $freeDelivery = $discountInSum = false;
        $discountSize = null;
        $discountSum = null;
        if (!empty($couponCode)) {
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


        // Jei restorane galima tik atsiimti arba, jei zmogus rinkosi, kad jis atsiimas, arba jei yra uzsakymas ir fiksuotas atsiemimas vietoje - neskaiciuojam pristatymo
        if ($place->getDeliveryOptions() == Place::OPT_ONLY_PICKUP ||
            ($order!=null && $order->getDeliveryType() == 'pickup')
            || $takeAway == true) {
            $hideDelivery = true;
        } else {
            $hideDelivery = false;
        }

        $params = array(
            'list'  => $list,
            'place' => $place,
            'total_cart' => sprintf('%.2f',$total_cart),
            'total_with_delivery' => ($freeDelivery ? $total_cart : ($total_cart + $deliveryTotal)),
            'total_delivery' => $deliveryTotal,
            'inCart' => (int)$inCart,
            'hide_delivery' => $hideDelivery,
            'applyDiscount' => $applyDiscount,
            'freeDelivery' => $freeDelivery,
            'discountSize' => $discountSize,
            'discountInSum' => $discountInSum,
            'discountSum' => $discountSum,
            'cart_minimum' => sprintf('%.2f',$cartMinimum),
            'cart_from_min' => $cartFromMin,
            'cart_from_max' => $cartFromMax,
            'display_cart_interval' => $displayCartInterval,
            'takeAway' => $takeAway,
            'isTodayNoOneWantsToWork' => $isTodayNoOneWantsToWork,
        );
        if ($renderView) {
            return $this->renderView('FoodCartBundle:Default:side_block.html.twig', $params);
        }
        return $this->render('FoodCartBundle:Default:side_block.html.twig', $params);
    }

    /**
     * TODO dabar routas cart/success, bet renaminant kart i kasikelis, reiks ir sita parenamint i kasikelis/apmoketas
     */
    public function successAction($orderHash)
    {
        $order = $this->get('food.order')->getOrderByHash($orderHash);

        return $this->render(
            'FoodCartBundle:Default:payment_success.html.twig',
            array('order' => $order)
        );
    }

    /**
     * TODO dabar routas cart/wait, bet renaminant kart i kasikelis, reiks ir sita parenamint i kasikelis/laukiama
     */
    public function waitAction($orderHash)
    {
        $order = $this->get('food.order')->getOrderByHash($orderHash);

        return $this->render(
            'FoodCartBundle:Default:payment_wait.html.twig',
            array('order' => $order)
        );
    }
}
