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
        $ml = $this->get('food.mailer');

        $variables = array(
            'username' => 'Birutė Biliūtė',
            'maisto_gamintojas' => 'FitFood',
            'maisto_ruosejas' => 'FitFood',
            'uzsakymas' => '1 butelis Šaltupio. 2 buteliai Krantų. 3 buteliai Smigio',
            'adresas' => 'Laisves 77c-58',
            'pristatymo_data' => 'Vakar',
        );

        $ml->setVariables( $variables )->setRecipient( 'paulius@foodout.lt', 'Sample Client')->setId( 30009269 )->send();


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

    public function reportAction()
    {
        $orderService = $this->get('food.order');

        $orders = $orderService->getYesterdayOrdersGrouped();

        return (
            $this->render(
                'FoodOrderBundle:Command:accounting_yesterday_report.txt.twig',
                array(
                    'orders' => $orders,
                    'reportFor' => date("Y-m-d", strtotime('-1 day')),
                )
            )
        );
    }
}