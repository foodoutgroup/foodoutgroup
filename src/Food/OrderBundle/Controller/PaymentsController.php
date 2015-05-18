<?php

namespace Food\OrderBundle\Controller;

use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Exception\Exception;
use Food\OrderBundle\Form\SebBanklinkType;
use Food\OrderBundle\Service\Banklink\Seb;
use Food\OrderBundle\Service\Events\BanklinkEvent;

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
            $oldStatus = $order->getPaymentStatus();

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

            /*
             * Jei jau buvo fiksuota sekme - necancelinam, bet ir bukim geri - neluzkim ir nerodykim dumpo bet kuriuo atveju
             */
            if ($oldStatus != OrderService::$paymentStatusComplete) {
                throw $e;
            }
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
                // Nedubliuokim pranesimu, jei paymentas jau patvirtintas, tai ner ko to daryt dar karta
                if ($order->getPaymentStatus() != $orderService::$paymentStatusComplete) {
                    // Paysera was waiting for funds to be transfered
                    $logger->alert('-- Payment is valid. Procceed with care..');
                    $orderService->setPaymentStatus($orderService::$paymentStatusComplete, 'Paysera billed payment');
                    $orderService->saveOrder();
                    $orderService->informPlace();

                    // Jei naudotas kuponas, paziurim ar nereikia jo deaktyvuoti
                    $orderService->deactivateCoupon();
                }

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
}
