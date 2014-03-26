<?php

namespace Food\AppBundle\Controller;

use Food\OrderBundle\Service\OrderService;
use Food\OrderBundle\Service\PaySera;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;


class TestController extends Controller
{
    public function indexAction()
    {
        $fo = $this->get('food.order');
        $list = $fo->getOrdersUnassigned('Vilnius');
        echo "<pre>";
        var_dump($list[0]->getPlacePoint()->getCity());
        var_dump($list[0]->getPlacePoint()->getLon());

        echo "</pre>";
        return new Response('Uber');
    }

    public function paymentAction()
    {
        /**
         * @var OrderService $orderService
         */
        $orderService = $this->container->get('food.order');

        /**
         * @var PaySera $paysera
         */
        $paysera = $this->container->get('food.paysera_biller');
//        $paysera->setTest(1);
//        $paysera->setAcceptUrl($this->generateUrl('paysera_accept'));
//        // TODO Paysera negrazina nieko, jei nutrauki mokejima. Gal vertetu order hash perduoti urlu del visa ko? jei sesija nusimustu?
//        $paysera->setCancelUrl($this->generateUrl('paysera_cancel'));
//        $paysera->setCallbackUrl($this->generateUrl('paysera_callback'));
        $orderService->setPayseraBiller($paysera);

        $redirectUrl = $orderService->billOrder(1, 'paysera');

        if (!empty($redirectUrl)) {
            return new RedirectResponse($redirectUrl);
        }

        return new Response("Ola, mister payment nothing happened :)");
    }
}