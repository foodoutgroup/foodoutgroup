<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Exception\Exception;

class PaymentsController extends Controller
{
    public function payseraAcceptAction(Request $request)
    {
        $logger = $this->container->get("logger");
        $logger->alert("==========================\naccept payment action for paysera came\n====================================\n");
        $logger->alert("Request data: ".var_export($request->query->all(), true));
        $logger->alert('-----------------------------------------------------------');

        $orderService = $this->container->get('food.order');

        try {
            $callbackValidator = $this->get('evp_web_to_pay.callback_validator');
            $data = $callbackValidator->validateAndParseData($request->query->all());

            $logger->alert("Parsed accept data: ".var_export($data, true));
            $logger->alert('-----------------------------------------------------------');

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
            } else if ($data['status'] == 2) {
                // Paysera wallet used. Payment in process, money havent reached our pocket yet
                $orderService->logPayment(
                    $order,
                    'paysera wallet payment started',
                    'Paysera wallet payment accepted. Waiting for funds to be billed',
                    $order
                );

                // Apsauga nuo labai greito responso, kai viena sekunde sukrenta viskas - negrazu, bet saugotis reikia, nes numusam complete su waitu ir gaunasi cirkas..
                // Palaukiam 0.4s ir pasitikrine ar viskas ok - vaziuojam toliau
                usleep(400000);
                $order = $orderService->getOrderById($data['orderid']);

                if ($order->getPaymentStatus() != $orderService::$paymentStatusComplete) {
                    $orderService->setPaymentStatus($orderService::$paymentStatusWaitFunds);
                    $orderService->saveOrder();

                    $this->get('food.cart')->clearCart($order->getPlace());

                    return new RedirectResponse($this->generateUrl('food_cart_wait', array('orderHash' => $order->getOrderHash())));
                }
            }

        } catch (\Exception $e) {
            //handle the callback validation error here
            $logger->alert("payment data validation failed!. Error: ".$e->getMessage());
            $logger->alert("trace: ".$e->getTraceAsString());

            if (isset($order) && $order) {
                $orderService->statusFailed('paysera_payment');
                $orderService->setPaymentStatus($orderService::$paymentStatusError, $e->getMessage());
                $orderService->saveOrder();
            }

            return new Response($e->getTraceAsString(), 500);
        }


        $this->get('food.cart')->clearCart($order->getPlace());

