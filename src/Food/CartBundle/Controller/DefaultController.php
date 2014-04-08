<?php

namespace Food\CartBundle\Controller;

use Food\CartBundle\Service\CartService;
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
        $this->getCartService()->addDishBySizeId(
            $request->get('dish-size'),
            intval($request->get('counter')),
            $request->get('options'),
            $request->get('option')
        );
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

    public function indexAction($placeId)
    {
        $request = $this->getRequest();

        $orderService = $this->container->get('food.order');
        $orderHash = $request->get('hash');
        $order = null;

        if (!empty($orderHash)) {
            $order = $orderService->getOrderByHash($orderHash);
            $place = $order->getPlace();
        } else {
            $place = $this->get('food.places')->getPlace($placeId);
        }

        // Form submitted
        if ($request->getMethod() == 'POST') {
            if (empty($order)) {
                $orderService->createOrderFromCart($placeId, $request->getLocale());
                $orderService->logOrder(null, 'create', 'Order created from cart', $orderService->getOrder());
            } else {
                $orderService->setOrder($order);
                $orderService->logOrder(null, 'retry', 'Canceled order billing retry by user', $orderService->getOrder());
            }

            $paymentMethod = $request->get('payment-type');
            $deliveryType = $request->get('delivery-type');
            $orderService->setPaymentMethod($paymentMethod);
            $orderService->setDeliveryType($deliveryType);
            $orderService->setLocale($request->getLocale());
            $orderService->setPaymentStatus($orderService::$paymentStatusWait);

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
                'place' => $place,
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
    public function sideBlockAction($place, $renderView = false, $inCart = false)
    {
        $list = $this->getCartService()->getCartDishes($place);
        $total = $this->getCartService()->getCartTotal($list, $place);
        $params = array(
            'list'  => $list,
            'place' => $place,
            'total' => $total,
            'inCart' => $inCart,
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
}
