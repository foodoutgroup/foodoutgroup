<?php

namespace Food\CartBundle\Controller;

use Food\CartBundle\Service\CartService;
use Food\DishesBundle\Entity\Place;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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
     * @return Response
     */
    public function actionAction($action)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $jsonResponseData = array();

        switch($action) {
            case 'add':
                $this->_actonAddItem($jsonResponseData, $this->getRequest());
                break;
            case 'add-option':
                break;
            case 'remove':
                $this->_actonRemoveItem($jsonResponseData, $this->getRequest());
                break;
            case 'remove-option':
                break;
        }
        /*
        $jsonResponseData['items'] = $this->getCartService()->getCartDishesForJson(
            $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find(
                $this->getRequest()->get('place')
            )
        );
        */
        $jsonResponseData['block'] = $this->sideBlockAction(
            $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find(
                $this->getRequest()->get('place')
            ),
            true
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
     * @param $dishId
     * @param $dishSize
     * @param int $dishQuantity
     * @param int[] $options
     */
    public function addDishToCartAction($dishId, $dishSize, $dishQuantity=0, $options=array())
    {
        $this->getCartService()->addDishByIds($dishId, $dishSize, $dishQuantity, $options);
    }

    public function removeDishAction($dishId,$cartId, $placeId)
    {
        $this->getCartService()->removeDish($dishId);
    }

    public function removeOpionAction($dishId, $optionId)
    {
        $this->getCartService()->removeOptionById($dishId, $optionId);
    }

    public function indexAction($placeId, $takeAway = null)
    {
        $request = $this->getRequest();

        $orderService = $this->get('food.order');
        $placeService = $this->get('food.places');
        $googleGisService = $this->container->get('food.googlegis');
        /**
         * @var UserManager $fosUserManager
         */
        $fosUserManager = $this->get('fos_user.user_manager');

        $orderHash = $request->get('hash');
        $order = null;

        if (!empty($orderHash)) {
            $order = $orderService->getOrderByHash($orderHash);
            $place = $order->getPlace();
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
            $this->get('food.order')->validateDaGiantForm($place, $request, $formHasErrors, $formErrors, ($takeAway ? true : false), $request->get('place_point'));
        }

        if ($formHasErrors) {
            $dataToLoad = $this->getRequest()->request->all();
        }

        if ($request->getMethod() == 'POST' && !$formHasErrors) {
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

                $user = $fosUserManager->findUserByEmail($userEmail);

                if (empty($user) || !$user->getId()) {
                    /**
                     * @var User $user
                     */
                    $user = $fosUserManager->createUser();
                    $user->setUsername($userEmail);
                    $user->setEmail($userEmail);
                    $user->setFullyRegistered(false);
                    $user->setFirstname($request->get('customer-firstname'));
                    $user->setLastname($request->get('customer-lastname', null));

                    if (!empty($userPhone)) {
                        $user->setPhone($userPhone);
                    }

                    // TODO gal cia normaliai generuosim desra-sasyskos-random krap ir siusim useriui emailu ir dar iloginsim
                    $user->setPlainPassword('new-user');
                    $user->addRole('ROLE_USER');

                    $fosUserManager->updateUser($user);
                }

                $selfDelivery = ($this->getRequest()->get('delivery-type') == "pickup" ? true : false);

                $orderService->createOrderFromCart($placeId, $request->getLocale(), $user, $placePoint, $selfDelivery);
                $orderService->logOrder(null, 'create', 'Order created from cart', $orderService->getOrder());
            } else {
                $orderService->setOrder($order);
                if ($takeAway) {
                    $orderService->getOrder()->setPlacePoint($placePoint);
                    $orderService->getOrder()->setPlacePointCity($placePoint->getCity());
                    $orderService->getOrder()->setPlacePointAddress($placePoint->getAddress());
                }
                $orderService->logOrder(null, 'retry', 'Canceled order billing retry by user', $orderService->getOrder());
            }

            if ($userPhone != $user->getPhone()) {
                $user->setPhone($userPhone);
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

            // Update order with recent address information. but only if we need to deliver
            if ($deliveryType == $orderService::$deliveryDeliver) {
                $locationData = $googleGisService->getLocationFromSession();
                $address = $orderService->createAddressMagic(
                    $user,
                    $locationData['city'],
                    $locationData['address_orig'],
                    (string)$locationData['lat'],
                    (string)$locationData['lng']
                );
                $orderService->getOrder()->setAddressId($address);
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
                'dataToLoad' => $dataToLoad
            )
        );
    }

    /**
     * Side cart block
     *
     * @param \Place $place
     * @param bool $renderView
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sideBlockAction(Place $place, $renderView = false, $inCart = false, $order = null, $takeAway = null)
    {
        $list = $this->getCartService()->getCartDishes($place);
        $total_cart = $this->getCartService()->getCartTotal($list, $place);
        $params = array(
            'list'  => $list,
            'place' => $place,
            'total_cart' => $total_cart,
            'total_with_delivery' => $total_cart + $place->getDeliveryPrice(),
            'inCart' => $inCart,
            'hide_delivery' => (($order!=null AND $order->getDeliveryType() == 'pickup') || $takeAway == true ? 1: 0)
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
     * TODO dabar routas cart/success, bet renaminant kart i kasikelis, reiks ir sita parenamint i kasikelis/apmoketas
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
