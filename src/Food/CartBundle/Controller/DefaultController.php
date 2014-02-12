<?php

namespace Food\CartBundle\Controller;

use Food\CartBundle\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
                break;
            case 'remove-option':
                break;
        }
        $jsonResponseData['items'] = $this->getCartService()->getCartDishesForJson();
        $response->setContent(json_encode($jsonResponseData));
        return $response;
    }

    /**
     * @param $responseData
     * @param array $params
     */
    private function _actonAddItem(&$responseData, $params)
    {
        $responseData = array("kebas"=>"grabas");
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

    public function removeDishAction($dishId)
    {
        $this->getCartService()->removeDish($dishId);
    }

    public function removeOpionAction($dishId, $optionId)
    {
        $this->getCartService()->removeOptionById($dishId, $optionId);
    }

    public function indexAction()
    {
        $request = $this->getRequest();

        $orderService = $this->container->get('food.order');
        $orderHash = $request->get('hash');
        $order = null;

        if (!empty($orderHash)) {
            $order = $orderService->getOrderByHash($orderHash);
        }

        // Form submitted
        if ($request->getMethod() == 'POST') {
            if (empty($order)) {
                $orderService->createOrderFromCart();
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

        return $this->render('FoodCartBundle:Default:index.html.twig', array('order' => $order));
    }

    /**
     * Side cart block
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sideBlockAction()
    {
        $list = $this->getCartService()->getCartDishes();
        return $this->render('FoodCartBundle:Default:side_block.html.twig', array('list' => $list));
    }
}