        return new RedirectResponse($this->generateUrl('food_cart_success', array('orderHash' => $order->getOrderHash())));
    }

    public function payseraCancelAction($hash, Request $request)
    {
        $logger = $this->container->get("logger");
        $logger->alert("==========================\ncancel payment action for paysera came\n====================================\n");
        $logger->alert("Request data: ".var_export($request->query->all(), true));
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

            if (isset($order) && $order) {
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

    public function payseraCallbackAction(Request $request)
    {
        $logger = $this->container->get("logger");
        $logger->alert("==========================\ncallback payment action for paysera came\n====================================\n");

        $orderService = $this->container->get('food.order');

        try {
            $callbackValidator = $this->get('evp_web_to_pay.callback_validator');
            $data = $callbackValidator->validateAndParseData($request->query->all());
            $logger->alert('-- parsing data');
            $logger->alert('Parsed data: '.var_export($data, true));

            $order = $orderService->getOrderById($data['orderid']);

            if (!$order) {
                throw new \Exception('Order not found. Order id: '.$data['orderid']);
            }

            if ($data['status'] == 1) {
                // Paysera was waiting for funds to be transfered
                $logger->alert('-- Payment is valid. Procceed with care..');
                $orderService->setPaymentStatus($orderService::$paymentStatusComplete, 'Paysera billed payment');
                $orderService->saveOrder();
                $orderService->informPlace();

                // Jei naudotas kuponas, paziurim ar nereikia jo deaktyvuoti
                $orderService->deactivateCoupon();

                return new Response('OK');
            }
        } catch (\Exception $e) {
            //handle the callback validation error here
            $logger->alert("payment callback validation failed!. Error: ".$e->getMessage());
            $logger->alert("trace: ".$e->getTraceAsString());

            if (isset($order) && $order) {
                $orderService->setPaymentStatus($orderService::$paymentStatusError, $e->getMessage());
                $orderService->saveOrder();
            }

            return new Response($e->getTraceAsString(), 500);
        }
    }

    public function swedbankGatewayRedirectAction($id, $locale)
    {
        $router = $this->container->get('router');
        $gateway = $this->container->get('pirminis_gateway');
        $em = $this->container->get('doctrine.orm.entity_manager');

        // get order
        $order = $em->getRepository('FoodOrderBundle:Order')
                    ->find($id);

        $locale = $locale ? $locale : 'lt';

        // configuration
        $successUrl = $router->generate('swedbank_gateway_success',
                                        array('_locale' => $locale),
                                        true);
        $failureUrl = $router->generate('swedbank_gateway_failure',
                                        array('_locale' => $locale),
                                        true);

        $options = array('order_id' => substr($order->getId() . '_' . time(),
                                              0,
                                              16),
                         'price' => (string)round($order->getTotal() * 100),
                         'email' => $order->getUser()->getEmail(),
                         'transaction_datetime' => date('Y-m-d H:i:s'),
                         'comment' => 'no comment',
                         'success_url' => $successUrl,
                         'failure_url' => $failureUrl,
                         'language' => $locale);
        $gateway->set_options($options);

        $form = $gateway->form_for('swedbank');
        $view = 'FoodOrderBundle:Payments:' .
                'swedbank_gateway/redirect.html.twig';

        return $this->render($view, ['form' => $form->createView()]);
    }

    public function swedbankGatewaySuccessAction(Request $request)
    {
        // for testing purposes
        // $fake_data = array('DPGReferenceId' => '151802',
        //                    'TransactionId' => '489_4fd6dedd0');
        // $request->query->replace($fake_data);

        // services
        $orderService = $this->container->get('food.order');
        $gateway = $this->container->get('pirminis_gateway');

        $view = 'FoodOrderBundle:Payments:' .
                'swedbank_gateway/something_wrong.html.twig';

        // get order
        $transactionId = $gateway->order_id('swedbank', $request);

        if (empty($transactionId)) return $this->render($view);

        $transactionIdSplit = explode('_', $transactionId);
        $orderId = !empty($transactionIdSplit[0]) ? $transactionIdSplit[0] : 0;
        $order = $orderService->getOrderById($orderId);

        if (!$order) {
            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/order_not_found.html.twig';
            return $this->render($view);
        }

        if ($gateway->is_authorized('swedbank', $request)) {
            $orderService->setPaymentStatus($orderService::$paymentStatusComplete, 'Swedbank gateway billed payment');
            $orderService->saveOrder();
            $orderService->informPlace();

            // Jei naudotas kuponas, paziurim ar nereikia jo deaktyvuoti
            $orderService->deactivateCoupon();

            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/success.html.twig';
        } elseif ($gateway->requires_investigation('swedbank', $request)) {
            $orderService->logPayment(
                $order,
                'Swedbank Gateway wallet payment started',
                'Swedbank Gateway wallet payment accepted. Waiting for funds to be billed',
                $order
            );

            usleep(400000);
            $order = $orderService->getOrderById($data['orderid']);

            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/processing.html.twig';
        } elseif ($gateway->is_error('swedbank', $request)) {
            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/error.html.twig';
        } elseif ($gateway->is_cancelled('swedbank', $request)) {
            $orderService->logPayment(
                $order,
                'Swedbank gateway payment canceled',
                'Swedbank gateway canceled in Swedbank',
                $order
            );

            $orderService->setPaymentStatus($orderService::$paymentStatusCanceled, 'User canceled payment in Swedbank gateway');

            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/cancelled.html.twig';
        } elseif ($gateway->communication_error('swedbank', $request)) {
            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/communication_error.html.twig';
        }

        return $this->render($view, ['order' => $order]);
    }

    public function swedbankGatewayFailureAction(Request $request)
    {
        return $this->swedbankGatewaySuccessAction($request);
    }
}
