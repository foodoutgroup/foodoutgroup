<?php

namespace Food\CartBundle\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Food\AppBundle\Entity\Slug;
use Food\CartBundle\Service\CartService;
use Food\DishesBundle\Entity\Place;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\OrderService;
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
     * @return CartService
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
     *
     * @return Response
     */
    public function actionAction($action, Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $jsonResponseData = [];

        switch ($action) {
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
            case 'empty':
                $this->_actionSetDelivery($request);
                break;
            case 'refresh':
                break;
        }

        $place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find($request->get('place', 0));

        // Somehow we've lost the place.. dont crash.. better show nothing
        if (!$place) {
            return new Response('');
        }

        $jsonResponseData['block'] = $this->sideBlockAction($place, true, $request->get('in_cart', false), null, $request->get('coupon_code', null));

        $response->setContent(json_encode($jsonResponseData));

        return $response;
    }

    /**
     * @param         $responseData
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
        $this->_recountBundles($request);
    }

    /**
     * @param Request $request
     */
    private function _recountBundles($request)
    {
        // $request->get('dish-size'), - adding
        // $request->get('place') removing
        $place = $request->get('place', null);
        if (empty($place)) {
            $dishSize = $this->container->get('doctrine')
                ->getRepository('FoodDishesBundle:DishSize')
                ->findBy((int)$request->get('dish-size'));
            $place = $dishSize->getDish()->getPlace()->getId();
        }
        if (empty($place)) {
            return;
        }
        $this->getCartService()->recalculateBundles($place);

        return;
    }

    /**
     * @param         $responseData
     * @param Request $request
     */
    private function _actonRemoveItem(&$responseData, $request)
    {
        $this->getCartService()->removeDishByIds(
            $request->get('dish_id'),
            $request->get('cart_id'),
            $request->get('place')
        );
        $this->_recountBundles($request);
    }

    /**
     * @param Request $request
     */
    private function _actionSetDelivery(Request $request)
    {
        $this->container
            ->get('session')
            ->set('delivery_type', $request->get('take_away', false) ?
                OrderService::$deliveryPickup :
                OrderService::$deliveryDeliver);
    }

    /**
     * @param       $dishId
     * @param       $dishSize
     * @param int $dishQuantity
     * @param int[] $options
     */
    public function addDishToCartAction($dishId, $dishSize, $dishQuantity = 0, $options = [])
    {
        $this->getCartService()->addDishByIds($dishId, $dishSize, $dishQuantity, $options);
    }

    public function removeOpionAction($dishId, $optionId)
    {
        $this->getCartService()->removeOptionById($dishId, $optionId);
    }

    public function indexAction($placeId, $takeAway = null, Request $request)
    {
        // for now this is relevant for callcenter functionality
        $pService = $this->container->get('food.phones_code_service');
        $orderService = $this->get('food.order');
        $placeService = $this->get('food.places');
        $miscUtils = $this->get('food.app.utils.misc');
        $lService = $this->container->get('food.location');
        $session = $request->getSession();
        $locationData = $session->get('locationData');

        /**
         * @var UserManager $fosUserManager
         */
        $fosUserManager = $this->get('fos_user.user_manager');

        $orderHash = $request->get('hash');
        $order = null;
        $require_lastname = false;

        if (!empty($orderHash)) {
            $order = $orderService->getOrderByHash($orderHash);
            $place = $order->getPlace();
            $takeAway = ($order->getDeliveryType() == 'pickup');
        } else {
            $place = $placeService->getPlace($placeId);
        }

        // Form submitted
        $formHasErrors = false;
        $formErrors = [];
        $dataToLoad = [];
        $placePointMap = [];

        /**
         * @var $doctrine Registry
         */
        $doctrine = $this->container->get('doctrine');
        $placePointId = $doctrine->getRepository('FoodDishesBundle:Place')->getPlacePointNear($placeId, $lService->get());
        $placePointMap[$placeId] = $placePointId;
        $session->set('point_data', $placePointMap);

        if (!empty($placePointMap[$placeId])) {
            $pointRecord = $this->container->get('doctrine')
                ->getRepository('FoodDishesBundle:PlacePoint')
                ->find($placePointMap[$placeId]);
        } else {
            $pointRecord = null;
        }

        $workingHoursForInterval = [];
        $workingDaysCount = 4;
        for ($i = 0; $i <= $workingDaysCount; $i++) {
            $workingHoursForInterval[date("Y-m-d", strtotime("+" . $i . " day"))] = $placeService->getFullRangeWorkTimes($place, $pointRecord, "+" . $i . " day");
        }

        // Dirba / Nedirba ?
        $pointWorkingErrors = [];
        $pointIsWorking = true;
        if ($pointRecord) {
            $orderService->workTimeErrors($pointRecord, $pointWorkingErrors);
        }
        if (!$pointRecord && !$takeAway || !empty($pointWorkingErrors)) {
            $pointIsWorking = false;
        }

        /**
         * $workingHoursToday = $placeService->getFullRangeWorkTimes($place, $pointRecord);
         * $workingHoursTommorow = $placeService->getFullRangeWorkTimes($place, $pointRecord, '+1 day');
         **/

        // TODO refactor this nonsense... if is if is if is bullshit...
        // Validate only if post happened
        if ($request->getMethod() == 'POST') {

            $couponEnt = null;
            if ($request->get('coupon_code', false)) {
                $couponEnt = $this->get('doctrine')->getRepository('FoodOrderBundle:Coupon')->findOneBy(['code' => $request->get('coupon_code', '')]);
            }
            $this->get('food.order')->validateDaGiantForm($place, $request, $formHasErrors, $formErrors, $takeAway, ($takeAway ? $request->get('place_point') : null), $couponEnt, false);
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
        } else {
            // search for alco inside the basket
            $require_lastname = $this->getCartService()->isAlcoholInCart($dishes);
        }

        // PreLoad UserAddress Begin
        $address = null;

        $current_user = $this->container->get('security.context')->getToken()->getUser();

        if (!empty($locationData) && !empty($current_user) && is_object($current_user)) {
            $address = $placeService->getCurrentUserAddress($locationData['city_id'], $locationData['address']);
        }

        if (empty($address) && !empty($current_user) && is_object($current_user)) {
            $defaultUserAddress = $current_user->getCurrentDefaultAddress();

            if (!empty($defaultUserAddress)) {
                $loc_city = $defaultUserAddress->getCityId();
                $loc_address = $defaultUserAddress->getAddress();
                $address = $placeService->getCurrentUserAddress($loc_city, $loc_address);
            }
        }


        // PreLoad UserAddress End

        if ($request->getMethod() == 'POST' && !$formHasErrors) {
            try {
                $countryCode = $request->get('country');

                // Jei vede kupona - uzsikraunam
                $deliveryType = $request->get('delivery-type');

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
                        $formatedPhone = $miscUtils->formatPhone($userPhone, $request->get('country'));
                        if (!empty($formatedPhone)) {
                            $userPhone = $formatedPhone;
                        }
                    }

                    $userData = [
                        'email' => $userEmail,
                        'phone' => $userPhone,
                        'firstname' => $userFirstName,
                        'lastname' => $userLastName,
                    ];

                    $user = $fosUserManager->findUserByEmail($userEmail);

                    // If bussines user - load it from here please
                    try {
                        $tmpUser = $this->container->get('security.context')->getToken()->getUser();

                        if ($tmpUser instanceof User && $tmpUser->getId() && $tmpUser->getIsBussinesClient()) {
                            $user = $tmpUser;
                        }
                    } catch (\Exception $e) {
                        $this->get('logger')->error($e->getTraceAsString());
                        $this->get('logger')->error($e->getMessage());
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

                    $blockedEmails = $this->getDoctrine()
                        ->getRepository('FoodAppBundle:BannedEmail')->findByEmail($userEmail);
                    if (!empty($blockedEmails) || !$user->isAccountNonLocked() || !$user->isEnabled()) {
                        return $this->redirect($this->get('slug')->urlFromParam('page_email_banned', Slug::TYPE_PAGE));
                    }

                    $selfDelivery = ($request->get('delivery-type') == "pickup" ? true : false);

                // Preorder date formation
                $orderDate = null;
                $preOrder = $request->get('pre-order');
                if ($preOrder == 'it-is') {
                    $orderDate = $request->get('pre_order_date') . ' ' . $request->get('pre_order_time');
                }

                $orderService->createOrderFromCart($placeId, $request->getLocale(), $user, $placePoint, $selfDelivery, $coupon, $userData, $orderDate);
                $orderService->logOrder(null, 'create', 'Order created from cart', $orderService->getOrder());
                if ($preOrder == 'it-is') {
                    $orderService->logOrder(null, 'pre-order', 'Order marked as pre-order', $orderService->getOrder());
                }
            } else {
                $orderService->setOrder($order);
                if ($takeAway) {
                    $orderService->getOrder()->setPlacePoint($placePoint);
                    $orderService->getOrder()->setCityId($placePoint->getCityId());
                    $orderService->getOrder()->setPlacePointAddress($placePoint->getAddress());
                }
                $orderService->logOrder(null, 'retry', 'Canceled order billing retry by user', $orderService->getOrder());

                    $user = $order->getUser();
                    $userPhone = $user->getPhone();
                }



            if ($countryCode != $user->getCountryCode() && !$user->getIsBussinesClient()) {
                $user->setCountryCode($countryCode);
            }

                if ($userPhone != $user->getPhone() && !$user->getIsBussinesClient()) {
                    $formatedPhone = $miscUtils->formatPhone($request->get('customer-phone'), $request->get('country'));

                    if (!empty($formatedPhone)) {
                        $user->setPhone($formatedPhone);
                    } else {
                        $user->setPhone($userPhone);
                    }
                    $fosUserManager->updateUser($user);
                }

                $paymentMethod = $request->get('payment-type');

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
                if ($deliveryType == $orderService::$deliveryDeliver || $deliveryType == $orderService::$deliveryPedestrian) {

                    $em = $this->getDoctrine()->getManager();
                    $address = $lService->saveAddressFromArrayToUser($lService->get(), $user);
                    $orderService->getOrder()->setAddressId($address);
                    // Set user default address

                    if (!$user->getDefaultAddress()) {
                        $em->persist($address);
                        $user->addAddress($address);
                    }
                }

                $orderService->getOrder()->setNewsletterSubscribe((boolean)$request->get('newsletter_subscribe'));

                $orderService->saveOrder();

                $billingUrl = $orderService->billOrder();
                if (!empty($billingUrl)) {
                    return new RedirectResponse($billingUrl);
                }
                // TODO Crap happened?
            } catch (\Exception $e) {
                $this->container->get('logger')->critical($e->getMessage());
                $translator = $this->container->get('translator');
                $formErrors = [$translator->trans('system.error_on_order')];
                $formHasErrors = true;
            }
        }

        $disabledPreorderDaysParam = $this->get('food.app.utils.misc')->getParam('disabled_preorder_days');
        if (!empty($disabledPreorderDaysParam)) {
            $disabledPreorderDays = array_map('trim', explode(",", $disabledPreorderDaysParam));
        } else {
            $disabledPreorderDays = array();
        }

        $currentCountry = $this->container->getParameter('country');

        $countryCode = $pService->getCountryCode($this->getUser(), $currentCountry);

        if ($request->getMethod() == 'POST' && !empty($_POST['country'])) {
            $countryCode = $_POST['country'];
        }

        if ($formHasErrors) {
            $dataToLoad = $request->request->all();
        }

        $data = [
            'order' => $order,
            'formHasErrors' => $formHasErrors,
            'formErrors' => $formErrors,
            'place' => $place,
            'takeAway' => ($takeAway ? true : false),
            'location' => $this->get('food.location')->get(),
            'dataToLoad' => $dataToLoad,
            'userAddress' => $address,
            'userAllAddress' => $placeService->getCurrentUserAddresses(),
            'submitted' => $request->isMethod('POST'),
            'testNordea' => $request->query->get('test_nordea'),
            'workingHoursForInterval' => $workingHoursForInterval,
            'workingDaysCount' => $workingDaysCount,
            'require_lastname' => $require_lastname,
            'pointIsWorking' => $pointIsWorking,
            'disabledPreorderDays' => $disabledPreorderDays,
            'countryCode' => $countryCode
        ];

        return $this->render('FoodCartBundle:Default:index.html.twig', $data);
    }

    /**
     * Side cart block
     *
     * @param Place $place
     * @param bool $renderView
     * @param bool $inCart
     * @param null|Order $order
     * @param null|string $couponCode
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sideBlockAction(Place $place, $renderView = false, $inCart = false, $order = null, $couponCode = null)
    {
        // TODO fix prices calculation
        $orderPriceService = $this->get('food.order_price_service');
        $miscService = $this->get('food.app.utils.misc');
        $session = $this->get('session');


        $enableDiscount = !$place->getOnlyAlcohol();
        $placeServ = $this->get('food.places');

        $cartFromMin = $placeServ->getMinCartPrice($place->getId());
        $cartFromMax = $placeServ->getMaxCartPrice($place->getId());
        $useAdminFee = $placeServ->useAdminFee($place);
        $adminFee = $placeServ->getAdminFee($place);

        $em = $this->getDoctrine()->getManager();

        $noneWorking = false;

        if ($useAdminFee && !$adminFee) {
            $adminFee = 0;
        }



        $isTodayNoOneWantsToWork = $this->get('food.order')->isTodayNoOneWantsToWork($place);

        $enable_free_delivery_for_big_basket = $miscService->getParam('enable_free_delivery_for_big_basket');
        $free_delivery_price = $miscService->getParam('free_delivery_price');

        $deliveryTotal = $this->get('food.places')->getMinDeliveryPrice($place->getId());

        if ($enable_free_delivery_for_big_basket) {
            $enable_free_delivery_for_big_basket = $place->isAllowFreeDelivery();
        }

        if ($enable_free_delivery_for_big_basket) {
            $placeMinimumFreeDeliveryPrice = $place->getMinimumFreeDeliveryPrice();
            if ($placeMinimumFreeDeliveryPrice) {
                $free_delivery_price = $placeMinimumFreeDeliveryPrice;
            }
        }
        $displayCartInterval = true;

        $basketErrors = [
            'foodQuantityError' => false,
            'drinkQuantityError' => false,
        ];

        $list = $this->getCartService()->getCartDishes($place);

        $takeAway = ($this->container->get('session')->get('delivery_type', false) == OrderService::$deliveryPickup);

        if ($takeAway) {
            $displayCartInterval = false;
            $deliveryTotal = 0;
        } else {
            $placePointMap = $this->container->get('session')->get('point_data');


            $locationData = $this->get('food.location')->get();

            if (empty($placePointMap) || !isset($placePointMap[$place->getId()])) {
                $deliveryTotal = $place->getDeliveryPrice();

            } elseif ($locationData['precision'] >= 0) {
                // TODO Trying to catch fatal when searching for PlacePoint
                if (!isset($placePointMap[$place->getId()]) || empty($placePointMap[$place->getId()])) {
                    $this->container->get('logger')->error('Trying to find PlacePoint without ID in CartBundle Default controller - sideBlockAction');
                }

                $checkNearest = $em
                    ->getRepository('FoodDishesBundle:Place')
                    ->getPlacePointNear($place->getId(), $locationData);

                if (empty($checkNearest)) {
                    $noneWorking = true;
                }

                $pointRecord = $em->getRepository('FoodDishesBundle:PlacePoint')->find($placePointMap[$place->getId()]);
                $deliveryTotal = $this->getCartService()->getDeliveryPrice($place, $locationData, $pointRecord, $noneWorking);
                $displayCartInterval = false;
                $cartFromMin = $this->getCartService()->getMinimumCart($place, $locationData, $pointRecord);
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

        $total_cart = $this->getCartService()->getCartTotal($list);
        $priceBeforeDiscount = $total_cart;

        if(($place->getCartMinimum() < $total_cart) && $useAdminFee){
            $useAdminFee = false;
        }


        $noMinimumCart = false;
        $current_user = $this->container->get('security.context')->getToken()->getUser();
        if (!empty($current_user) && is_object($current_user)) {
            $noMinimumCart = $current_user->getNoMinimumCart();
            $this->get('food.user')->getDiscount($current_user);
        }

        $applyDiscount = $freeDelivery = $discountInSum = false;
        $discountSize = null;
        $discountSum = null;
        $checkAdmin = false;
        // If coupon in use

        if (!empty($couponCode)) {
            $coupon = $this->get('food.order')->getCouponByCode($couponCode);

            if ($coupon) {
                $applyDiscount = true;

                if ($coupon->getIgnoreCartPrice()) {
                    $noMinimumCart = true;
                }

                $freeDeliver = $coupon->getFreeDelivery();

                $discountSize = $coupon->getDiscount();

                if (!empty($discountSize)) {
                    $discountSum = $this->getCartService()->getTotalDiscount($list, $coupon->getDiscount());
                } else {
                    $discountSize = null;
                    $discountInSum = true;
                    $discountSum = $coupon->getDiscountSum();
                }

                $realDiscountSum = $discountSum;

                $otherPriceTotal = 0;
                foreach ($list as $dish) {
                    $sum = $dish->getDishSizeId()->getPrice() * $dish->getQuantity();
                    if (!$this->getCartService()->isAlcohol($dish->getDishId())) {
                        $otherPriceTotal += $sum;
                    }
                }

                // tikrina ar kitu produktu suma (ne alko) yra mazesne nei nuolaida jei taip tada pritaiko discount kaip ta suma;
//                $otherMinusDiscount = $otherPriceTotal - $discountSum;
//                if ($otherMinusDiscount < 0) {
//                    $discountSum = $otherPriceTotal;
//                }


                if ($enableDiscount) {
                    $total_cart -= $discountSum;
                } else {
                    $discountSum = 0;
                }

                if ($total_cart <= 0) {
                    if ($coupon->getFullOrderCovers() || $coupon->getIncludeDelivery()) {
                        if ($deliveryTotal < 0 || $total_cart < $realDiscountSum) {
                            $freeDelivery = true;
                            if ($total_cart < $place->getCartMinimum()) {
                                $useAdminFee = true;
                                $checkAdmin = true;

                                if(($total_cart - $adminFee) < $realDiscountSum){
                                    $total_cart += $adminFee;
                                    $freeDelivery = false;
                                }
                            } else {
                                $useAdminFee = false;
                            }
                        }
                    }
                }

                if ($freeDeliver) {
                    $discountSum += $deliveryTotal;
                    $freeDelivery = true;
                }
            }
        } // Business client discount
        elseif (!empty($current_user) && is_object($current_user) && $current_user->getIsBussinesClient()) {
            $businesCheck = $place->getNoBusinessDiscount();

            if (!$takeAway && !$place->getSelfDelivery() && !$businesCheck) {
                $applyDiscount = true;
                $discountSize = $this->get('food.user')->getDiscount($current_user);

                $discountSum = $this->getCartService()->getTotalDiscount($list, $discountSize);

                $otherPriceTotal = 0;
                foreach ($list as $dish) {
                    if (!$this->getCartService()->isAlcohol($dish->getDishId())) {
                        $sum = $dish->getDishSizeId()->getPrice() * $dish->getQuantity();
                        $otherPriceTotal += $sum;
                    }
                }

                // tikrina ar kitu produktu suma (ne alko) yra mazesne nei nuolaida jei taip tada pritaiko discount kaip ta suma;
                $otherMinusDiscount = $otherPriceTotal - $discountSum;
                if ($otherMinusDiscount < 0) {
                    $discountSum = $otherPriceTotal;
                }

                if ($enableDiscount) {
                    $total_cart -= $discountSum;
                } else {
                    $discountSum = 0;
                }
            }
        }


        if ($useAdminFee && (($cartFromMin - $total_cart) >= 0.00001) && !$checkAdmin ) {
            $total_cart += $adminFee;
        }

        $cartSumTotal = $total_cart;
        // Jei restorane galima tik atsiimti arba, jei zmogus rinkosi, kad jis atsiimas, arba jei yra uzsakymas ir fiksuotas atsiemimas vietoje - neskaiciuojam pristatymo
        if ($place->getDeliveryOptions() == Place::OPT_ONLY_PICKUP ||
            ($order != null && $order->getDeliveryType() == 'pickup')
            || $takeAway == true
        ) {
            $hideDelivery = true;
        } else {
            $hideDelivery = false;
        }

        if (!isset($coupon)) {
            $coupon = false;
        }

        if ($takeAway && !$place->getMinimalOnSelfDel()) {
            $useAdminFee = false;
        }


        // Nemokamas pristatymas dideliam krepseliui
        $self_delivery = $place->getSelfDelivery();
        $left_sum = 0;
        if ($enable_free_delivery_for_big_basket || ($coupon && $coupon->getIgnoreCartPrice())) {
            $minusDiscount = 0;
            if ($coupon && $coupon->getIgnoreCartPrice()) {
                $minusDiscount = $cartSumTotal;
            }

            // Kiek liko iki nemokamo pristatymo
            if ($free_delivery_price > $total_cart) {
                $left_sum = sprintf('%.2f', $free_delivery_price - $total_cart - $minusDiscount);
            }
            // Krepselio suma pasieke nemokamo pristatymo suma
            if ($left_sum == 0) {
                $deliveryTotal = 0;
            }
        }

        if($freeDelivery){
            $totalWIthDelivery = ($total_cart > 0) ? $total_cart : 0;
        }else{
            $totalWIthDelivery = ($total_cart + $deliveryTotal) > 0 ? $total_cart + $deliveryTotal : 0;
        }

        //$prices = $orderPriceService->getOrderPrices($place);
        // total_cart
        // delivery_price
        // total_delivery
        // discountSum
        // total_with_delivery

        // ??
        // left_sum
        // discountSum

        $params = [
            'list' => $list,
            'place' => $place,
            'total_cart' => sprintf('%.2f', $priceBeforeDiscount),
            'total_with_delivery' => $totalWIthDelivery,
            'total_delivery' => $deliveryTotal,
            'inCart' => (int)$inCart,
            'hide_delivery' => $hideDelivery,
            'applyDiscount' => $applyDiscount,
            'freeDelivery' => $freeDelivery,
            'discountSize' => $discountSize,
            'discountInSum' => $discountInSum,
            'discountSum' => $discountSum,
            'noMinimumCart' => $noMinimumCart,
            'cart_from_min' => sprintf('%.2f', $cartFromMin),
            'cart_from_max' => $cartFromMax,
            'display_cart_interval' => $displayCartInterval,
            'takeAway' => $takeAway,
            'isTodayNoOneWantsToWork' => $isTodayNoOneWantsToWork,
            'free_delivery_price' => $free_delivery_price,
            'left_sum' => $left_sum,
            'self_delivery' => $self_delivery,
            'enable_free_delivery_for_big_basket' => $enable_free_delivery_for_big_basket,
            'basket_errors' => $basketErrors,
            'isCallcenter' => false,
            'useAdminFee' => $useAdminFee,
            'adminFee' => $adminFee,
            'noneWorking' => $noneWorking
        ];

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
            ['order' => $order]
        );
    }

    /**
     * TODO dabar routas cart/success, bet renaminant kart i kasikelis, reiks ir sita parenamint i kasikelis/apmoketas
     */
    public function reverseAction($orderHash)
    {
        $order = $this->get('food.order')->getOrderByHash($orderHash);

        return $this->render(
            'FoodCartBundle:Default:reverse.html.twig',
            ['order' => $order]
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
            ['order' => $order]
        );
    }

    public function debugAction()
    {
        $pimPirim[1] = ["key" => 1, "data" => "2"];
        $pimPirim[2] = ["key" => 2, "data" => "3"];
        $pimPirim[3] = ["key" => 3, "data" => "1"];
        $pimPirim[4] = ["key" => 4, "data" => "6"];
        $pimPirim[5] = ["key" => 5, "data" => "4"];
        $keys = [];
        $values = [];
        foreach ($pimPirim as $key => $value) {
            $keys[] = $value['key'];
            $values[] = $value['data'];

        }
        echo "<pre>";
        var_dump($pimPirim);
        array_multisort($values, SORT_ASC, $pimPirim);
        var_dump($pimPirim);

        return new Response('Smooth end');
    }


}
