<?php

namespace Food\OrderBundle\Service;

use Food\AppBundle\Entity\Driver;
use Food\CartBundle\Entity\Cart;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderDetails;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\OrderBundle\Common;
use Food\OrderBundle\Service\NavService\OrderDataForNavDecorator;
use Food\OrderBundle\Service\Events\SoapFaultEvent;

class NavService extends ContainerAware
{
    use OrderDataForNavDecorator;

    /**
     * Modifajeris. NELIESTI. Patys galesite tada rankutemis issirankioti is Cili DB savo uzsakymus.
     *
     * @var int
     */
    private $_orderIdModifier = 2000000000;
    /**
     * @var Resource
     */
    private $conn = null;


    //private $headerTable = '[prototipas6].[dbo].[PROTOTIPAS Skambuciu Centras$Web ORDER Header]';

    //private $lineTable = '[prototipas6].[dbo].[PROTOTIPAS Skambuciu Centras$Web ORDER Lines]';

    //private $orderTable = '[prototipas6].[dbo].[PROTOTIPAS$FoodOut Order]';

    //private $messagesTable = '[prototipas6].[dbo].[PROTOTIPAS Skambuciu Centras$Web Order Messages]';

//    private $deliveryOrderTable = '[prototipas6].[dbo].[PROTOTIPAS Skambuciu Centras$Delivery Order]';
//
//    private $posTransactionLinesTable = '[prototipas6].[dbo].[PROTOTIPAS Skambuciu Centras$POS Trans_ Line]';

//    private $deliveryOrderStatusTable = '[prototipas6].[dbo].[PROTOTIPAS Skambuciu Centras$Delivery order status]';

