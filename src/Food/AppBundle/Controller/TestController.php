<?php

namespace Food\AppBundle\Controller;

use Food\AppBundle\Entity\MarketingUser;
use Food\OrderBundle\Service\OrderService;
use Food\OrderBundle\Service\PaySera;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
            'order_hash' => 'bbca285316560602a55bc8f5766085fd',
            'adresas' => 'Laisves 77c-58',
            'pristatymo_data' => 'Vakar',
        );

        $ml->setVariables( $variables )->setRecipient( 'karolis.m@foodout.lt', 'Sample Client')->setId( 30009269 )->send();
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
        $link = mssql_pconnect('213.197.176.247:5566', 'fo_order', 'peH=waGe?zoOs69');
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

    public function putOrderAction($id)
    {
        $order = $this->get('doctrine')->getRepository('FoodOrderBundle:Order')->find($id);
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

    public function listOrders2Action() {
        $ns = $this->get('food.nav');
        $query = "SELECT                 dOrder.[Order No_] As [OrderNo],                 dOrder.[Phone No_],                 dOrder.[Date Created],                 dOrder.[Time Created],                 dOrder.[Order Date],                 dOrder.[Contact Pickup Time],                 dOrder.[Driver ID],                 dOrder.[Address],                 dOrder.[City],                 dOrder.[Directions],                 dOrder.[Tender Type],                 dOrder.[Restaurant No_],                 dOrder.[Sales Type],                 dOrder.[Chain],                 dOrder.[Contact No_],                 dOrder.[Amount Incl_ VAT],                 pTrans.[VAT %],                 pTrans.[Amount] AS DeliveryAmount,                 (                     SELECT                     SUM([Amount])                     FROM [skamb_centras].[dbo].[LV Call Center$POS Trans_ Line] pSumTrans                     WHERE                         pSumTrans.[Receipt No_] = dOrder.[Order No_]                         AND pSumTrans.[Deleted] = 0                         AND pSumTrans.[Entry Status] = 0                 ) AS OrderSum,                 (                  SELECT TOP 1                     oStat.[Status]                  FROM [skamb_centras].[dbo].[LV Call Center$Delivery order status] oStat                  WHERE                     [ORDER No_] = dOrder.[Order No_]                  ORDER BY [TIME] DESC                  ) AS OrderStatus,                  cCustomer.[Name] AS CustomerName,                  cCustomer.[Address] AS CustomerAddress,                  cCustomer.[City] AS CustomerCity,                  cCustomer.[VAT Registration No_] AS CustomerVatNo,                  cCustomer.[E-mail] AS CustomerEmail,                  cCustomer.[Registration No_] AS CustomerRegNo             FROM [skamb_centras].[dbo].[LV Call Center$Delivery Order] dOrder             LEFT JOIN [skamb_centras].[dbo].[LV Call Center$POS Trans_ Line] pTrans ON pTrans.[Receipt No_] = dOrder.[Order No_]             LEFT JOIN [skamb_centras].[dbo].[LV Call Center$Contract] cContract ON cContract.[Contract Register No_] = dOrder.[Contract Register No_]             LEFT JOIN [skamb_centras].[dbo].[LV Call Center$Customer] cCustomer ON cCustomer.[No_] = cContract.[Customer No_]             WHERE                 dOrder.[Date Created] >= '2016-09-23'                 AND dOrder.[Time Created] >= '1754-01-01 14:20:02'                 AND dOrder.[Delivery Region] IN ('Vilnius', 'Kaunas', 'Klaipeda','Ryga')                 AND dOrder.[FoodOut Order] != 1                 AND pTrans.[Number] IN ('ZRAW0009996', 'ZRAW0010001', 'ZRAW0010002', 'ZRAW0010190', 'ZRAW0010255')                 AND dOrder.[Replication Counter] > 0             ORDER BY                 dOrder.[Date Created] ASC,                 dOrder.[Time Created] ASC";
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

    public function zonesAction($id)
    {
        $placePoint = $this->container->get('doctrine')->getRepository('FoodDishesBundle:PlacePoint')->find($id);
        $zones = $placePoint->getZones();
        return $this->render(
            'FoodAppBundle:Test:zones.html.twig',
            array(
                'placepoint'=>$placePoint,
                'zones' => $zones
            )
        );
    }

    public function devzonesAction($id)
    {
        $place = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find($id);
        $color = array(
            '#ff0000',
            '#00ff00',
            '#0000ff',
            '#ffcc00',
            '#008080',
            '#800080',
            '#800000'
        );
        $points = $place->getPoints();
        $zerZones = array();
        $pointReturn = array();
        foreach ($points as $key=>$point) {
            $zz = $point->getZones();
            $akey = md5($key);
            $pointReturn[$akey]['lat'] = $point->getLat();
            $pointReturn[$akey]['lon'] = $point->getLon();
            $pointReturn[$akey]['zones'] = array();
            $pointReturn[$akey]['color'] = $color[$key];
            $pointReturn[$akey]['address'] = $point->getAddress();
            $pointReturn[$akey]['zones'][] = array("distance"=>0.1);
            foreach ($zz as $k2 => $z) {
                if ($z->getActive()) {
                    $pointReturn[$akey]['zones'][] = array(
                        'distance' => $z->getDistance()
                    );
                }
            }
        }
        return $this->render(
            'FoodAppBundle:Test:devzones.html.twig',
            array(
                'points'=> $pointReturn
            )
        );
    }

    public function betaCodeAction()
    {
        echo $this->get('food.order')->getBetaCode();
        return new Response();
    }

    public function gameAction(Request $request)
    {
        $data = array(
            'showSuccess' => false,
            'showError' => false,
        );

        $participant = new MarketingUser();
        $participant->setCreatedAt(new \DateTime("now"));

        $form = $this->createFormBuilder($participant)
            ->add('firstName', 'text')
            ->add('lastName', 'text')
            ->add('city', 'text')
            ->add('birthDate', 'date')
            ->add('phone', 'text', array('attr' => array('placeholder' => '370XXXXXXX')))
            ->add('email', 'text')
            ->add('save', 'submit', array('label' => 'food.game.register'))
            ->getForm();


        $form->handleRequest($request);

        if ($form->isValid()) {
            // Do the save
            $em = $this->container->get('doctrine')->getManager();

            $em->persist($participant);
            $em->flush();

            $data['showSuccess'] = true;
        } else if (!$request->isMethod('POST')) {
            $data['showError'] = true;
        }

        $data['form'] = $form->createView();

        return $this->render(
            '@FoodApp/Default/game.html.twig',
            $data
        );
    }

    public function timeParseAction()
    {
        echo "=== Time parse test === <br><br>";

        $miscUtil = $this->get('food.app.utils.misc');

        $ver1 = "1 val.";
        $ver2 = "45 min.";
        $ver3 = "1 val";
        $ver4 = "59 min";
        $ver5 = "1.5 val";
        $ver6 = "1,5 val";
        $ver7 = "90-120 min.";

        echo sprintf(
            'Text: "%s" - Result in minutes %d<br>',
            $ver1,
            $miscUtil->parseTimeToMinutes($ver1)
        );
        echo sprintf(
            'Text: "%s" - Result in minutes %d<br>',
            $ver2,
            $miscUtil->parseTimeToMinutes($ver2)
        );
        echo sprintf(
            'Text: "%s" - Result in minutes %d<br>',
            $ver3,
            $miscUtil->parseTimeToMinutes($ver3)
        );
        echo sprintf(
            'Text: "%s" - Result in minutes %d<br>',
            $ver4,
            $miscUtil->parseTimeToMinutes($ver4)
        );
        echo sprintf(
            'Text: "%s" - Result in minutes %s<br>',
            $ver5,
            $miscUtil->parseTimeToMinutes($ver5)
        );
        echo sprintf(
            'Text: "%s" - Result in minutes %d<br>',
            $ver6,
            $miscUtil->parseTimeToMinutes($ver6)
        );
        echo sprintf(
            'Text: "%s" - Result in minutes %d<br>',
            $ver7,
            $miscUtil->parseTimeToMinutes($ver7)
        );

        return new Response();
    }

    public function magicReportForDaugisAction()
    {
        $con = $this->container->get('doctrine')->getConnection();
        $activeMembers1sql = "SELECT DISTINCT SUBSTRING(order_date, 1, 7) as date, COUNT(DISTINCT user_id) as count FROM orders WHERE order_date > '2015-01-01 00:00:00' GROUP BY 1";
        $stmt = $con->prepare($activeMembers1sql);
        $stmt->execute();
        $activeMembers1 = $stmt->fetchAll();
        $activeMembersData1 = array();
        foreach ($activeMembers1 as $row) {
            $activeMembersData1[$row['date']] = $row['count'];
        }

        $activeMembers2sql = "SELECT DISTINCT SUBSTRING(order_date, 1, 7) as date, user_id, COUNT(*) as cnt FROM orders WHERE order_date > '2015-01-01 00:00:00' GROUP BY 1,2 HAVING COUNT(*) > 1";
        $stmt = $con->prepare($activeMembers2sql);
        $stmt->execute();
        $activeMembers2 = $stmt->fetchAll();
        $activeMembersData2 = array();
        foreach ($activeMembers2 as $row) {
            if (empty($activeMembersData2[$row['date']])) {
                $activeMembersData2[$row['date']] = 0;
            }
            $activeMembersData2[$row['date']]++;
        }

        $newPlacePointsSql = "SELECT DISTINCT SUBSTRING(created_at, 1, 7) as date, COUNT(*) as count FROM place_point WHERE created_at > '2015-01-01 00:00:00' GROUP BY 1";
        $stmt = $con->prepare($newPlacePointsSql);
        $stmt->execute();
        $newPlacePoints = $stmt->fetchAll();
        $newPlacePointsData = array();
        foreach ($newPlacePoints as $row) {
            $newPlacePointsData[$row['date']] = $row['count'];
        }

        return $this->render(
            'FoodAppBundle:Test:daugis_report.html.twig',
            array(
                'activeMembers1' => $activeMembersData1,
                'activeMembers2' => $activeMembersData2,
                'newPlacePoints' => $newPlacePointsData,
                'keys' => array_keys($activeMembersData1)
            )
        );
    }
}
