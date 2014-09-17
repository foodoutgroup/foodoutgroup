<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Exception\Exception;
use Food\OrderBundle\Form\SebBanklinkType;
use Food\OrderBundle\Service\Banklink\Seb;

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
                if ($order->getPaymentStatus() != $orderService::$paymentStatusComplete) {
                    $orderService->setPaymentStatus($orderService::$paymentStatusError, $e->getMessage());
                    $orderService->saveOrder();
                } else {
                    $logger->error('Payment status was completed. Can not cancel it. Its final!');
                }
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
        $gateway = $this->container->get('pirminis_banklink_gateway');
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
                         //  'price' => (string)round($order->getTotal() * 100),
                         'price' => '1',
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

    public function swedbankCreditCardGatewayRedirectAction($id)
    {
        $router = $this->container->get('router');
        $gateway = $this->container->get('pirminis_credit_card_gateway');
        $em = $this->container->get('doctrine.orm.entity_manager');

        // get order
        $order = $em->getRepository('FoodOrderBundle:Order')
                    ->find($id);

        // configuration
        $returnUrl = $router->generate('swedbank_credit_card_gateway_success',
                                        [],
                                        true);
        $expiryUrl = $router->generate('swedbank_credit_card_gateway_failure',
                                        [],
                                        true);

        $options = array('order_id' => substr($order->getId() . '_' . time(),
                                              0,
                                              16),
                         //  'price' => (string)round($order->getTotal() * 100),
                         'price' => '1',
                         'transaction_datetime' => date('Y-m-d H:i:s'),
                         'comment' => 'no comment',
                         'return_url' => $returnUrl,
                         'expiry_url' => $expiryUrl);
        $gateway->set_options($options);

        return new RedirectResponse($gateway->redirect_url('swedbank'));
    }

    public function swedbankGatewaySuccessAction(Request $request)
    {
        // services
        $orderService = $this->container->get('food.order');
        $gateway = $this->container->get('pirminis_credit_card_gateway');

        $view = 'FoodOrderBundle:Payments:' .
                'swedbank_gateway/something_wrong.html.twig';

        // get order
        $transactionId = $gateway->order_id('swedbank', $request);

        if (empty($transactionId)) return $this->render($view);

        // extract actual order id. say thanks to swedbank requirements
        $transactionIdSplit = explode('_', $transactionId);
        $orderId = !empty($transactionIdSplit[0]) ? $transactionIdSplit[0] : 0;
        $order = $orderService->getOrderById($orderId);

        if (!$order) {
            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/order_not_found.html.twig';
            return $this->render($view);
        }

        // is order paid? let's find out!
        if ($service->is_successful_payment('swedbank', $request)) {
            $this->markOrderPaid($orderService);

            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/success.html.twig';
        }

        return $this->render($view, ['order' => $order]);
    }

    public function swedbankGatewayFailureAction(Request $request)
    {
        return $this->swedbankGatewaySuccessAction($request);
    }

    public function swedbankCreditCardGatewaySuccessAction(Request $request)
    {
        // services
        $orderService = $this->container->get('food.order');
        $gateway = $this->container->get('pirminis_gateway');

        $view = 'FoodOrderBundle:Payments:' .
                'swedbank_gateway/something_wrong.html.twig';

        // get order
        $transactionId = $gateway->order_id('swedbank', $request);

        if (empty($transactionId)) return $this->render($view);

        // extract actual order id. say thanks to swedbank requirements
        $transactionIdSplit = explode('_', $transactionId);
        $orderId = !empty($transactionIdSplit[0]) ? $transactionIdSplit[0] : 0;
        $order = $orderService->getOrderById($orderId);

        if (!$order) {
            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/order_not_found.html.twig';
            return $this->render($view);
        }

        // is this event (callback from bank) or just ordinary customer?
        $isEvent = $gateway->is_event('swedbank', $request);

        // is order paid? let's find out!
        if ((!$isEvent &&
             $gateway->is_authorized('swedbank', $request)) ||
            ($isEvent &&
             $gateway->is_event_authorized('swedbank', $request))
        ) {
            $this->markOrderPaid($orderService);

            if ($isEvent) {
                return new Response('<Response>OK</Response>');
            } else {
                $view = 'FoodOrderBundle:Payments:' .
                        'swedbank_gateway/success.html.twig';
            }
        // is order payment accepted and is currently processing?
        } elseif ((!$isEvent &&
                   $gateway->requires_investigation('swedbank', $request)) ||
                  ($isEvent &&
                   $gateway->event_requires_investigation('swedbank', $request))
        ) {
            $this->markOrderProcessing($orderService, $order);

            if ($isEvent) {
                return new Response('<Response>OK</Response>');
            } else {
                $view = 'FoodOrderBundle:Payments:' .
                        'swedbank_gateway/processing.html.twig';
            }
        // is payment cancelled due to reasons?
        } elseif ((!$isEvent &&
                   $gateway->is_cancelled('swedbank', $request)) ||
                  ($isEvent &&
                   $gateway->is_event_cancelled('swedbank', $request))
        ) {
            $this->markOrderCancelled($orderService, $order);

            if ($isEvent) {
                return new Response('<Response>OK</Response>');
            } else {
                $view = 'FoodOrderBundle:Payments:' .
                        'swedbank_gateway/cancelled.html.twig';
            }
        // did we get error from the bank? :(
        } elseif ($gateway->is_error('swedbank', $request)) {
            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/error.html.twig';
        // was there a communication error with/in bank?
        } elseif ($gateway->communication_error('swedbank', $request)) {
            $view = 'FoodOrderBundle:Payments:' .
                    'swedbank_gateway/communication_error.html.twig';
        }

        return $this->render($view, ['order' => $order]);
    }

    public function swedbankCreditCardGatewayFailureAction(Request $request)
    {
        return $this->swedbankCreditCardGatewaySuccessAction($request);
    }

    public function sebBanklinkRedirectAction($id)
    {
        $router = $this->container->get('router');
        $factory = $this->container->get('form.factory');
        $seb = $this->container->get('food.seb_banklink');

        // get order
        $order = $this->findOrder($id);

        // seb banklink type
        $options = ['snd_id' => 'snd_id',
                    'curr' => 'LTL',
                    'acc' => 'acc',
                    'name' => 'name',
                    'lang' => 'LIT',
                    'stamp' => $order->getId(),
                    'amount' => (string)round($order->getTotal() * 100),
                    'ref' => $order->getId(),
                    'msg' => 'no seb banklink message',
                    'return_url' => $router->generate('seb_banklink_return',
                                                      [],
                                                      true)];
        $type = new SebBanklinkType($options);

        // redirect form
        $options = ['action' => $seb->getBankUrl(), 'method' => 'POST'];
        $form = $factory->createNamed('', $type, null, $options);

        $this->updateFormWithMAC($form);

        // template
        $view = 'FoodOrderBundle:Payments:' .
                'seb_banklink/redirect.html.twig';

        return $this->render($view, ['form' => $form->createView()]);
    }

    public function sebBanklinkReturnAction(Request $request)
    {
        // services
        $orderService = $this->container->get('food.order');
        $seb = $this->container->get('food.seb_banklink');

        // preparation
        $orderId = max(0, (int)$request->get('VK_REF'));
        $service = max(0, $request->get('VK_SERVICE', 0));
        $mac = $request->get('VK_MAC', '');
        $data = [];

        // template
        $view = 'FoodOrderBundle:Payments:' .
                'seb_banklink/something_wrong.html.twig';

        // order
        $order = $orderService->getOrderById($orderId);

        // verify
        try {
            $request = 
            foreach ($request->all() as $child) {
                $data[$child->getName()] = $child->getData();
            }

            $mac = $seb->mac($data, $service);
            $verified = $seb->verify($mac, Arr::getOrElse($data, 'VK_MAC', ''), $publicKey);
        } catch (\Exception $e) {
            $api->status($cart, 'rejected');

            return new RedirectResponse($this->generateUrl('fish_parado_unsuccess'));
        }

        if (Seb::WAITING_SERVICE == $service) {
            // template
            $view = 'FoodOrderBundle:Payments:' .
                    'seb_banklink/waiting.html.twig';

            // log
            $orderService->logPayment(
                $order,
                'SEB banklink payment started',
                'SEB banklink payment accepted. Waiting for funds to be billed',
                $order
            );
        } elseif (Seb::FAILURE_SERVICE == $service) {
            // template
            $view = 'FoodOrderBundle:Payments:' .
                    'seb_banklink/failure.html.twig';

            // log
            $orderService->logPayment(
                $order,
                'SEB banklink payment canceled',
                'SEB banklink canceled in SEB',
                $order
            );

            $orderService->setPaymentStatus(
                $orderService::$paymentStatusCanceled,
                'User canceled payment in SEB banklink');
        } elseif (Seb::SUCCESS_SERVICE == $service) {
            // template
            $view = 'FoodOrderBundle:Payments:' .
                    'seb_banklink/success.html.twig';

            // log
            $orderService->setPaymentStatus(
                $orderService::$paymentStatusComplete,
                'SEB banklink billed payment');
            $orderService->saveOrder();
            $orderService->informPlace();

            // Jei naudotas kuponas, paziurim ar nereikia jo deaktyvuoti
            $orderService->deactivateCoupon();
        }

        return $this->render($view);
    }

    protected function markOrderPaid($orderService)
    {
        $orderService->setPaymentStatus(
            $orderService::$paymentStatusComplete,
            'Swedbank gateway billed payment');
        $orderService->saveOrder();
        $orderService->informPlace();

        // Jei naudotas kuponas, paziurim ar nereikia jo deaktyvuoti
        $orderService->deactivateCoupon();
    }

    protected function markOrderProcessing($orderService, $order)
    {
        $orderService->logPayment(
            $order,
            'Swedbank Gateway wallet payment started',
            'Swedbank Gateway wallet payment accepted. Waiting for funds to be billed',
            $order
        );

        usleep(400000);
        $order = $orderService->getOrderById($orderId);
    }

    protected function markOrderCancelled($orderService, $order)
    {
        $orderService->logPayment(
            $order,
            'Swedbank gateway payment canceled',
            'Swedbank gateway canceled in Swedbank',
            $order
        );

        $orderService->setPaymentStatus($orderService::$paymentStatusCanceled, 'User canceled payment in Swedbank gateway');
    }

    protected function findOrder($id)
    {
        return $this->container
                    ->get('doctrine.orm.entity_manager')
                    ->getRepository('FoodOrderBundle:Order')
                    ->find($id);
    }

    protected function updateFormWithMAC($form)
    {
        $seb = $this->container->get('food.seb_banklink');

        // fill array with form data
        $data = [];

        foreach ($form->all() as $child) {
            $data[$child->getName()] = $child->getData();
        }

        // generate encoded MAC
        $mac = $seb->sign($seb->mac($data, Seb::REDIRECT_SERVICE),
                          $seb->getPrivateKey());

        // finally update form
        $form->get('VK_MAC')->setData($mac);
    }
}
