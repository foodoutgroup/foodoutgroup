<?php

namespace Food\AppBundle\Controller;

use Food\OrderBundle\Service\OrderService;
use Food\OrderBundle\Service\PaySera;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;


class TestController extends Controller
{
    /**
     * @return Response
     *
     * @codeCoverageIgnore
     */
    public function indexAction()
    {
        /*
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
        */

        $ord = $this->get('doctrine')->getRepository('FoodOrderBundle:Order')->find(1006);

        //$nav = $this->get('food.nav')->putTheOrderToTheNAV($ord);
        $returner = $this->get('food.nav')->updatePricesNAV($ord);
        var_dump($returner);
        echo '-- NEXT --';
        die();
        if($returner) {
            $returner = $this->get('food.nav')->updatePricesNAV($ord);
            var_dump($returner);
        }

        return new Response('Uber');
    }

    public function mssqlAction()
    {
        echo "<pre>";
        $link = mssql_pconnect('213.190.40.38:5566', 'fo_order', 'peH=waGe?zoOs69');
        if (!$link) {
            echo mssql_get_last_message();
            die();
        }
        if(!mssql_select_db('skamb_centras', $link)) {
            echo mssql_get_last_message();
            die();
        }
        return new Response();
    }

    /**
     * @return RedirectResponse|Response
     *
     * @codeCoverageIgnore
     */
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
        $orderService->setPayseraBiller($paysera);

        $redirectUrl = $orderService->billOrder(1, 'paysera');

        if (!empty($redirectUrl)) {
            return new RedirectResponse($redirectUrl);
        }

        return new Response("Ola, mister payment nothing happened :)");
    }

    /**
     * @return Response
     *
     * @codeCoverageIgnore
     */
    public function reportAction()
    {
        $em = $this->get('doctrine')->getManager();

//        $orders = $orderService->getDriversMonthlyOrderCount();
//
//        return (
//            $this->render(
//                'FoodOrderBundle:Command:accounting_monthly_driver_report.html.twig',
//                array(
//                    'orders' => $orders,
//                    'reportFor' => date("Y-m", strtotime('-1 month')),
//                )
//            )
//        );

        $orders = $em->getRepository('FoodOrderBundle:Order')->getYesterdayOrdersGrouped();

        return (
            $this->render(
                'FoodOrderBundle:Command:accounting_yesterday_report.html.twig',
                array(
                    'orders' => $orders,
                    'reportFor' => date("Y-m-d", strtotime('-1 day')),
                )
            )
        );
    }

    public function nav1Action()
    {

        $navs = $this->get('food.nav');
        $navs->getOrderHeader(1592);
        $navs->getOrderHeader(1593);

        die("MOJO");
    }

    public function nav12Action()
    {
        $navs = $this->get('food.nav');
        $client = $navs->getWSConnection();
        $return = $client->FoodOutUpdatePrices(array('pInt' =>2000002044));

        die('E');
        $cs = $this->get('food.cart');
        $place = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:Place')->find(63);
        $pp = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:PlacePoint')->find(82);
        $cds = $cs->getCartDishes($place);

        $navs = $this->get('food.nav');

        $navs->validateCartInNav(
            '8615644121',
            $pp,
            date('Y.m.d'),
            date('23:i:s'),
            OrderService::$deliveryDeliver,
            $cds
        );

        //$ss = $navs->initSqlConn();
        //$querys=$ss->query("SELECT TOP 10 * FROM ".$navs->getMessagesTable());
        //var_dump($ss->fetchArray($querys));
        //die('E');
        return new Response("\n\n<br><br>THIS IS THE END");
    }

    public function invoiceAction()
    {
        $order = $this->get('food.order')->getOrderById(985);
        return $this->render(
            'FoodOrderBundle:Default:invoice.html.twig',
            array('order' => $order)
        );
    }

    public function pdfAction()
    {
        $os = $this->get('food.order');
        $is = $this->get('food.invoice');

        $order = $os->getOrderById(985);

        $path = $is->generateUserInvoice($order);

        return new Response(
            'Path: '.$path
        );
    }

    public function putOrderAction()
    {
        $order = $this->get('doctrine')->getRepository('FoodOrderBundle:Order')->find(2008);
        $ns = $this->get('food.nav');
        $ns->putTheOrderToTheNAV($order);
        return new Response('THE END');
    }
}