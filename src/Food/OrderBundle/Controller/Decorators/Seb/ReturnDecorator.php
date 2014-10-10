<?php

namespace Food\OrderBundle\Controller\Decorators\Seb;

use Symfony\Component\HttpFoundation\Request;
use Food\OrderBundle\Service\Banklink\Seb as SebService;

trait ReturnDecorator
{
    protected function handleReturn(Request $request)
    {
        // a hack
        ini_set('display_errors', true);
        error_reporting(E_ALL);
        $request->request->add($request->query->all());

        // services
        $orderService = $this->container->get('food.order');
        $seb = $this->container->get('food.seb_banklink');
        $dispatcher = $this->container->get('event_dispatcher');
        $cartService = $this->get('food.cart');

        // preparation
        $orderId = max(0, (int)$request->get('VK_REF'));
        $service = max(0, $request->get('VK_SERVICE', 0));
        $mac = $request->get('VK_MAC', '');
        $verified = false;

        // template
        $view = 'FoodOrderBundle:Payments:' .
                'seb_banklink/something_wrong.html.twig';

        // order
        $order = $orderService->getOrderById($orderId);

        // banklink log
        $this->logBanklink($dispatcher, $request, $order);

        // verify
        $data = [];

        try {
            foreach ($request->request->all() as $key => $value) {
                $data[$key] = $value;
            }

            // generate encoded MAC
            $myMac = $seb->sign($seb->mac($data, $service),
                              $seb->getPrivateKey());

            // finally update form
            $verified = $seb->verify($myMac, $mac, $seb->getBankKey());
        } catch (\Exception $e) {
        }

        if ($verified) {
            if (SebService::WAITING_SERVICE == $service) {
                // template
                $view = 'FoodOrderBundle:Payments:' .
                        'seb_banklink/waiting.html.twig';

                // processing
                $this->logProcessingAndFinish($orderService,
                                              $order,
                                              $cartService);
            } elseif (SebService::FAILURE_SERVICE == $service) {
                // template
                $view = 'FoodOrderBundle:Payments:' .
                        'seb_banklink/failure.html.twig';

                // failure
                $this->logFailureAndFinish($orderService, $order);
            } elseif (Seb::SUCCESS_SERVICE == $service) {
                // template
                // $view = 'FoodOrderBundle:Payments:' .
                //         'seb_banklink/success.html.twig';
                $view = 'FoodCartBundle:Default:payment_success.html.twig';

                // success
                $this->logPaidAndFinish($orderService, $order, $cartService);
            }
        }

        $data = ['order' => $order];
        return [$view, $data];
    }
}
