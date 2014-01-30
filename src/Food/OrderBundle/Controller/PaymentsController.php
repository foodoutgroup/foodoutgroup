<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Acl\Exception\Exception;

class PaymentsController extends Controller
{
    public function payseraAcceptAction()
    {
        // TODO accept da payment
        $logger = $this->container->get("logger");
        $logger->alert("==========================\naccept payment action for paysera came\n====================================\n");
        $logger->alert("Request data: ".var_export($this->getRequest()->query->all(), true));
        $logger->alert('-----------------------------------------------------------');
    }

    public function payseraCancelAction()
    {
        // TODO cancel cart, show das error
        $logger = $this->container->get("logger");
        $logger->alert("==========================\ncancel payment action for paysera came\n====================================\n");
        $logger->alert("Request data: ".var_export($this->getRequest()->query->all(), true));
        $logger->alert('-----------------------------------------------------------');
    }

    public function payseraCallbackAction()
    {
        // TODO callback with data. Check, do accept or do cancel
        $logger = $this->container->get("logger");
        $logger->alert("==========================\ncallback payment action for paysera came\n====================================\n");
        $logger->alert("Request data: ".var_export($this->getRequest()->query->all(), true));
        try {
            $callbackValidator = $this->get('evp_web_to_pay.callback_validator');
            $data = $callbackValidator->validateAndParseData($this->getRequest()->query->all());
            $logger->alert('---------------------- parsing data ----------------------');
            $logger->alert('Parsed data: '.var_export($data, true));
            if ($data['status'] == 1) {
                // Provide your customer with the service

                return new Response('OK');
            }
        } catch (\Exception $e) {
            //handle the callback validation error here
            $logger->alert("payment callback validation failed!. Error: ".$e->getMessage());
            $logger->alert("trace: ".$e->getTraceAsString());

            return new Response($e->getTraceAsString(), 500);
        }
    }
}
