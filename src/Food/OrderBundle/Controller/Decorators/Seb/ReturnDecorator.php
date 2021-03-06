<?php

namespace Food\OrderBundle\Controller\Decorators\Seb;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\LockMode;
use Food\OrderBundle\Service\Banklink\Seb as SebService;

trait ReturnDecorator
{
    protected function handleReturn(Request $request)
    {
        // a hack
        $request->request->add($request->query->all());

        // services
        $orderService = $this->container->get('food.order');
        $config = $this->container->getParameter('seb');
        $seb = $this->container->get('food.seb_banklink');
        $dispatcher = $this->container->get('event_dispatcher');
        $cartService = $this->get('food.cart');
        $placeService = $this->get('food.places');
        $navService = $this->get('food.nav');
        $em = $this->get('doctrine')->getManager();
        $logger = $this->get('logger');

        // preparation
        $orderId = max(0, (int)$request->get('VK_REF'));
        $service = max(0, $request->get('VK_SERVICE', 0));
        $mac = $request->get('VK_MAC', '');
        $verified = false;

        // template
        $view = 'FoodOrderBundle:Payments:seb_banklink/failure.html.twig';

        // order
        try {
            $order = $em->getRepository('FoodOrderBundle:Order')
                ->find($orderId, LockMode::OPTIMISTIC)
            ;
            $orderService->setOrder($order);
        } catch (\Exception $e) {
            return [$view, []];
        }

        if($order->getMobile()) {
            $view = 'FoodApiBundle:Default:payment_fail.html.twig';
        }

        // banklink log
        $this->logBanklink($dispatcher, $request, $order);

        // verify
        $data = $request->request->all();

        try {
            // generate encoded MAC
            $myMac = $seb->mac($data, $service);

            // finally update form
            $verified = $seb->verify($myMac, $mac, $seb->getBankKey());
        } catch (\Exception $e) {
        }

        if ($verified) {
            if ($config['WAITING_SERVICE'] == $service) {
                // template
                $view = 'FoodOrderBundle:Payments:' .
                    'seb_banklink/waiting.html.twig';

                // processing
                $this->logProcessingAndFinish('SEB banklink payment started',
                    $orderService,
                    $order,
                    $cartService);
            } elseif ($config['FAILURE_SERVICE'] == $service) {
                // failure
                $this->logFailureAndFinish('SEB banklink canceled payment',
                    $orderService,
                    $order);
            } elseif ($config['SUCCESS_SERVICE'] == $service) {
                // template
                $view = 'FoodCartBundle:Default:payment_success.html.twig';
                if($order->getMobile()) {
                    $view = 'FoodApiBundle:Default:payment_success.html.twig';
                }
                // success
                $this->logPaidAndFinish('SEB banklink billed payment',
                    $orderService,
                    $order,
                    $cartService,
                    $em,
                    $navService,
                    $logger,
                    $placeService
                );
            }
        }

        $data = ['order' => $order];

        return [$view, $data];
    }
}
