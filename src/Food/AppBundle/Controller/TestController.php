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

        $order = $os->getOrderById(5541);

        $path = $is->generateUserInvoice($order);

        return new Response(
            'Path: '.$path
        );
    }

    public function putOrderAction()
    {
        $order = $this->get('doctrine')->getRepository('FoodOrderBundle:Order')->find(2020);
        $ns = $this->get('food.nav');
        $ns->putTheOrderToTheNAV($order);

        $order = $this->get('doctrine')->getRepository('FoodOrderBundle:Order')->find(2023);
        $ns = $this->get('food.nav');
        $ns->putTheOrderToTheNAV($order);
        return new Response('THE END');
    }

    public function migrateCiliAction()
    {
        $oldPlaceId = 63;
        $newPlaceId = 85;
        //echo "<pre>";
        $query = "SELECT o.id as oid, n.id as nid
          FROM
            food_category o, food_category n
            WHERE
            o.place_id = ".$oldPlaceId." AND n.place_id = ".$newPlaceId."
            AND o.lineup = n.lineup
            AND o.name = n.name
        ";
        $adp = $this->container->get('doctrine')->getManager()->getConnection();
        $stmt = $adp->prepare($query);
        $stmt->execute();
        $categoryList = $stmt->fetchAll();
        $mapCatList = array();
        foreach ($categoryList as $catRow) {
            $mapCatList[$catRow['oid']] = $catRow['nid'];
        }
        var_dump($mapCatList);

        $query = "SELECT o.id as oid, n.id as nid
          FROM
            dish_unit o, dish_unit n
            WHERE
            o.place = ".$oldPlaceId." AND n.place = ".$newPlaceId."
            AND o.created_at = n.created_at
            AND o.name = n.name
        ";
        $adp = $this->container->get('doctrine')->getManager()->getConnection();
        $stmt = $adp->prepare($query);
        $stmt->execute();
        $dishUnit = $stmt->fetchAll();
        $mapDishUnitList = array();
        foreach ($dishUnit as $catRow) {
            $mapDishUnitList[$catRow['oid']] = $catRow['nid'];
        }

        $query = "SELECT o.id as oid, n.id as nid, o.name, n.name, o.code, n.code FROM `dish_option` o, `dish_option` n  WHERE
            o.place_id = ".$oldPlaceId." AND n.place_id = ".$newPlaceId."
            AND
            o.name = n.name AND
            (IF(o.code IS NULL, n.code IS NULL, o.code=n.code)) AND
            (IF(o.sub_code IS NULL, n.sub_code IS NULL, o.sub_code=n.sub_code))
            AND
            o.created_at = n.created_at
        ";
        $adp = $this->container->get('doctrine')->getManager()->getConnection();
        $stmt = $adp->prepare($query);
        $stmt->execute();
        $dishOptions = $stmt->fetchAll();
        $dishOptionMap = array();
        foreach ($dishOptions as $catRow) {
            $dishOptionMap[$catRow['oid']] = $catRow['nid'];
        }


        $query = "SELECT * FROM dish WHERE place_id = ".$oldPlaceId;
        $adp = $this->container->get('doctrine')->getManager()->getConnection();
        $stmt = $adp->prepare($query);
        $stmt->execute();
        $dishes = $stmt->fetchAll();
        foreach ($dishes as $dish) {
            $query = "INSERT INTO `dish`
              (`id`, `place_id`, `created_by`, `edited_by`, `deleted_by`, `name`, `description`, `created_at`, `deleted_at`, `edited_at`, `recomended`, `photo`, `active`, `time_from`, `time_to`, `discount_prices_enabled`)
              VALUES
              (null,
              ".$newPlaceId.",
              ".$dish['created_by'].",
              ".(empty($dish['edited_by']) ? 'null': $dish['edited_by']).",
              ".(empty($dish['deleted_by']) ? 'null': $dish['deleted_by']).",
              '".addslashes($dish['name'])."',
              '".addslashes($dish['description'])."',
              '".$dish['created_at']."',
              ".(empty($dish['deleted_at']) ? "null": "'".$dish['deleted_at']."'").",
              ".(empty($dish['edited_at']) ? "null": "'".$dish['edited_at']."'").",
              '".$dish['recomended']."',
              '".$dish['photo']."',
              '".$dish['active']."',
              '".(empty($dish['time_from']) ? $dish['time_from']:'')."',
              '".(empty($dish['time_to']) ? $dish['time_to']:'')."',
              ".(empty($dish['discount_prices_enabled']) ? 'null':'1')."
              );";

            $stmt = $adp->prepare($query);
            $stmt->execute();
            $lastId = $adp->lastInsertId();
            $query2 = "SELECT * FROM dish_size WHERE dish_id=".$dish['id'];

            $stmt = $adp->prepare($query2);
            $stmt->execute();
            $dishSize = $stmt->fetch();

            $genQuery = "INSERT INTO `dish_size` (`id`, `dish_id`, `unit_id`, `created_by`, `edited_by`, `deleted_by`, `code`, `price`, `created_at`, `edited_at`, `deleted_at`, `discount_price`)
              VALUES
              (
                null,
                ".$lastId.",
                ".$mapDishUnitList[$dishSize['unit_id']].",
                NULL,
                NULL,
                NULL,
                '".$dishSize['code']."',
                '".$dishSize['price']."',
                '".$dishSize['created_at']."',
                ".(empty($dishSize['edited_at']) ? "null": "'".$dishSize['edited_at']."'").",
                ".(empty($dishSize['deleted_at']) ? "null": "'".$dishSize['deleted_at']."'").",
                '".$dishSize['discount_price']."'
              );";

            $stmt = $adp->prepare($genQuery);
            $stmt->execute();


            $query3 = "SELECT * FROM food_category_dish_map WHERE dish_id=".$dish['id'];
            $stmt = $adp->prepare($query3);
            $stmt->execute();
            $categories = $stmt->fetchAll();
            foreach ($categories as $cat) {
                $queryCats = "INSERT INTO food_category_dish_map VALUES(".$lastId.", ".$mapCatList[$cat['foodcategory_id']].");";
                $stmt = $adp->prepare($queryCats);
                $stmt->execute();
            }

            $query4 = "SELECT * FROM dish_option_map WHERE dish_id=".$dish['id'];
            $stmt = $adp->prepare($query4);
            $stmt->execute();
            $categories = $stmt->fetchAll();
            foreach ($categories as $cat) {
                $queryOpts = "INSERT INTO dish_option_map VALUES(".$lastId.", ".$dishOptionMap[$cat['dishoption_id']].");";
                $stmt = $adp->prepare($queryOpts);
                $stmt->execute();
            }
            //$dishOptionMap
        }

        die('KEBAS');
    }

    public function listOrdersAction() {
        $ns = $this->get('food.nav');
        $query = 'SELECT TOP 20 * FROM [skamb_centras].[dbo].[Čilija Skambučių Centras$Web ORDER Header] ORDER BY [Order No_] DESC';
        $rez = $ns->initSqlConn()->query($query);
        echo "<pre>";
        while ($rowRez = $this->get('food.mssql')->fetchArray($rez)) {
            var_dump($rowRez);
        }
        die();
    }

    public function migratorAction($date)
    {
        $select = "SELECT * FROM orders WHERE order_date LIKE '$date%' AND order_status='completed' AND payment_status='complete' AND sf_series IS NULL AND sf_number IS NULL";
        $adp = $this->get('doctrine')->getConnection();
        $stmt = $adp->prepare($select);
        $stmt->execute();
        $all = $stmt->fetchAll();
        $os = $this->get('food.order');
        foreach ($all as $row) {
            $ent = $this->get('doctrine')->getRepository('FoodOrderBundle:Order')->find($row['id']);
            echo $row['id']."<br>";
            if ($ent) {
                if ($ent->getPlace()->getSendInvoice()
                    && !$ent->getPlacePointSelfDelivery()
                    && $ent->getDeliveryType() == OrderService::$deliveryDeliver) {
                    $os->setOrder($ent);
                        $os->setInvoiceDataForOrder();
                        $this->get('food.invoice')->addInvoiceToSend($ent);
                    $os->saveOrder();
                }
            }
        }



        die($date);
    }
}