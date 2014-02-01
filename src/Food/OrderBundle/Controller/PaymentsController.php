<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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

            $orderService->setPaymentStatus($orderService::$paymentStatusComplete);

        } catch (\Exception $e) {
            //handle the callback validation error here
            $logger->alert("payment data validation failed!. Error: ".$e->getMessage());
            $logger->alert("trace: ".$e->getTraceAsString());

            if ($order) {
                $orderService->setPaymentStatus($orderService::$paymentStatusError, $e->getMessage());
            }

            return new Response($e->getTraceAsString(), 500);
        }

        // TODO - Parodom, kad viskas yra super ir gaus valgyt kazkada :)
        return new Response('Payment accepted');
    }

    public function payseraCancelAction()
    {
        $logger = $this->container->get("logger");
        $logger->alert("==========================\ncancel payment action for paysera came\n====================================\n");
        $logger->alert("Request data: ".var_export($this->getRequest()->query->all(), true));
        $logger->alert('-----------------------------------------------------------');

        try {
            $callbackValidator = $this->get('evp_web_to_pay.callback_validator');
            $data = $callbackValidator->validateAndParseData($this->getRequest()->query->all());

            $logger->alert("Parsed accept data: ".var_export($data, true));
            $logger->alert('-----------------------------------------------------------');

            $orderService = $this->container->get('food.order');
            $order = $orderService->getOrderById($data['orderid']);

            $orderService->setPaymentStatus($orderService::$paymentStatusCanceled);

            if (!$order) {
                throw new \Exception('Order not found. Order id from Paysera: '.$data['orderid']);
            }
        } catch (\Exception $e) {
            //handle the callback validation error here
            $logger->alert("payment data validation failed!. Error: ".$e->getMessage());
            $logger->alert("trace: ".$e->getTraceAsString());

            if ($order) {
                $orderService->setPaymentStatus($orderService::$paymentStatusError, $e->getMessage());
            }

            return new Response($e->getTraceAsString(), 500);
        }

        // TODO - Parodom, kad nutiko beda, mes informuoti ir siulome bandyti dar karta??? arba pasakom, kad jus atsisakete susimoketi, gailike
        return new Response('Payment canceled');
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
                // Lets check if order in our side is all OK
                $logger->alert('-- Payment is valid. Procceed with care..');
                return new Response('OK');
            }
        } catch (\Exception $e) {
            //handle the callback validation error here
            $logger->alert("payment callback validation failed!. Error: ".$e->getMessage());
            $logger->alert("trace: ".$e->getTraceAsString());

            if ($order) {
                $orderService->setPaymentStatus($orderService::$paymentStatusError, $e->getMessage());
            }

            return new Response($e->getTraceAsString(), 500);
        }
    }
}
