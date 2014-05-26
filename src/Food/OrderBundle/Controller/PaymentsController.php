<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Exception\Exception;

class PaymentsController extends Controller
{
    public function payseraAcceptAction()
    {
        $logger = $this->container->get("logger");
        $logger->alert("==========================\naccept payment action for paysera came\n====================================\n");
        $logger->alert("Request data: ".var_export($this->getRequest()->query->all(), true));
        $logger->alert('-----------------------------------------------------------');

        try {
            $callbackValidator = $this->get('evp_web_to_pay.callback_validator');
            $data = $callbackValidator->validateAndParseData($this->getRequest()->query->all());

            $logger->alert("Parsed accept data: ".var_export($data, true));
            $logger->alert('-----------------------------------------------------------');

            $orderService = $this->container->get('food.order');
            $order = $orderService->getOrderById($data['orderid']);

            if (!$order) {
                throw new \Exception('Order not found. Order id from Paysera: '.$data['orderid']);
            }

            if ($data['status'] == 1) {
                $orderService->logPayment(
                    $order,
                    'paysera payment accepted',
                    'Payment succesfuly billed in Paysera',
                    $order
                );

                $orderService->setPaymentStatus($orderService::$paymentStatusComplete);
            } else if ($data['status'] == 2) {
                // Paysera wallet used. Payment in process, money havent reached our pocket yet
                $orderService->logPayment(
                    $order,
                    'paysera wallet payment started',
                    'Paysera wallet payment accepted. Waiting for funds to be billed',
                    $order
                );

                $orderService->setPaymentStatus($orderService::$paymentStatusWait);

                $this->get('food.cart')->clearCart($order->getPlace());

                return new RedirectResponse($this->generateUrl('food_cart_wait', array('orderHash' => $order->getOrderHash())));
            }

        } catch (\Exception $e) {
            //handle the callback validation error here
            $logger->alert("payment data validation failed!. Error: ".$e->getMessage());
            $logger->alert("trace: ".$e->getTraceAsString());

            if ($order) {
                $orderService->statusFailed();
                $orderService->setPaymentStatus($orderService::$paymentStatusError, $e->getMessage());
                $orderService->saveOrder();
            }

            return new Response($e->getTraceAsString(), 500);
        }


        $this->get('food.cart')->clearCart($order->getPlace());
        $orderService->informPlace();

        return new RedirectResponse($this->generateUrl('food_cart_success', array('orderHash' => $order->getOrderHash())));
    }

    public function payseraCancelAction($hash)
    {
        $logger = $this->container->get("logger");
        $logger->alert("==========================\ncancel payment action for paysera came\n====================================\n");
        $logger->alert("Request data: ".var_export($this->getRequest()->query->all(), true));
        $logger->alert('-----------------------------------------------------------');

        try {
            $orderService = $this->container->get('food.order');
            $order = $orderService->getOrderByHash($hash);

            $orderService->logPayment(
                $order,
                'paysera payment canceled',
                'Payment canceled in Paysera',
                $order
            );

            $orderService->setPaymentStatus($orderService::$paymentStatusCanceled, 'User canceled payment');
        } catch (\Exception $e) {
            //handle the callback validation error here
            $logger->alert("payment cancelation fails!. Error: ".$e->getMessage());
            $logger->alert("trace: ".$e->getTraceAsString());

            if ($order) {
                $orderService->setPaymentStatus($orderService::$paymentStatusError, $e->getMessage());
                $orderService->saveOrder();
            }

            return new Response($e->getTraceAsString(), 500);
        }

        return new RedirectResponse(
            $this->generateUrl(
                'food_cart',
                array('placeId' => $order->getPlace()->getId())
            )
            .'?hash='.$order->getOrderHash()
        );
    }

    public function payseraCallbackAction()
    {
        $logger = $this->container->get("logger");
        $logger->alert("==========================\ncallback payment action for paysera came\n====================================\n");
        try {
            $callbackValidator = $this->get('evp_web_to_pay.callback_validator');
            $data = $callbackValidator->validateAndParseData($this->getRequest()->query->all());
            $logger->alert('-- parsing data');
            $logger->alert('Parsed data: '.var_export($data, true));

            $orderService = $this->container->get('food.order');
            $order = $orderService->getOrderById($data['orderid']);

            if (!$order) {
                throw new \Exception('Order not found. Order id: '.$data['orderid']);
            }

            if ($data['status'] == 1) {
                // Paysera was waiting for funds to be transfered
                if ($order->getPaymentStatus() == $orderService::$paymentStatusWaitFunds) {
                    $logger->alert('-- Payment was waiting for funds... now they are transfered');

                    $orderService->setPaymentStatus($orderService::$paymentStatusComplete);
                    $orderService->saveOrder();
                    $orderService->informPlace();
                } else {
                    // Lets check if order in our side is all OK
                    $logger->alert('-- Payment is valid. Procceed with care..');
                }
                return new Response('OK');
            }
        } catch (\Exception $e) {
            //handle the callback validation error here
            $logger->alert("payment callback validation failed!. Error: ".$e->getMessage());
            $logger->alert("trace: ".$e->getTraceAsString());

            if ($order) {
                $orderService->setPaymentStatus($orderService::$paymentStatusError, $e->getMessage());
                $orderService->saveOrder();
            }

            return new Response($e->getTraceAsString(), 500);
        }
    }
}