    private $headerTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Web ORDER Header]';

    private $lineTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Web ORDER Lines]';

    private $orderTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$FoodOut Order]';

    private $messagesTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Web Order Messages]';

    private $itemsTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Item]';

    private $deliveryOrderTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Delivery Order]';

    private $posTransactionLinesTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$POS Trans_ Line]';

    private $deliveryOrderStatusTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Delivery order status]';
    
    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private function getContainer()
    {
        return $this->container;
    }

    /**
     * @return string
     */
    public function getHeaderTable()
    {
        return $this->headerTable;
    }

    /**
     * @return string
     */
    public function getLineTable()
    {
        return $this->lineTable;
    }

    /**
     * @return string
     */
    public function getOrderTable()
    {
        return $this->orderTable;
    }

    /**
     * @return string
     */
    public function getMessagesTable()
    {
        return $this->messagesTable;
    }

    /**
     * @return string
     */
    public function getItemsTable()
    {
        return $this->itemsTable;
    }

    /**
     * @return string
     */
    public function getDeliveryOrderTable()
    {
        return $this->deliveryOrderTable;
    }

    /**
     * @return string
     */
    public function getPosTransactionLinesTable()
    {
        return $this->posTransactionLinesTable;
    }

    /**
     * @return string
     */
    public function getDeliveryOrderStatusTable()
    {
        return $this->deliveryOrderStatusTable;
    }

    /**
     * @return false|resource
     */
    public function getConnection()
    {
        if ($this->conn == null) {
            $serverName = "213.190.40.38, 5566"; //serverName\instanceName, portNumber (default is 1433)
            //$connectionInfo = array( "Database"=>"prototipas6", "UID"=>"fo_order", "PWD"=>"peH=waGe?zoOs69");
            //$connectionInfo = array( "Database"=>"prototipas6", "UID"=>"nas", "PWD"=>"c1l1j@");
            $connectionInfo = array( "Database"=>"prototipas6", "UID"=>"CILIJA\Neotest", "PWD"=>"NewNeo@123");
            //$connectionInfo = array( "Database"=>"skamb_centras", "UID"=>"fo_order", "PWD"=>"peH=waGe?zoOs69");
            $this->conn = sqlsrv_connect( $serverName, $connectionInfo);

            if( $this->conn === false ) {
                die( print_r( sqlsrv_errors(), true));
            }
        }
        return $this->conn;
    }

    /**
     * @return SqlConnectorService
     */
    public function initSqlConn() {
        $sqlSS = $this->container->get('food.mssql');

        $isConnected = $sqlSS->init(
            '213.190.40.38',
            5566,
            'skamb_centras',
            'fo_order',
            'peH=waGe?zoOs69'
        );

        return $isConnected ? $sqlSS : $isConnected;
    }

    /**
     * @return int
     */
    public function getNavIdModifier()
    {
        return $this->_orderIdModifier;
    }

    public function initTestSqlConn() {
        $sqlSS = $this->container->get('food.mssql');

        $isConnected = $sqlSS->init(
            '213.190.40.38',
            5566,
            'prototipas6',
            'Neotest',
            'NewNeo@123'
        );

        return $isConnected ? $sqlSS : $isConnected;
    }

    public function getLastOrders()
    {
        $sqlSS = $this->initSqlConn();
        $rez = sqlsrv_query ( $this->getConnection() , 'SELECT TOP 1 * FROM '.iconv('utf-8', 'cp1257',$this->getHeaderTable()).' ORDER BY timestamp DESC');

        if( $rez === false) {
            die( print_r( sqlsrv_errors(), true) );
        }
        echo '<pre>';
        while ($rowRez = sqlsrv_fetch_array($rez, SQLSRV_FETCH_ASSOC)) {
            //echo $rowRez['Order No'];
            print_r($rowRez);
            echo "\n-----------\n";
        }
        echo '</pre>';

        $rez = sqlsrv_query ( $this->getConnection() , 'SELECT TOP 5 * FROM '.iconv('utf-8', 'cp1257',$this->getLineTable()).' ORDER BY timestamp DESC');

        if( $rez === false) {
            die( print_r( sqlsrv_errors(), true) );
        }
        echo '<pre>';
        while ($rowRez = sqlsrv_fetch_array($rez, SQLSRV_FETCH_ASSOC)) {
            //echo $rowRez['Order No'];
            print_r($rowRez);
            echo "\n-----------\n";
        }
        echo '</pre>';
    }

    /**
     * I am old and unused
     */
    public function testInsertOrder()
    {
        $dataToPut = array(
            'Order No_' => '10004',
            'Phone' => '37061544121',
            'ZipCode' => '03115',
            'City' => '',
            'Street' => '',
            'Street No_' => '',
            'Floor' => '',
            'Grid' => '',
            'Chain' => 'PICA',
            'Name' => 'FoodOut_dev',
            'Delivery Type' => '1',
            'Restaurant No_' => '64',
            'Order Date' => date("Y-m-d H:i:s"),
            //'Order Time' => '1754-01-01 '.date("H:i:s", strtotime('-3 hours')),
            //'Takeout Time' => date("Y-m-d H:i:s", (strtotime('+20 minutes') - (3600 * 3))),
            'Order Time' => '1754-01-01 '.date("H:i:s"),
            'Takeout Time' => date("Y-m-d H:i:s", (strtotime('+20 minutes'))),
            'Directions' => 'Negaminti',
            'Discount Card No_' => '',
            'Order Status' => '',
            'Delivery Order No_' => '',
            'Error Description' => '',
            'Flat No_' => '',
            'Entrance Code' => '',
            'Region Code' => '',
            'Delivery Status' => '',
            'In Use By User' => '',
            'Loyalty Card No_' => '',
            'Order with Alcohol' => '0'
        );
        $queryPart = $this->generateQueryPart($dataToPut);
        var_dump('INSERT INTO '.$this->getHeaderTable().' ('.$queryPart['keys'].') VALUES('.$queryPart['values'].')');
        $rez = sqlsrv_query ( $this->getConnection() , 'INSERT INTO '.$this->getHeaderTable().' ('.$queryPart['keys'].') VALUES('.$queryPart['values'].')');

        if( $rez === false) {
            echo "<pre>";
            die( print_r( sqlsrv_errors(), true) );
        }

        var_dump($queryPart);
    }

    private function generateQueryPart($data)
    {
        $arrayKeys = array_keys($data);
        $values = array_values($data);
        return array(
            'keys' => '['.implode('],[', $arrayKeys).']',
            'values' => "'".implode("','", $values)."'",
        );
    }

    private function generateQueryPartNoQuotes($data)
    {
        $arrayKeys = array_keys($data);
        $values = array_values($data);
        return array(
            'keys' => '['.implode('],[', $arrayKeys).']',
            'values' => implode(',', $values)
        );
    }

    public function putTheOrderToTheNAV(Order $order)
    {
        $orderNewId = $this->getNavOrderId($order);

        $orderRow = null;
        $street = "";
        $houseNr = "";
        $flatNr = "";
        if ($order->getAddressId()) {
            $target = $order->getAddressId()->getAddress();
            preg_match('/(([0-9]{1,3})[-|\s]{0,4}([0-9]{0,3}))$/i', $target, $errz);
            $street = trim(str_replace($errz[0], '', $target));
            $houseNr = (!empty($errz[2]) ? $errz[2] : '');
            $flatNr = (!empty($errz[3]) ? $errz[3] : '');
            /*
            $orderRow = $this->container->get('doctrine')->getRepository('FoodAppBundle:Streets')->findOneBy(
                array(
                    'name' => $street,
                    'numberFrom' => $houseNr,
                    'deliveryRegion' => $order->getAddressId()->getCity()
                )
            );
            */
            //$this->container->get('doctrine')->getManager()->refresh($orderRow);
        }


        $city = $order->getPlacePoint()->getCity();
        $city = str_replace("ė", "e", $city);
        $region = mb_strtoupper($city);

        $orderDate = $order->getOrderDate();
        $orderDate->add(new \DateInterval('P0DT0H'));
        $deliveryDate = $order->getDeliveryTime();
        $deliveryDate->sub(new \DateInterval('P0DT2H'));

        $comment = $order->getComment();

        if ($order->getPaymentMethod() == "local.card") {
            $comment.=". Mokesiu kortele";
        } elseif ($order->getPaymentMethod() == "local") {
            $comment.=". Mokesiu grynais";
        } else {

        }

        $dataToPut = array(
            'Order No_' => $orderNewId,
            'Phone' => str_replace('370', '8', $order->getUser()->getPhone()),
            'ZipCode' => '', // ($order->getDeliveryType() == OrderService::$deliveryDeliver ? $orderRow->getZipCode() : ''),
            'City' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? $order->getAddressId()->getCity() : ''),
            'Street' => $street, //($order->getDeliveryType() == OrderService::$deliveryDeliver ? $orderRow->getStreetName(): ''),
            'Street No_' => $houseNr, //($order->getDeliveryType() == OrderService::$deliveryDeliver ? $orderRow->getNumberFrom(): ''),
            'Floor' => '',
            'Grid' => '', // ($order->getDeliveryType() == OrderService::$deliveryDeliver ? $orderRow->getGrid(): ''),
            'Chain' => $order->getPlace()->getChain(),
            'Name' => 'FO:'.$order->getUser()->getFirstname(),
            'Delivery Type' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? 1 : 4),
            //'Restaurant No_' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? '':  $order->getPlacePoint()->getInternalCode()),
            'Restaurant No_' => $order->getPlacePoint()->getInternalCode(),
            'Order Date' => $orderDate->format("Y-m-d"),
            'Order Time' => '1754-01-01 '.$orderDate->format("H:i:s"),
            'Takeout Time' => $deliveryDate->format("Y-m-d H:i:s"),
            'Directions' => $comment,
            'Discount Card No_' => '',
            'Order Status' => 4,
            'Delivery Order No_' => '',
            'Error Description' => '',
            'Flat No_' => $flatNr, //($order->getDeliveryType() == OrderService::$deliveryDeliver ? $flatNr: ''),
            'Entrance Code' => '',
            'Region Code' => $region, //$order->getDeliveryType() == OrderService::$deliveryDeliver ? $orderRow->getDeliveryRegion() : ''),
            'Delivery Status' => 12,
            'In Use By User' => '',
            'Loyalty Card No_' => '',
            'Order with Alcohol' => '0'
        );
        $queryPart = $this->generateQueryPart($dataToPut);
        $query = 'INSERT INTO '.$this->getHeaderTable().' ('.$queryPart['keys'].') VALUES('.$queryPart['values'].')';
        @mail("paulius@foodout.lt", '#'.($orderNewId - $this->_orderIdModifier).' [SQL Line Query]-#HEADER', $query, "FROM: info@foodout.lt");
        $sqlSS = $this->initSqlConn()->query($query);

        $this->_processLines($order, $orderNewId);
    }

    private function _processLines(Order $order, $orderNewId)
    {
        $theKey = 1;
        $this->container->get('doctrine')->getManager()->refresh($order);
        foreach ($order->getDetails() as $key=>$detail) {
            $theKey = $this->_processLine($detail, $orderNewId, $theKey);
            $theKey = $theKey + 1;
        }

        if ($order->getPaymentMethod() != "local.card" && $order->getPaymentMethod() != "local") {
            $this->_processPayedLineDelivery($order, $orderNewId, $theKey);
            $theKey = $theKey + 1;
        }

        if ($order->getDeliveryType() == OrderService::$deliveryDeliver) {
            $this->_processLineDelivery($order, $orderNewId, $theKey);
        }
    }

    /**
     * @param $orderNewId
     * @param $key
     *
     */
    private function _processPayedLineDelivery(Order $order, $orderNewId, $key)
    {
        $dataToPut = array(
            'Order No_' => $orderNewId,
            'Line No_' => $key,
            'Entry Type' => 3,
            'No_' => "'B_SWED'",
            'Description' => "''",
            'Quantity' => 1,
            'Price' => $order->getTotal(),
            'Parent Line' => 0,
            'Amount' => 0,
            'Discount Amount' => 0,
            'Payment' => 0,
            'Value' => "''"
        );

        $queryPart = $this->generateQueryPartNoQuotes($dataToPut);

        $query = 'INSERT INTO '.$this->getLineTable().' ('.$queryPart['keys'].') VALUES('.$queryPart['values'].')';
        @mail("paulius@foodout.lt", '#'.($orderNewId - $this->_orderIdModifier).' [SQL Line Query]-#PREPAID', $query, "FROM: info@foodout.lt");
        $sqlSS = $this->initSqlConn()->query($query);
    }

    /**
     * @param $orderNewId
     * @param $key
     *
     * @todo - kolkas hardcoded delivery atstumas
     */
    private function _processLineDelivery(Order $order, $orderNewId, $key)
    {
        $devPrice = 1.5;
        $couponCode = $order->getCouponCode();
        if (!empty($couponCode) && strlen($couponCode) > 1) {
            $devPrice = $order->getDeliveryPrice();
        }

        $dataToPut = array(
            'Order No_' => $orderNewId,
            'Line No_' => $key,
            'Entry Type' => 0,
            'No_' => "'ZRAW0009996'",
            'Description' => "''",
            'Quantity' => 1,
            'Price' => $devPrice,
            'Parent Line' => 0, // @todo kaip optionsai sudedami. ar prie pirmines kainos ar ne
            'Amount' => $devPrice,
            'Discount Amount' => 0,
            'Payment' => $devPrice,
            'Value' => "''"
        );

        $queryPart = $this->generateQueryPartNoQuotes($dataToPut);

        $query = 'INSERT INTO '.$this->getLineTable().' ('.$queryPart['keys'].') VALUES('.$queryPart['values'].')';
        @mail("paulius@foodout.lt", '#'.($orderNewId - $this->_orderIdModifier).' [SQL Line Query]-#DELIVERY', $query, "FROM: info@foodout.lt");
        $sqlSS = $this->initSqlConn()->query($query);
    }

    private function _processLine(OrderDetails $detail, $orderNewId, $key)
    {
        $this->container->get('doctrine')->getManager()->refresh($detail);

        $desc = $detail->getDishName();
        $unitDesc = $detail->getDishUnitName();

        $desc = str_replace(array("'",'"', ',', '(', ')'), '', $desc);
        $desc = str_replace(array('ė', 'e', 'Ę','Ė'), 'e', $desc);
        $desc = str_replace(array('ą', 'Ą'), 'a', $desc);
        $desc = str_replace(array('č', 'Č'), 'c', $desc);
        $desc = str_replace(array('į', 'Į'), 'i', $desc);
        $desc = str_replace(array('š', 'Š'), 's', $desc);
        $desc = str_replace(array('ų','ū', 'Ų','Ū'), 'u', $desc);
        $desc = strtolower($desc);

        $unitDesc = str_replace(array("'",'"', ',', '(', ')'), '', $unitDesc);
        $unitDesc = str_replace(array('ė', 'e', 'Ę','Ė'), 'e', $unitDesc);
        $unitDesc = str_replace(array('ą', 'Ą'), 'a', $unitDesc);
        $unitDesc = str_replace(array('č', 'Č'), 'c', $unitDesc);
        $unitDesc = str_replace(array('į', 'Į'), 'i', $unitDesc);
        $unitDesc = str_replace(array('š', 'Š'), 's', $unitDesc);
        $unitDesc = str_replace(array('ų','ū', 'Ų','Ū'), 'u', $unitDesc);
        $unitDesc = strtolower($unitDesc);

        $desc = str_replace("makaronai", "makar", $desc);
        $desc = str_replace("lasisomis", "lasis", $desc);

        $desc = "'".substr($desc, 0, 29)." ".$unitDesc."'";
        $desc = str_replace(" pica", "", $desc);
        $desc = str_replace("Apkepti", "apk", $desc);
        $desc = str_replace("blyneliai", "blynel", $desc);
        $desc = str_replace(" Porcija", "", $desc);

        $code = $detail->getDishSizeCode();
        $optionIdUsed = -1;
        if (empty($code)) {
            $detailOptions = $detail->getOptions();
            if (!empty($detailOptions)) {
                if ($detailOptions[0] && $detailOptions[0]->getDishOptionId()->getInfocode()) {
                    $code = $detailOptions[1]->getDishOptionCode();
                    $optionIdUsed = 1;
                } else {
                    $code = $detailOptions[0]->getDishOptionCode();
                    $optionIdUsed = 0;
                }
            }
        }


        $data = $this->container->get('doctrine')->getRepository('FoodOrderBundle:NavItems')->find($code);
        if ($data) {
            $desc = "'".$data->getDescription()."'";
        }

        $priceForInsert = $detail->getPrice();
        $amountForInsert = $priceForInsert * $detail->getQuantity();
        $discountAmount = 0;
        $paymentAmount = $amountForInsert;

        /*
        if ($detail->getDishId()->getShowDiscount()) {
            $discountPrice = $detail->getPrice();
            $priceForInsert = $detail->getOrigPrice();
            $amountForInsert = $priceForInsert * $detail->getQuantity();
            $discountAmount = ($priceForInsert - $discountPrice) * $detail->getQuantity();
            $paymentAmount = $amountForInsert - $discountAmount;
        }
        */
        $desc = str_replace(array("'",'"', ',', '(', ')', '`'), '', $desc);
        $desc = "'".$desc."'";
        $dataToPut = array(
            'Order No_' => $orderNewId,
            'Line No_' => $key,
            'Entry Type' => 0,
            'No_' => "'".$code."'",
            'Description' => $desc,
            'Quantity' => $detail->getQuantity(),
            'Price' => $priceForInsert, //$detail->getPrice(), // @todo test the price. Kaip gula. Total ar ne.
            'Parent Line' => 0, // @todo kaip optionsai sudedami. ar prie pirmines kainos ar ne
            'Amount' => $amountForInsert, // $detail->getPrice() * $detail->getQuantity(),// @todo test the price. Kaip gula. Total ar ne.
            'Discount Amount' => "-".$discountAmount,
            'Payment' => $paymentAmount, //$detail->getPrice() * $detail->getQuantity(),
            'Value' => "''"
        );
        $queryPart = $this->generateQueryPartNoQuotes($dataToPut);
        $query = 'INSERT INTO '.$this->getLineTable().' ('.$queryPart['keys'].') VALUES('.$queryPart['values'].')';

        @mail("paulius@foodout.lt", '#'.($orderNewId - $this->_orderIdModifier).' [SQL Line Query]-#'.$key, $query, "FROM: info@foodout.lt");
        $sqlSS = $this->initSqlConn()->query($query);

        $okeyCounter = $key;
        foreach ($detail->getOptions() as $okey=>$opt) {
            if ($okey != $optionIdUsed) {
                $okeyCounter++;
                $code = $opt->getDishOptionCode();
                $desc = $opt->getDishOptionName();
                if ($opt->getDishOptionId()->getInfocode()) {
                    $desc = $opt->getDishOptionId()->getSubCode();
                }

                $dataToPut = array(
                    'Order No_' => $orderNewId,
                    'Line No_' => $okeyCounter,
                    'Entry Type' => 1,
                    'No_' => "'".$code."'",
                    'Description' => "'".$desc."'",
                    'Quantity' => 0,
                    'Price' => 0,
                    'Parent Line' => $key,
                    'Amount' => 0,
                    'Discount Amount' => 0,
                    'Payment' => 0,
                    'Value' => "''"
                );
                $queryPart = $this->generateQueryPartNoQuotes($dataToPut);
                $query = 'INSERT INTO '.$this->getLineTable().' ('.$queryPart['keys'].') VALUES('.$queryPart['values'].')';
                @mail("paulius@foodout.lt", '#'.($orderNewId - $this->_orderIdModifier).' [SQL Line Query SUBQ]#'.$key."-".$okey, $query, "FROM: info@foodout.lt");
                $sqlSS = $this->initSqlConn()->query($query);
            }
        }
        $key = $okeyCounter;
        return $key;
    }

    /**
     * @param Order $order
     * @return int
     */
    public function getNavOrderId(Order $order)
    {
        return $this->_orderIdModifier + (int)$order->getId();
    }

    /**
     * @param int $navId
     * @return int
     */
    public function getOrderIdFromNavId($navId)
    {
        return $navId - $this->_orderIdModifier;
    }

    public function getWSConnection()
    {

        $clientUrl = "http://213.190.40.38:7059/DynamicsNAV/WS/Codeunit/WEB_Service2?wsdl";
        // $clientUrl2 = "http://213.190.40.38:7059/DynamicsNAV/WS/PROTOTIPAS%20Skambuciu%20Centras/Codeunit/WEB_Service2";
        $clientUrl2 = "http://213.190.40.38:7055/DynamicsNAV/WS/Čilija%20Skambučių%20Centras/Codeunit/WEB_Service2";

        stream_wrapper_unregister('http');
        stream_wrapper_register('http', '\Food\OrderBundle\Common\FoNTLMStream') or die("Failed to register protocol");

        $url = $clientUrl2;
        //$options = array('trace'=>1, 'login' =>'CILIJA\fo_order', 'password' => 'peH=waGe?zoOs69');
        $options = array('trace'=>1, 'cache_wsdl' => WSDL_CACHE_NONE, 'login' =>'CILIJA\nas', 'password' => 'c1l1j@');
        $client = @new Common\FoNTLMSoapClient($url, $options);
        stream_wrapper_restore('http');
        return $client;
    }

    public function updatePricesNAV(Order $order)
    {
        if (!$order->getNavPriceUpdated()) {
            $orderId = $this->getNavOrderId($order);
            ob_start();
            $client = $this->getWSConnection();
            $return = $client->FoodOutUpdatePrices(array('pInt' =>(int)$orderId));
            ob_end_clean();
            $order->setNavPriceUpdated(true);
            $this->getContainer()->get('doctrine')->getManager()->merge($order);
            $this->getContainer()->get('doctrine')->getManager()->flush();
        } else {
            $return = true;
        }
        return $return;
    }

    public function processOrderNAV(Order $order)
    {
        $orderId = $this->getNavOrderId($order);

        $query = 'UPDATE '.$this->getHeaderTable().' SET [Order Status]=0, [Delivery Status]=0 WHERE [Order No_] = '.$orderId;

        $sqlSS = $this->initSqlConn()->query($query);

        if (!$order->getNavPorcessedOrder()) {
            ob_start();
            $client = $this->getWSConnection();
            $return = $client->FoodOutProcessOrder(array('pInt' =>(int)$orderId));
            ob_end_flush();
            $order->setNavPorcessedOrder(true);
            $this->getContainer()->get('doctrine')->getManager()->merge($order);
            $this->getContainer()->get('doctrine')->getManager()->flush();
        } else {
            $return = true;
        }
        return $return;
    }

    public function createInvoice(Order $order)
    {
        $response = new \StdClass();

        // we will need to connect to a web service
        $client = $this->getWSConnection();

        // before requesting a web service method we must fill some mandatory parameters
        $o = \Maybe($order);

        // some calculations beforehand
        $total = $o->getTotal()->val(0.0);
        $discountTotal = $o->getDiscountSum()->val(0.0);
        $deliveryTotal = $o->getPlace()->getDeliveryPrice()->val(0.0);
        $foodTotal = $total - $discountTotal - $deliveryTotal;

        // payment type and code preprocessing
        $driverId = $o->getDriver()->getId()->val('');

        if (empty($driverId) && $order->getNavDriverCode() != '') {
            $driverId = $order->getNavDriverCode();
        }

        $paymentType = $o->getPaymentMethod()->val('');
        $paymentCode = $paymentType == 'local'
                       ? $driverId
                       : $this->convertPaymentType($paymentType);

        // main variable that holds parameters for a Soap call
        $params = ['InvoiceNo' => $o->getSfSeries()->val('') . $o->getSfNumber()->val(''),
                   'OrderID' => $o->getId()->val('0'),
                   'OrderDate' => $o->getOrderDate()->format('Y.m.d')->val('1754-01-01'),
                                  // ' ' .
                                  // $o->getOrderDate()->format('H:i:s')->val('00:00:00'),
                   'RestaurantID' => $o->getPlace()->getId()->val('0'),
                   'RestaurantName' => $o->getPlaceName()->val(''),
                   'DriverID' => $driverId,
                   'ClientName' => $o->getUser()->getFirstname()->val('') .
                                   ' ' .
                                   $o->getUser()->getLastname()->val(''),
                   'RegistrationNo' => $o->getCompanyCode()->val(''),
                   'VATRegistrationNo' => $o->getVATCode()->val(''),
                   'DeliveryAddress' => $o->getAddressId()->getAddress()->val(''),
                   'City' => $o->getPlacePointCity()->val(''),
                   'PaymentType' => $paymentType,
                   'PaymentCode' => $paymentCode,
                   'FoodAmount' => number_format($foodTotal, 2, '.', ''),
                   'AlcoholAmount' => number_format(0.0, 2, '.', ''),
                   'DeliveryAmount' => $o->getDeliveryType()->val('') == 'pickup'
                                       ? '0.00'
                                       : number_format($o->getPlace()->getDeliveryPrice()->val('0.0'), 2, '.', '')];

        // send a call to a web service, but beware of exceptions
        try {
            $response = $client->FoodOutCreateInvoice(['params' => $params]);

            $r = \Maybe($response);

            // correct logic is when $response->return_value === 0
            if (!($r->return_value->val('') === 0)) {
                throw new \SoapFault((string) $r->return_value->val(''),
                                     'Soap call "FoodOutCreateInvoice" didn\'t return 0.');
            }
        } catch (\SoapFault $e) {
            $event = new SoapFaultEvent($e);

            $this->getContainer()
                 ->get('event_dispatcher')
                 ->dispatch(SoapFaultEvent::SOAP_FAULT, $event);
        }

        $r = \Maybe($response);

        return $r->return_value->val('') === 0 ? true : false;
    }

    /**
     * @param String $phone
     * @param PlacePoint $restaurant
     * @param String $orderDate
     * @param String $orderTime
     * @param String $deliveryType
     * @param Cart[] $dishes
     * @return array
     */
    public function validateCartInNav($phone, $restaurant, $orderDate, $orderTime, $deliveryType, $dishes)
    {
        $rcCode = !empty($restaurant) ? $restaurant->getInternalCode() : '';

        $requestData = array(
            'Phone'=> str_replace("370", "8", $phone),
            'RestaurantNo' => $rcCode,
            'OrderDate' => str_replace("-", ".", $orderDate),
            'OrderTime' => $orderTime,
            'DeliveryType' => ($deliveryType == OrderService::$deliveryDeliver ? 1: 4)
        );

        $lineNo = 0;
        foreach ($dishes as $detailKey=>$cart) {
            $lineNo = $lineNo + 1;
            $code = $cart->getDishSizeId()->getCode();
            $disFromOptions = -1;
            if (empty($code)) {
                $detailOptions = $cart->getOptions();
                if (!empty($detailOptions)) {
                    if ($detailOptions[0]->getDishOptionId()->getInfocode()) {
                        $code = $detailOptions[1]->getDishOptionId()->getCode();
                        $disFromOptions = 1;
                    } else {
                        $code = $detailOptions[0]->getDishOptionId()->getCode();
                        $disFromOptions = 0;
                    }
                }
            }

            $lineMap = array();
            $requestData['Lines'][] = array('Line' => array(
                'LineNo' => $lineNo,
                'ParentLineNo' => 0,
                'EntryType' => 0,
                'ItemNo' => $code,
                'Description' => mb_substr($cart->getDishId()->getName(), 0, 30),
                'Quantity' => $cart->getQuantity(),
                'Price' => $cart->getDishSizeId()->getPrice(),
                'Amount' => $cart->getDishSizeId()->getPrice() * $cart->getQuantity()
            ));
            $lineMap[$lineNo] = array(
                'parent' => 0,
                'name' => $cart->getDishId()->getName()
            );

            $origLineNo = $lineNo;
            foreach ( $detailOptions = $cart->getOptions() as $optKey => $option) {
                if ($optKey == $disFromOptions) {
                    continue;
                } else {
                    $optionCode = $option->getDishOptionId()->getCode();
                    $description = "";
                    if($option->getDishOptionId()->getInfocode()) {
                        $optionCode = $option->getDishOptionId()->getCode();
                        $description = $option->getDishOptionId()->getSubCode();
                    } else {
                        $description = $option->getDishOptionId()->getName();
                    }
                    $lineNo = $lineNo + 1;

                    $lineMap[$lineNo] = array(
                        'parent' => $origLineNo,
                        'name' => $description
                    );

                    $requestData['Lines'][] = array('Line' => array(
                        'LineNo' => $lineNo,
                        'ParentLineNo' => $origLineNo,
                        'EntryType' => 1,
                        'ItemNo' => $optionCode,
                        'Description' => mb_substr($description, 0, 30),
                        'Quantity' => $cart->getQuantity(),
                        'Price' => $option->getDishOptionId()->getPrice(),
                        'Amount' => $option->getDishOptionId()->getPrice() * $cart->getQuantity()
                    ));

                }
            }
        }

        // this is more like anomaly than a rule
        if (empty($requestData['Lines'])) {
            $returner = [
                'valid' => false,
                'errcode' => [
                    'code' => 255,
                    'line' => __LINE__,
                    'msg' => 'No line items.',
                    'problem_dish' => ''
                ]
            ];

            return $returner;
        }

        ob_start();
        @mail("paulius@foodout.lt", "CILI NVB VALIDATE REQUEST", print_r($requestData, true), "FROM: info@foodout.lt");
        $response = $this->getWSConnection()->FoodOutValidateOrder(
                array(
                    'params' => $requestData,
                    'errors' => array()
                )
        );
        @mail("paulius@foodout.lt", "CILI NVB VALIDATE RESPONSE", print_r($response, true), "FROM: info@foodout.lt");
        ob_end_clean();

        $prbDish = "";
        if ($response->return_value == 2) {
            if ($lineMap[$response->errors->Error->SubCode]['parent'] == 0) {
                $prbDish = $lineMap[$response->errors->Error->SubCode]['name'];
            } else {
                $prbDish = $lineMap[$lineMap[$response->errors->Error->SubCode]['parent']]['name'];
            }
        }

        $returner = array(
            'valid' => ($response->return_value == 0 ? true: false),
            'errcode' => array(
                'code' => ($response->return_value != 0 ? $response->errors->Error->Code : ''),
                'line' => ($response->return_value != 0 ? $response->errors->Error->SubCode : ''),
                'msg' => ($response->return_value != 0 ? $response->errors->Error->Description : ''),
                'problem_dish' => $prbDish
            )
        );
        return $returner;
    }

    /**
     * @param Order[] $orders
     *
     * @return array
     */
    public function getRecentNavOrders($orders)
    {
        $orderIds = $this->getNavIdsFromOrders($orders);
        if (empty($orderIds)) {
            return array();
        }

        $query = sprintf(
            'SELECT woh.[Order No_], woh.[Order Status], woh.[Delivery Status], woh.[Delivery Order No_], dor.[Driver ID]
            FROM %s woh
            LEFT JOIN %s dor ON dor.[ORDER No_] = woh.[Delivery ORDER No_]
            WHERE
                [Order No_] IN (%s)
            ORDER BY [Order No_] DESC',
            $this->getHeaderTable(),
            $this->getDeliveryOrderTable(),
            implode(', ', $orderIds)
        );

        $result = $this->initSqlConn()->query($query);
        if( $result === false) {
            return array();
        }

        $return = array();
        while ($rowRez = $this->container->get('food.mssql')->fetchArray($result)) {
            $return[$this->getOrderIdFromNavId($rowRez['Order No_'])] = $rowRez;
        }

        return $return;
    }

    /**
     * @param Order[] $orders
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function getRecentNavOrderSums($orders)
    {
        $orderIds = $this->getNavIdsFromOrders($orders);

        $query = sprintf(
            '
            SELECT [Order No_], SUM(Amount) AS total
            FROM %s
            WHERE
              [Order No_] IN ( %s )
            GROUP BY [Order No_]
            ORDER BY [Order No_] DESC',
            $this->getLineTable(),
            implode(', ', $orderIds)
        );

        $result = $this->initSqlConn()->query($query);
        if( $result === false) {
            throw new \InvalidArgumentException('No wanted orders found in Nav. How is that even possible?');
        }

        $return = array();
        while ($rowRez = $this->container->get('food.mssql')->fetchArray($result)) {
            $return[$this->getOrderIdFromNavId($rowRez['Order No_'])] = $rowRez;
        }

        return $return;
    }

    /**
     * @param Order[] $orders
     * @return array
     */
    public function getImportedOrdersStatus($orders)
    {
        $orderIds = array();
        $orderIdMap = array();

        foreach($orders as $order) {
            if ($order->getOrderFromNav()) {
                $orderIds[] = $order->getNavDeliveryOrder();
                $orderIdMap[$order->getNavDeliveryOrder()] = $order->getId();
            }
        }

        if (empty($orderIds)) {
            return array();
        }

        $query = sprintf(
            'SELECT
                dOrder.[Order No_] As [OrderNo],
                dOrder.[Driver ID],
                (
                 SELECT TOP 1
                    oStat.[Status]
                 FROM %s oStat
                 WHERE
                    [ORDER No_] = dOrder.[Order No_]
                 ORDER BY [TIME] DESC
                 ) AS OrderStatus,
                 (
                    SELECT
                    SUM([Amount])
                    FROM %s pSumTrans
                    WHERE
                        pSumTrans.[Receipt No_] = dOrder.[Order No_]
                ) AS OrderSum
            FROM %s dOrder
            WHERE
                dOrder.[Order No_] IN (%s)',
            $this->getDeliveryOrderStatusTable(),
            $this->getPosTransactionLinesTable(),
            $this->getDeliveryOrderTable(),
            implode(', ', $orderIds)
        );

        $result = $this->initSqlConn()->query($query);
        if( $result === false) {
            return array();
        }

        $return = array();
        while ($rowRez = $this->container->get('food.mssql')->fetchArray($result)) {
            $return[$orderIdMap[$rowRez['OrderNo']]] = array(
                'Order No_' => $rowRez['OrderNo'],
                'Order Status' => null,
                'Delivery Status' => $rowRez['OrderStatus'],
                'Delivery Order No_' => $rowRez['OrderNo'],
                'Driver ID' => $rowRez['Driver ID'],
                'Total Sum' => $rowRez['OrderSum'],
            );
        }

        return $return;
    }

    /**
     * @param Order $order
     * @param array $navOrder
     */
    public function changeOrderStatusByNav($order, $navOrder)
    {
        $orderService = $this->container->get('food.order');
        $orderService->setOrder($order);
        $logger = $this->container->get('logger');
        /*
        Nav statusai:
        0-Naujas,
        1-Priimtas,
        2-Persiųstas,
        3-Pakeistas,
        4-Atspausdintas,
        5-Gaminamas,
        6-Pagamintas,
        7-Išvežtas,
        8-Pristatytas,
        9-Baigtas,
        10-Atšauktas,
        11-Grąžintas,
        12-Problemos uzsakymo perdavimo metu
         */
        switch ($navOrder['Delivery Status'])
        {
            case 1:
            case 4:
            case 5:
            // 7-as - assigned, bet nezinom kuriam vairui, tai darom tik accepta
            case 7:
                if ($order->getOrderStatus() == OrderService::$status_new) {
                    $orderService->statusAccepted('cili_nav');
                }
                break;

            case 2:
                // TODO persiuntimas
                $logger->error('Order #'.$order->getId().' was marked as redirected in Cili Nav');
                break;

            case 6:
                if ($order->getOrderStatus() == OrderService::$status_new) {
                    // First mark as accepted, for user information
                    $orderService->statusAccepted('cili_nav');
                    $orderService->statusFinished('cili_nav');
                } else if ($orderService->isValidOrderStatusChange($order->getOrderStatus(), OrderService::$status_finished)) {
                    $orderService->statusFinished('cili_nav');
                } else if ($order->getOrderStatus() == OrderService::$status_finished) {
                    // do nothing - it is already finished
                } else {
                    $logger->error(sprintf(
                        'Invalid status change detected: Order #%d was marked as "finished" in Cili Nav. His current status: %s',
                        $order->getId(),
                        $order->getOrderStatus()
                    ));
                }
                break;

            case 8:
            case 9:
                if ($orderService->isValidOrderStatusChange($order->getOrderStatus(), OrderService::$status_completed)
                    && (
                        $order->getDeliveryType() == OrderService::$deliveryPickup
                        || ($order->getDeliveryType() == OrderService::$deliveryDeliver && $order->getOrderFromNav())
                        )
                    ) {
                        $orderService->statusCompleted('cili_nav');

                        // log order data (if we have listeners)
                        $orderService->markOrderForNav($order);
                }
                break;

            case 10:
            case 11:
                if ($orderService->isValidOrderStatusChange($order->getOrderStatus(), OrderService::$status_canceled)) {
                    $orderService->statusCanceled('cili_nav');
                } else {
                    $logger->error(sprintf(
                        'Invalid status change detected: Order #%d was marked as "canceled" in Cili Nav. His current status: %s',
                        $order->getId(),
                        $order->getOrderStatus()
                    ));
                }
                break;

            case 0:
            default:
                // Set delivery order ID if it is not set
                $deliveryOrderId = $order->getNavDeliveryOrder();
                if (empty($deliveryOrderId)) {
                    // TODO pasitikrint lauko pavadinima
                    $order->setNavDeliveryOrder($navOrder['Delivery Order No_']);
                }

                break;
        }
    }

    /**
     * @param Order $order
     * @param string $driverId
     */
    public function setDriverFromNav(Order $order, $driverId)
    {
        $order->setNavDriverCode($driverId);
        $driver = $this->getDriverByNavId($driverId);

        if ($driver instanceof Driver) {
            $order->setDriver($driver);
        }
    }

    /**
     * @param Order[] $orders
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getNavIdsFromOrders($orders)
    {
        if (!is_array($orders)) {
            throw new \InvalidArgumentException('Not an array given. Can not extract Nav ids');
        }

        $navIds = array();

        foreach ($orders as $order) {
            if (!$order instanceof Order) {
                throw new \InvalidArgumentException('Got a non order object when extracting Nav ids');
            }
            // Skip orders from Nav
            if ($order->getOrderFromNav()) {
                continue;
            }

            $navIds[] = $this->getNavOrderId($order);
        }

        return $navIds;
    }

    /**
     * @param int $oId
     */
    public function getOrderHeader($oId)
    {
        $orderId = $this->_orderIdModifier + (int)$oId;
        $result = $this->initSqlConn()->query('SELECT * FROM '.$this->getHeaderTable()." WHERE [Order No_] = '".$orderId."'");
        $result2 = $this->initSqlConn()->query('SELECT * FROM '.$this->getLineTable()." WHERE [Order No_] = '".$orderId."'");
        if( $result === false) {
            throw new \InvalidArgumentException('Wow Such fail.. Many problems... Such no results?');
        }
        echo "<pre>";
        while($rowRez = $this->container->get('food.mssql')->fetchArray($result)) {
            var_dump($rowRez);
        }
        while($rowRez = $this->container->get('food.mssql')->fetchArray($result2)) {
            var_dump($rowRez);
        }
        echo "\n\n----------------------------------";
    }

    /**
     * @param null $date
     * @throws \InvalidArgumentException
     */
    public function syncDisDescription($date = null)
    {
        $result = $this->initSqlConn()->query('SELECT [No_], [Description], [Search Description] FROM '.$this->getItemsTable()." WHERE LEN([No_]) > 3 AND [No_] NOT LIKE 'DIS%'");
        if( $result === false) {
            throw new \InvalidArgumentException('Wow Such fail.. Many problems... Such no results?');
        }

        $counter = 0;
        while($rowRez = $this->container->get('food.mssql')->fetchArray($result)) {
            $query = "REPLACE INTO nav_items VALUES('".addslashes($rowRez['No_'])."', '".addslashes(iconv('cp1257','utf-8',$rowRez['Description']))."', '".addslashes(iconv('cp1257','utf-8',$rowRez['Search Description']))."')";
            $stmt = $this->container->get('doctrine')->getEntityManager()->getConnection()->prepare($query);
            $stmt->execute();
            $counter++;
        }
    }

    protected function convertPaymentType($type)
    {
        switch ($type) {
            case 'local':
                return 'CASH';
            case 'local.card':
                return 'CC';
            case 'paysera':
                return 'BANK_PAYSERA';
            case 'swedbank-gateway':
                return 'BANK_SWED';
            case 'swedbank-credit-card-gateway':
                return 'BANK_CC';
            case 'seb-banklink':
                return 'BANK_SEB';
            case 'nordea-banklink':
                return 'BANK_NORD';
            default:
                return $type;
        }
    }

    public function areWebServicesAlive()
    {
        $critical = false;

        try {
            $client = $this->getContainer()->get('food.nav')->getWSConnection();
            $functions = $client->__getFunctions();

            if (
                !in_array('FoodOutUpdatePrices_Result FoodOutUpdatePrices(FoodOutUpdatePrices $parameters)', $functions)
                || !in_array('FoodOutProcessOrder_Result FoodOutProcessOrder(FoodOutProcessOrder $parameters)', $functions)
            ) {
                $critical = true;
                $text = '<error>ERROR: Foodout NAV SOAP commands not found';
            } else {
                $text = '<info>OK: web services are up and running.</info>';
            }
        } catch (\SoapFault $e) {
            $critical = true;
            $text = '<error>ERROR: could not connect to NAV web services: "' . $e->getMessage() . '"</error>';
        }

        return [$critical, $text];
    }

    /**
     * @return array
     */
    public function getNewNonFoodoutOrders()
    {
        $query = sprintf(
            "SELECT
                dOrder.[Order No_] As [OrderNo],
                dOrder.[Phone No_],
                dOrder.[Date Created],
                dOrder.[Time Created],
                dOrder.[Contact Pickup Time],
                dOrder.[Driver ID],
                dOrder.[Address],
                dOrder.[City],
                dOrder.[Directions],
                dOrder.[Tender Type],
                dOrder.[Restaurant No_],
                dOrder.[Sales Type],
                dOrder.[Chain],
                dOrder.[Contact No_],
                dOrder.[Amount Incl_ VAT],
                pTrans.[VAT %%],
                pTrans.[Amount] AS DeliveryAmount,
                (
                    SELECT
                    SUM([Amount])
                    FROM %s pSumTrans
                    WHERE
                        pSumTrans.[Receipt No_] = dOrder.[Order No_]
                ) AS OrderSum,
                (
                 SELECT TOP 1
                    oStat.[Status]
                 FROM %s oStat
                 WHERE
                    [ORDER No_] = dOrder.[Order No_]
                 ORDER BY [TIME] DESC
                 ) AS OrderStatus
            FROM %s dOrder
            LEFT JOIN %s pTrans ON pTrans.[Receipt No_] = dOrder.[Order No_]
            WHERE
                dOrder.[Date Created] >= '%s'
                AND dOrder.[Time Created] >= '%s'
                AND dOrder.[Delivery Region] IN (%s)
                AND dOrder.[FoodOut Order] != 1
                AND pTrans.[Number] IN ('ZRAW0009996', 'ZRAW0010001', 'ZRAW0010002', 'ZRAW0010190', 'ZRAW0010255')
                AND dOrder.[Replication Counter] > 0
            ORDER BY
                dOrder.[Date Created] ASC,
                dOrder.[Time Created] ASC
            ",
            $this->getPosTransactionLinesTable(),
            $this->getDeliveryOrderStatusTable(),
            $this->getDeliveryOrderTable(),
            $this->getPosTransactionLinesTable(),
            date('Y-m-d'),
            '1754-01-01 '.date("H:i:s", strtotime('-2 hour')),
            "'Vilnius', 'Kaunas', 'Klaipeda'"
        );

        $result = $this->initSqlConn()->query($query);
        if( $result === false) {
            return array();
        }

        $return = array();
        while ($rowRez = $this->container->get('food.mssql')->fetchArray($result)) {
            $return[$rowRez['OrderNo']] = $rowRez;
        }

        return $return;
    }

    /**
     * @param string $chain
     * @param string $restaurantNo
     * @return PlacePoint
     */
    public function getLocalPlacePoint($chain, $restaurantNo)
    {
        $repo = $this->getContainer()->get('doctrine')->getRepository('FoodDishesBundle:PlacePoint');
        $pPoint = $repo->findOneBy(array('internal_code' => $restaurantNo));

        if (!$pPoint instanceof PlacePoint || $pPoint->getId() == '')
        {
            return false;
        }

        if ($pPoint->getPlace()->getChain() != $chain) {
            $this->getContainer()->get('logger')
                ->error(sprintf(
                    'Found placePoint for restaurant no "%s" with id: %d but chain from Nav "%s" does not match Place chain "%s". The point will still be used',
                    $restaurantNo,
                    $pPoint->getId(),
                    $chain.
                    $pPoint->getPlace()->getChain()
                ));
        }

        return $pPoint;
    }

    /**
     * @param string $navDriverId
     * @return mixed
     */
    public function getDriverByNavId($navDriverId)
    {
        $nameParts = str_split($navDriverId, 3);

        $driverQueryBuilder = $this->getContainer()->get('doctrine')->getRepository('FoodAppBundle:Driver')
            ->createQueryBuilder('d')
            ->where('d.name LIKE :first_name_part')
            ->andWhere('d.name LIKE :second_name_part')
            ->setParameters(array(
                'first_name_part' => $nameParts[0]."%",
                'second_name_part' => "% ".$nameParts[1]."%"
            ))
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(1);

        $driver = $driverQueryBuilder->getQuery()->getResult();

        if (empty($driver)) {
            return false;
        }

        return $driver[0];
    }
}
