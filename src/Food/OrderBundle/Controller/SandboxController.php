<?php

namespace Food\OrderBundle\Controller;

use Food\OrderBundle\Entity\WalletTransaction;
use Food\OrderBundle\Service\OrderService;
use Food\OrderBundle\Service\WalletService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Exception\Exception;
use Food\OrderBundle\Service\Events\BanklinkEvent;
use Symfony\Component\Validator\Constraints\DateTime;

class SandboxController extends Controller
{
    public function returnAction(Request $request) {
        $orderService = $this->container->get('food.order');
        $order = $orderService->getOrderByHash($request->get('ResNum'));

        if ($request->get('status') == 'accept') {
            return $this->render(
                ($order->getMobile() ? 'FoodApiBundle:Default:payment_success.html.twig' : 'FoodCartBundle:Default:payment_success.html.twig'),
                array('order' => $order)
            );
        }

        return $this->render(
            ($order->getMobile() ? 'FoodApiBundle:Default:payment_fail.html.twig' : 'FoodOrderBundle:Payments/SamanBank:fail.html.twig')
        );
    }

    public function chooseAction(Request $request) {
        return $this->render(
            'FoodOrderBundle:Payments/Sandbox:choose.html.twig',
            array(
                'Amount' => $request->get('Amount'),
                'RedirectURL' => $request->get('RedirectURL'),
                'ResNum' => $request->get('ResNum'),
            )
        );
    }

}