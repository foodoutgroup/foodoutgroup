<?php

namespace Food\OrderBundle\Controller;

use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/hello/{name}")
     * @Template()
     */
    public function indexAction($name)
    {
        return array('name' => $name);
    }

    /**
     * @param $hash
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mobileAction($hash, Request $request)
    {
        $orderService = $this->get('food.order');
        $order = $orderService->getOrderByHash($hash);
        $currentOrderStatus = $orderService->getOrder()->getOrderStatus();

        if ($request->isMethod('post')) {
            // Validate stats change, and then perform :P
            $formStatus = $request->get('status');
            if ($orderService->isValidOrderStatusChange($currentOrderStatus, $this->formToEntityStatus($formStatus))) {
                switch($formStatus) {
                    case 'confirm':
                        $orderService->statusAccepted('restourant_mobile');
                    break;

                    case 'delay':
                        $orderService->statusDelayed('restourant_mobile', 'delay reason: '.$request->get('delay_reason'));
                        $orderService->getOrder()->setDelayed(true);
                        $orderService->getOrder()->setDelayReason($request->get('delay_reason'));
                        $orderService->getOrder()->setDelayDuration($request->get('delay_duration'));
                        $orderService->saveDelay();
                    break;

                    case 'cancel':
                        $orderService->statusCanceled('restourant_mobile');
                    break;

                    case 'finish':
                        $orderService->statusFinished('restourant_mobile');
                    break;

                    case 'completed':
                        $orderService->statusCompleted('restourant_mobile');
                    break;
                }

                $orderService->saveOrder();

                return $this->redirect(
                    $this->generateUrl('ordermobile', array('hash' => $hash))
                );
            } else {
                $errorMessage = sprintf(
                    'Restoranas %s bande uzsakymui #%d pakeisti uzsakymo statusa is "%s" i "%s"',
                    $orderService->getOrder()->getPlaceName(),
                    $orderService->getOrder()->getId(),
                    $currentOrderStatus,
                    $orderService->getOrder()->getOrderStatus()
                );
                $this->get('logger')->error($errorMessage);
            }
        }
        return $this->render('FoodOrderBundle:Default:mobile.html.twig', array('order' => $order));
    }

    /**
     * Mobile admin page for order to be monitored and ruined
     * @param $hash
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mobileAdminAction($hash, Request $request)
    {
        $order = $this->get('food.order')->getOrderByHash($hash);
        if ($request->isMethod('post')) {
            switch($request->get('status')) {
                case 'confirm':
                    $this->get('food.order')->statusAccepted('admin_mobile');
                break;

                case 'delay':
                    $this->get('food.order')->statusDelayed('admin_mobile', 'delay reason: '.$request->get('delay_reason'));
                    $this->get('food.order')->getOrder()->setDelayed(true);
                    $this->get('food.order')->getOrder()->setDelayReason($request->get('delay_reason'));
                    $this->get('food.order')->getOrder()->setDelayDuration($request->get('delay_duration'));
                    $this->get('food.order')->saveDelay();
                    $this->get('food.order')->getOrderByHash($hash);
                break;

                case 'cancel':
                    $this->get('food.order')->statusCanceled('admin_mobile');
                break;

                case 'finish':
                    $this->get('food.order')->statusFinished('admin_mobile');
                break;

                case 'completed':
                    $this->get('food.order')->statusCompleted('admin_mobile');
                break;
            }
            $this->get('food.order')->saveOrder();

            return $this->redirect(
                $this->generateUrl('order_support_mobile', array('hash' => $hash))
            );
        }
        return $this->render('FoodOrderBundle:Default:mobile_admin.html.twig', array('order' => $order));
    }

    /**
     * @param $hash
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mobileDriverAction($hash, Request $request)
    {
        $orderService =  $this->get('food.order');
        $order = $orderService->getOrderByHash($hash);
        $currentOrderStatus = $orderService->getOrder()->getOrderStatus();

        if ($request->isMethod('post')) {
            // Validate stats change, and then perform :P
            $formStatus = $request->get('status');
            if ($orderService->isValidOrderStatusChange($currentOrderStatus, $this->formToEntityStatus($formStatus))) {
                switch($formStatus) {
                    case 'completed':
                        $this->get('food.order')->statusCompleted('driver_mobile');
                    break;
                }
                $this->get('food.order')->saveOrder();

                return $this->redirect(
                    $this->generateUrl('drivermobile', array('hash' => $hash))
                );
            } else {
                $errorMessage = sprintf(
                    'Vairuotojas %s bande uzsakymui #%d pakeisti uzsakymo statusa is "%s" i "%s"',
                    $orderService->getOrder()->getDriver()->getName(),
                    $orderService->getOrder()->getId(),
                    $currentOrderStatus,
                    $orderService->getOrder()->getOrderStatus()
                );
                $this->get('logger')->error($errorMessage);
            }
        }
        return $this->render('FoodOrderBundle:Default:mobile-driver.html.twig', array('order' => $order));
    }

    /**
     * @param $hash
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function driverInvoiceAction($hash)
    {
        $order = $this->get('food.order')->getOrderByHash($hash);
        return $this->render('FoodOrderBundle:Default:driver-invoice.html.twig', array('order' => $order));
    }

    /**
     * @param $hash
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function restaurantInvoiceAction($hash)
    {
        $order = $this->get('food.order')->getOrderByHash($hash);
        return $this->render('FoodOrderBundle:Default:restaurant-invoice.html.twig', array('order' => $order));
    }

    /**
     * @param string$formStatus
     * @return string
     */
    public function formToEntityStatus($formStatus)
    {
        $statusTable = array(
            'confirm' => OrderService::$status_accepted,
            'delay' => OrderService::$status_delayed,
            'cancel' => OrderService::$status_canceled,
            'finish' => OrderService::$status_finished,
            'completed' => OrderService::$status_completed,
        );

        if (!isset($statusTable[$formStatus])) {
            return '';
        }

        return $statusTable[$formStatus];
    }

    public function testNav1Action()
    {
        $this->get('food.nav')->testInsertOrder();
        die("TEST NAV");
    }

    public function testNav2Action()
    {
        $this->get('food.nav')->getLastOrders();
        die("TEST NAV");
    }

    public function postToNavAction($id)
    {
        echo "<pre>";
        $order = $this->get('doctrine')->getRepository('FoodOrderBundle:Order')->find($id);
        $this->get('food.nav')->putTheOrderToTheNAV($order);
        die("THE END");
    }

    public function updatePricesNavAction($id)
    {
        echo "<pre>";
        $order = $this->get('doctrine')->getRepository('FoodOrderBundle:Order')->find($id);
        $this->get('food.nav')->updatePricesNAV($order);
        die("THE END UPDATE PRC NAV");
    }
}
