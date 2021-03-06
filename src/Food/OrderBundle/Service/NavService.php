<?php

namespace Food\OrderBundle\Service;

use Food\AppBundle\Entity\Driver;
use Food\CartBundle\Entity\Cart;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderDetails;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\OrderBundle\Common;
use Food\OrderBundle\Service\NavService\OrderDataForNavDecorator;
use Food\OrderBundle\Service\Events\SoapFaultEvent;
use Food\OrderBundle\Entity\InvoiceToSendNavOnly;

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

    private $headerTable = '[skamb_centras].[dbo].[%1$s$Web ORDER Header]';

    private $lineTable = '[skamb_centras].[dbo].[%1$s$Web ORDER Lines]';

    private $orderTable = '[skamb_centras].[dbo].[%1$s$FoodOut Order]';

    private $messagesTable = '[skamb_centras].[dbo].[%1$s$Web Order Messages]';

    private $itemsTable = '[skamb_centras].[dbo].[%1$s$Item]';

    private $deliveryOrderTable = '[skamb_centras].[dbo].[%1$s$Delivery Order]';

    private $posTransactionLinesTable = '[skamb_centras].[dbo].[%1$s$POS Trans_ Line]';

    private $deliveryOrderStatusTable = '[skamb_centras].[dbo].[%1$s$Delivery order status]';

    private $contractTable = '[skamb_centras].[dbo].[%1$s$Contract]';

    private $customerTable = '[skamb_centras].[dbo].[%1$s$Customer]';

    private $invoiceTable = '[skamb_centras].[dbo].[%1$s$Foodout Invoice]';

    private $postedDeliveryOrdersTable = '[skamb_centras].[dbo].[%1$s$Posted Delivery Orders]';

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
        return sprintf($this->headerTable, $this->container->getParameter('nav_table_prefix'));
    }

    /**
     * @return string
     */
    public function getLineTable()
    {
        return sprintf($this->lineTable, $this->container->getParameter('nav_table_prefix'));
    }

    /**
     * @return string
     */
    public function getOrderTable()
    {
        return sprintf($this->orderTable, $this->container->getParameter('nav_table_prefix'));
    }

    /**
     * @return string
     */
    public function getMessagesTable()
    {
        return sprintf($this->messagesTable, $this->container->getParameter('nav_table_prefix'));
    }

    /**
     * @return string
     */
    public function getItemsTable()
    {
        return sprintf($this->itemsTable, $this->container->getParameter('nav_table_prefix'));
    }

    /**
     * @return string
     */
    public function getDeliveryOrderTable()
    {
        return sprintf($this->deliveryOrderTable, $this->container->getParameter('nav_table_prefix'));
    }

    /**
     * @return string
     */
    public function getPosTransactionLinesTable()
    {
        return sprintf($this->posTransactionLinesTable, $this->container->getParameter('nav_table_prefix'));
    }

    /**
     * @return string
     */
    public function getDeliveryOrderStatusTable()
    {
        return sprintf($this->deliveryOrderStatusTable, $this->container->getParameter('nav_table_prefix'));
    }

    public function getInvoiceTable()
    {
        return sprintf($this->invoiceTable, $this->container->getParameter('nav_table_prefix'));
    }

    /**
     * @return string
     */
    public function getContractTable()
    {
        return sprintf($this->contractTable, $this->container->getParameter('nav_table_prefix'));
    }

    /**
     * @return string
     */
    public function getCustomerTable()
    {
        return sprintf($this->customerTable, $this->container->getParameter('nav_table_prefix'));
    }

    public function getPostedDeliveryOrdersTable()
    {
        return sprintf($this->postedDeliveryOrdersTable, $this->container->getParameter('nav_table_prefix'));
    }


    /**
     * @return false|resource
     */
    public function getConnection()
    {
        if ($this->conn == null) {
            $serverName = "213.197.176.247, 5566"; //serverName\instanceName, portNumber (default is 1433)
            //$connectionInfo = array( "Database"=>"prototipas6", "UID"=>"fo_order", "PWD"=>"peH=waGe?zoOs69");
            //$connectionInfo = array( "Database"=>"prototipas6", "UID"=>"nas", "PWD"=>"c1l1j@");
            $connectionInfo = ["Database" => "prototipas6", "UID" => "CILIJA\Neotest", "PWD" => "NewNeo@123"];
            //$connectionInfo = array( "Database"=>"skamb_centras", "UID"=>"fo_order", "PWD"=>"peH=waGe?zoOs69");
            $this->conn = sqlsrv_connect($serverName, $connectionInfo);

            if ($this->conn === false) {
                die(print_r(sqlsrv_errors(), true));
            }
        }

        return $this->conn;
    }

    /**
     * @return SqlConnectorService
     */
    public function initSqlConn()
    {
        $sqlSS = $this->container->get('food.mssql');

        $isConnected = $sqlSS->init(
            '213.197.176.247',
            5566,
            'skamb_centras',
            'Neotest',
            'NewNeo@123'
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

    public function initTestSqlConn()
    {
        $sqlSS = $this->container->get('food.mssql');

        $isConnected = $sqlSS->init(
            '213.197.176.247',
            5566,
            'prototipas6',
            'Neotest',
            'NewNeo@123'
        );

        return $isConnected ? $sqlSS : $isConnected;
    }

    public function getLastOrders()
    {
        $query = sprintf('SELECT TOP 1 * FROM %s WHERE [Order No_] = 2000529081', $this->getHeaderTable());
        $conn = $this->initSqlConn();
        $result = $conn->query($query);
        foreach ($conn->fetchArray($result) as $row) {
            var_dump($row);
        }
    }

    /**
     * I am old and unused
     */
    public function testInsertOrder()
    {
        $dataToPut = [
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
            'Order Time' => '1754-01-01 ' . date("H:i:s"),
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
        ];
        $queryPart = $this->generateQueryPart($dataToPut);
        $this->getContainer()->get('logger')->alert('INSERT INTO ' . $this->getHeaderTable() . ' (' . $queryPart['keys'] . ') VALUES(' . $queryPart['values'] . ')');
        $rez = sqlsrv_query($this->getConnection(), 'INSERT INTO ' . $this->getHeaderTable() . ' (' . $queryPart['keys'] . ') VALUES(' . $queryPart['values'] . ')');

        if ($rez === false) {
            $this->getContainer()->get('logger')->alert(print_r(sqlsrv_errors(), true));
            die(print_r(sqlsrv_errors(), true));
        }

        print_r($queryPart);
    }

    private function generateQueryPart($data)
    {
        $arrayKeys = array_keys($data);
        $values = array_values($data);

        return [
            'keys' => '[' . implode('],[', $arrayKeys) . ']',
            'values' => "'" . implode("','", $values) . "'",
        ];
    }

    private function generateQueryPartNoQuotes($data)
    {
        $arrayKeys = array_keys($data);
        $values = array_values($data);

        return [
            'keys' => '[' . implode('],[', $arrayKeys) . ']',
            'values' => implode(',', $values)
        ];
    }
//todo MULTI-L BBZ
    public function putTheOrderToTheNAV(Order $order)
    {

        $orderNewId = $this->getNavOrderId($order);

        $orderRow = null;
        $street = "";
        $houseNr = "";
        $flatNr = "";
        if ($order->getAddressId()) {
            $target = $order->getAddressId()->getAddress();
            preg_match('/(([0-9]{1,3}[a-z]{0,1})[-|\s]{0,4}([0-9]{0,3}))$/i', $target, $errz);
            $street = trim(str_replace($errz[0], '', $target));
            $houseNr = (!empty($errz[2]) ? $errz[2] : '');
            $flatNr = (!empty($errz[3]) ? $errz[3] : '');
        }

        $cityObj = $order->getPlacePoint()->getCityId();

        $city = $cityObj->getTitle();
        $region = $cityObj->getCode();

        $orderDate = $order->getOrderDate();
        $orderDate->add(new \DateInterval('P0DT0H'));
        $deliveryDate = $order->getDeliveryTime();
        //~ $deliveryDate->sub(new \DateInterval('P0DT3H'));
//        $deliveryDate->sub(new \DateInterval('P0DT3H'));
        $deliveryDate->sub(new \DateInterval('P0DT2H'));
        //~ $deliveryDate->setTimezone(new DateTimeZone('UTC'));
        /**
         * This thing is when we move to daytime saving mode.
         * @todo put to the CONFIG or make AUTO
         *
         * $deliveryDate->sub(new \DateInterval('P0DT2H'));
         */

        $comment = $order->getComment();

        if ($order->getPaymentMethod() == "local.card") {
            if($this->container->getParameter('country') == 'LV'){
                $comment .= ". KREDITKARTE";
            } else {
                $comment.=". Mokesiu kortele";
            }
        } elseif ($order->getPaymentMethod() == "local") {
            if($this->container->getParameter('country') == 'LV') {
                $comment .= ". SKAIDRA NAUDA";
            } else {
                $comment .= ". Mokesiu grynais";
            }
        } else {
            $comment .= ". " . $order->getPaymentMethod();
        }

        $dataToPut = [
            'Order No_'          => $orderNewId,
            'Phone'              => str_replace(['370', '371'], '8', $order->getOrderExtra()->getPhone()), //TODO: create standart at params
            'ZipCode'            => '', // ($order->getDeliveryType() == OrderService::$deliveryDeliver ? $orderRow->getZipCode() : ''),
            'City'               => $city,
            'Street'             => $street, //($order->getDeliveryType() == OrderService::$deliveryDeliver ? $orderRow->getStreetName(): ''),
            'Street No_'         => $houseNr, //($order->getDeliveryType() == OrderService::$deliveryDeliver ? $orderRow->getNumberFrom(): ''),
            'Floor'              => '',
            'Grid'               => '', // ($order->getDeliveryType() == OrderService::$deliveryDeliver ? $orderRow->getGrid(): ''),
            'Chain'              => $order->getPlace()->getChain(),
            'Name'               => 'FO:' . $order->getUser()->getFirstname(),
            'Delivery Type'      => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? 1 : 4),
            //'Restaurant No_' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? '':  $order->getPlacePoint()->getInternalCode()),
            'Restaurant No_' => $order->getPlacePoint()->getInternalCode(),
            'Order Date' => $orderDate->format("Y-m-d"),
            'Order Time' => '1754-01-01 ' . $orderDate->format("H:i:s"),
            'Takeout Time' => $deliveryDate->format("Y-m-d H:i:s"),
            'Directions' => $comment,
            'Discount Card No_' => '',
            'Order Status' => 4,
            'Delivery Order No_' => '',
            'Error Description'  => '',
            'Flat No_'           => $flatNr, //($order->getDeliveryType() == OrderService::$deliveryDeliver ? $flatNr: ''),
            'Entrance Code'      => '',
            'Region Code'        => $region, //$order->getDeliveryType() == OrderService::$deliveryDeliver ? $orderRow->getDeliveryRegion() : ''), // TODO: MULTI-L REGION UPPER CASE ??
            'Delivery Status'    => 12,
            'In Use By User'     => '',
            'Loyalty Card No_'   => '',
            'Order with Alcohol' => '0'
        ];
        $queryPart = $this->generateQueryPart($dataToPut);
        $query = 'INSERT INTO ' . $this->getHeaderTable() . ' (' . $queryPart['keys'] . ') VALUES(' . $queryPart['values'] . ')';
        //~ $logger->alert('#' . ($orderNewId - $this->_orderIdModifier) . ' [SQL Line Query]-#HEADER');
        //~ $logger->alert($query);
        $this->container->get('food.order')->logOrder($order, 'NAV_INSERT_QUERY', null, $query);
        $sqlSS = $this->initSqlConn()->query($query);


        $this->_processLines($order, $orderNewId);
    }

    private function _processLines(Order $order, $orderNewId)
    {
        $theKey = 1;
        $this->container->get('doctrine')->getManager()->refresh($order);
        $discountSum = $order->getDiscountSum();
        $discountSumLeft = $discountSum;
        foreach ($order->getDetails() as $key => $detail) {
            $theKey = $this->_processLine($detail, $orderNewId, $theKey, $discountSum, $discountSumLeft);
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
        $dataToPut = [
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
            'Value' => "''",
            'Discount No_' => "''"
        ];

        $queryPart = $this->generateQueryPartNoQuotes($dataToPut);

        $query = 'INSERT INTO ' . $this->getLineTable() . ' (' . $queryPart['keys'] . ') VALUES(' . $queryPart['values'] . ')';
        //~ $this->getContainer()->get('logger')->alert('#' . ($orderNewId - $this->_orderIdModifier) . ' [SQL Line Query]-#PREPAID');
        //~ $this->getContainer()->get('logger')->alert($query);
        $this->container->get('food.order')->logOrder($order, 'NAV_INSERT_LINE_PAYED_QUERY', null, $query);
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
        //$devPrice = $order->getPlace()->getDeliveryPrice();
        //$couponCode = $order->getCouponCode();
        //if (!empty($couponCode) && strlen($couponCode) > 1) {
        $devPrice = $order->getDeliveryPrice();
        //}

        $dataToPut = [
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
            'Value' => "''",
            'Discount No_' => "''"
        ];

        $queryPart = $this->generateQueryPartNoQuotes($dataToPut);

        $query = 'INSERT INTO ' . $this->getLineTable() . ' (' . $queryPart['keys'] . ') VALUES(' . $queryPart['values'] . ')';
        //~ $this->getContainer()->get('logger')->alert('#' . ($orderNewId - $this->_orderIdModifier) . ' [SQL Line Query]-#DELIVERY');
        //~ $this->getContainer()->get('logger')->alert($query);
        $this->container->get('food.order')->logOrder($order, 'NAV_INSERT_LINE_DELIVERY_QUERY', null, $query);
        $sqlSS = $this->initSqlConn()->query($query);
    }

    private function _processLine(OrderDetails $detail, $orderNewId, $key, $discountSum, &$discountSumLeft)
    {
        $this->container->get('doctrine')->getManager()->refresh($detail);
        $order = $detail->getOrderId();

        //~ $desc = $detail->getDishName();
        //~ $unitDesc = $detail->getDishUnitName();

        //~ $desc = str_replace(["'", '"', ',', '(', ')'], '', $desc);
        //~ $desc = str_replace(['ė', 'e', 'Ę', 'Ė'], 'e', $desc);
        //~ $desc = str_replace(['ą', 'Ą'], 'a', $desc);
        //~ $desc = str_replace(['č', 'Č'], 'c', $desc);
        //~ $desc = str_replace(['į', 'Į'], 'i', $desc);
        //~ $desc = str_replace(['š', 'Š'], 's', $desc);
        //~ $desc = str_replace(['ų', 'ū', 'Ų', 'Ū'], 'u', $desc);
        //~ $desc = strtolower($desc);

        //~ $unitDesc = str_replace(["'", '"', ',', '(', ')'], '', $unitDesc);
        //~ $unitDesc = str_replace(['ė', 'e', 'Ę', 'Ė'], 'e', $unitDesc);
        //~ $unitDesc = str_replace(['ą', 'Ą'], 'a', $unitDesc);
        //~ $unitDesc = str_replace(['č', 'Č'], 'c', $unitDesc);
        //~ $unitDesc = str_replace(['į', 'Į'], 'i', $unitDesc);
        //~ $unitDesc = str_replace(['š', 'Š'], 's', $unitDesc);
        //~ $unitDesc = str_replace(['ų', 'ū', 'Ų', 'Ū'], 'u', $unitDesc);
        //~ $unitDesc = strtolower($unitDesc);

        //~ $desc = str_replace("makaronai", "makar", $desc);
        //~ $desc = str_replace("lasisomis", "lasis", $desc);

        //~ $desc = "'" . substr($desc, 0, 29) . " " . $unitDesc . "'";
        //~ $desc = str_replace(" pica", "", $desc);
        //~ $desc = str_replace("Apkepti", "apk", $desc);
        //~ $desc = str_replace("blyneliai", "blynel", $desc);
        //~ $desc = str_replace(" Porcija", "", $desc);

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

        if (!empty($code)) {
            $data = $this->container->get('doctrine')->getRepository('FoodOrderBundle:NavItems')->find($code);
            if ($data) {
                $desc = "'" . $data->getDescription() . "'";
            }
        } else {
            $desc = "'" . $detail->getNameToNav() . "'";
        }

        $priceForInsert = $detail->getOrigPrice();
        $amountForInsert = $priceForInsert * $detail->getQuantity();
        $discountAmount = 0;
        $paymentAmount = $amountForInsert;

        $discountInOrder = $detail->getOrderId()->getDiscountSize();
        $dp = $detail->getPrice();
        $dop = $detail->getOrigPrice();
        $discPrcEnabled = $detail->getDishId()->getDiscountPricesEnabled();
        $placeDiscountPriceEnabled = $detail->getOrderId()->getPlace()->getDiscountPricesEnabled();
        $detailPercentDiscount = $detail->getPercentDiscount();

        if ($detail->getIsFree()) {
            $discountAmount = $amountForInsert;
        } else {
            if (!($dp != $dop && $discPrcEnabled && $placeDiscountPriceEnabled) && empty($detailPercentDiscount) && $discountInOrder > 0) {
                $discountAmount = $paymentAmount - round($paymentAmount * ((100 - intval($discountInOrder)) / 100), 2);
            } elseif (!($dp != $dop && $discPrcEnabled && $placeDiscountPriceEnabled) && !empty($detailPercentDiscount)) {
                $discountAmount = ($detail->getOrigPrice() - $detail->getPrice()) * $detail->getQuantity();
            } elseif ($dp != $dop && $discPrcEnabled && $placeDiscountPriceEnabled) {
                $discountAmount = ($detail->getOrigPrice() - $detail->getPrice()) * $detail->getQuantity();
            }
        }
        /**
         * Some freaky ugly magic for havin Cart discount
         */
        /*
                if (0 && !empty($discountSum) && !($detail->getPrice()!=$detail->getOrigPrice() && $detail->getDishId()->getDiscountPricesEnabled() && $detail->getOrderId()->getPlace()->getDiscountPricesEnabled())) {
                    $paymentPart = $paymentAmount + $discountAmount;
                    $paymentPercentPart = ceil($detail->getOrderId()->getTotal() / $paymentPart) * 100;
                    $discountPartWouldBe = ceil($discountSum * 100 / $paymentPercentPart);
                    if ($discountSumLeft <= $discountPartWouldBe) {
                        $discountPartWouldBe = $discountSumLeft;
                        $discountSumLeft = 0;
                    } else {
                        $discountSumLeft = $discountSumLeft - $discountPartWouldBe;
                    }
                    $discountAmount = $discountAmount + $discountPartWouldBe;
                }
        */
        /*
        if ($detail->getDishId()->getShowDiscount()) {
            $discountPrice = $detail->getPrice();
            $priceForInsert = $detail->getOrigPrice();
            $amountForInsert = $priceForInsert * $detail->getQuantity();
            $discountAmount = ($priceForInsert - $discountPrice) * $detail->getQuantity();
            $paymentAmount = $amountForInsert - $discountAmount;
        }
        */
        $desc = str_replace(["'", '"', ',', '(', ')', '`'], '', $desc);
        $desc = "'" . $desc . "'";
        $mmCode = "'" . $detail->getDishSizeMmCode() . "'";
        $dataToPut = [
            'Order No_' => $orderNewId,
            'Line No_' => $key,
            'Entry Type' => 0,
            'No_' => "'" . $code . "'",
            'Description' => $desc,
            'Quantity' => $detail->getQuantity(),
            'Price' => $priceForInsert, //$detail->getPrice(), // @todo test the price. Kaip gula. Total ar ne.
            'Parent Line' => 0, // @todo kaip optionsai sudedami. ar prie pirmines kainos ar ne
            'Amount' => $amountForInsert, // $detail->getPrice() * $detail->getQuantity(),// @todo test the price. Kaip gula. Total ar ne.
            'Discount Amount' => "-" . $discountAmount,
            'Payment' => $amountForInsert - $discountAmount, //$detail->getPrice() * $detail->getQuantity(),
            'Value' => "''",
            'Discount No_' => $mmCode
        ];
        $queryPart = $this->generateQueryPartNoQuotes($dataToPut);
        $query = 'INSERT INTO ' . $this->getLineTable() . ' (' . $queryPart['keys'] . ') VALUES(' . $queryPart['values'] . ')';
        //~ $this->getContainer()->get('logger')->alert('#' . ($orderNewId - $this->_orderIdModifier) . ' [SQL Line Query]-#' . $key);
        //~ $this->getContainer()->get('logger')->alert($query);

        $this->container->get('food.order')->logOrder($order, 'NAV_INSERT_LINE_QUERY', null, $query);
        $sqlSS = $this->initSqlConn()->query($query);

        $okeyCounter = $key;
        foreach ($detail->getOptions() as $okey => $opt) {
            if ($okey != $optionIdUsed) {
                $okeyCounter++;
                $code = $opt->getDishOptionCode();
                $desc = $opt->getNameToNav();
                if ($opt->getDishOptionId()->getInfocode()) {
                    $desc = $opt->getDishOptionId()->getSubCode();
                }

                $dataToPut = [
                    'Order No_' => $orderNewId,
                    'Line No_' => $okeyCounter,
                    'Entry Type' => ($opt->getDishOptionId()->getFirstLevel() ? 0 : 1),
                    'No_' => "'" . $code . "'",
                    'Description' => "'" . $desc . "'",
                    'Quantity' => ($opt->getDishOptionId()->getFirstLevel() ? $opt->getOrderDetail()->getQuantity() : 0),
                    'Price' => ($opt->getDishOptionId()->getFirstLevel() ? $opt->getDishOptionId()->getPrice() : 0),
                    'Parent Line' => ($opt->getDishOptionId()->getFirstLevel() ? 0 : $key),
                    'Amount' => ($opt->getDishOptionId()->getFirstLevel() ? ($opt->getOrderDetail()->getQuantity() * $opt->getDishOptionId()->getPrice()) : 0),
                    'Discount Amount' => 0,
                    'Payment' => ($opt->getDishOptionId()->getFirstLevel() ? ($opt->getOrderDetail()->getQuantity() * $opt->getDishOptionId()->getPrice()) : 0),
                    'Value' => "''",
                    'Discount No_' => "''"
                ];
                $queryPart = $this->generateQueryPartNoQuotes($dataToPut);
                $query = 'INSERT INTO ' . $this->getLineTable() . ' (' . $queryPart['keys'] . ') VALUES(' . $queryPart['values'] . ')';
                //~ $this->getContainer()->get('logger')->alert('#' . ($orderNewId - $this->_orderIdModifier) . ' [SQL Line Query SUBQ]#' . $key . "-" . $okey);
                //~ $this->getContainer()->get('logger')->alert($query);
                $this->container->get('food.order')->logOrder($order, 'NAV_INSERT_LINE_OPTIONS_QUERY', null, $query);
                $sqlSS = $this->initSqlConn()->query($query);
            }
        }
        $key = $okeyCounter;

        return $key;
    }

    /**
     * @param Order $order
     *
     * @return int
     */
    public function getNavOrderId(Order $order)
    {
        return $this->_orderIdModifier + (int)$order->getId();
    }

    /**
     * @param int $navId
     *
     * @return int
     */
    public function getOrderIdFromNavId($navId)
    {
        return $navId - $this->_orderIdModifier;
    }

    public function getWSConnection()
    {

        $clientUrl = "http://213.197.176.247:7059/DynamicsNAV/WS/Codeunit/WEB_Service2?wsdl";
        // $clientUrl2 = "http://213.190.40.38:7059/DynamicsNAV/WS/PROTOTIPAS%20Skambuciu%20Centras/Codeunit/WEB_Service2";
        $clientUrl2 = sprintf('http://213.197.176.247:7055/DynamicsNAV/WS/%1$s/Codeunit/WEB_Service2', str_replace(" ", "%20", $this->container->getParameter('nav_ws_prefix')));

        stream_wrapper_unregister('http');
        stream_wrapper_register('http', '\Food\OrderBundle\Common\FoNTLMStream') or die("Failed to register protocol");

        $url = $clientUrl2;
        //$options = array('trace'=>1, 'login' =>'CILIJA\fo_order', 'password' => 'peH=waGe?zoOs69');
        $options = ['trace' => 1, 'cache_wsdl' => WSDL_CACHE_NONE, 'login' => 'CILIJA\nas', 'password' => 'c1l1j@'];
        $client = @new Common\FoNTLMSoapClient($url, $options);
        $client->setContainer($this->getContainer());
        stream_wrapper_restore('http');

        return $client;
    }

    public function updatePricesNAV(Order $order)
    {
        if (!$order->getNavPriceUpdated()) {
            $orderId = $this->getNavOrderId($order);
            ob_start();
            $client = $this->getWSConnection();
            $return = $client->FoodOutUpdatePrices(['pInt' => (int)$orderId]);
            ob_end_clean();
            $order = $this->getContainer()->get('doctrine')->getRepository('FoodOrderBundle:Order')->find($order->getId());
            $this->getContainer()->get('doctrine')->getManager()->refresh($order);
            $order->setNavPriceUpdated(true);
            $this->getContainer()->get('doctrine')->getManager()->persist($order);
            $this->getContainer()->get('doctrine')->getManager()->flush();
        } else {
            $return = true;
        }

        return $return;
    }

    public function processOrderNAV(Order $order)
    {
        $orderId = $this->getNavOrderId($order);

        $query = 'UPDATE ' . $this->getHeaderTable() . ' SET [Order Status]=0, [Delivery Status]=0 WHERE [Order No_] = ' . $orderId;

        $sqlSS = $this->initSqlConn()->query($query);

        if (!$order->getNavPorcessedOrder()) {
            ob_start();
            $client = $this->getWSConnection();
            $return = $client->FoodOutProcessOrder(['pInt' => (int)$orderId]);
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

        $deliveryTotal = $o->getDeliveryPriceForNav()->val(0.0);
        $discountSum = $o->getDiscountSum()->val(0.0);
        $foodTotal = $o->getFoodTotalForNav()->val(0.0);

        // payment type and code preprocessing
        $driverId = $o->getDriver()->getId()->val('');

        // client name depends on if client is a company or not
        $clientName = $o->getCompany()->val(false)
            ? $o->getCompanyName()->val('')
            : sprintf('%s %s',
                $o->getUser()->getFirstname()->val(''),
                $o->getUser()->getLastname()->val(''));

        $paymentType = $o->getPaymentMethod()->val('');
        $paymentCode = $paymentType == 'local'
            ? $driverId
            : $this->convertPaymentType($paymentType);

        // main variable that holds parameters for a Soap call
        $params = ['InvoiceNo' => $o->getSfSeries()->val('') . $o->getSfNumber()->val(''),
            'OrderID' => $o->getId()->val('0'),
            'OrderDate' => $o->getDeliveryTime()->format('Y.m.d')->val('1754-01-01'),
            'RestaurantID' => $o->getPlace()->getId()->val('0'),
            'RestaurantName' => $o->getPlaceName()->val(''),
            'DriverID' => $driverId,
            'ClientName' => $this->normalizeStringForNav($clientName),
            'RegistrationNo' => $o->getCompanyCode()->val(''),
            'VATRegistrationNo' => $o->getVATCode()->val(''),
            'DeliveryAddress' => $this->normalizeStringForNav($o->getAddressId()->getAddress()->val('')),
            'City' => $o->getPlacePointCity()->val(''),
            'PaymentType' => substr($paymentType, 0, 20),
            'PaymentCode' => $paymentCode,
            'FoodAmount' => number_format($foodTotal, 2, '.', ''),
            'AlcoholAmount' => number_format(0.0, 2, '.', ''),
            'DeliveryAmount' => $o->getDeliveryType()->val('') == 'pickup'
                ? '0.00'
                : number_format($deliveryTotal, 2, '.', ''),
            'DiscountAmount' => number_format($discountSum, 2, '.', '') // doesn't works
        ];

        // send a call to a web service, but beware of exceptions
        try {
            $response = $client->FoodOutCreateInvoice(['params' => $params]);

            $r = \Maybe($response);

            // correct logic is when $response->return_value == 0
            if (!($r->return_value->val('') == 0)) {
                throw new \SoapFault((string)$r->return_value->val(''),
                    'Soap call "FoodOutCreateInvoice" didn\'t return 0. Parameters used: ' . var_export($params, true));
            }

            if ($o->getDiscountSum()->val(0.0) > 0) {
                $this->updateNavInvoice($order, ['Discount Amount with VAT' => number_format($discountSum, 2, '.', '')]);
            }
        } catch (\SoapFault $e) {
            if (preg_match('/The Foodout Invoice already exists\./', $e->getMessage())) {
                return true;
            }

            $event = new SoapFaultEvent($e);

            $this->getContainer()
                ->get('event_dispatcher')
                ->dispatch(SoapFaultEvent::SOAP_FAULT, $event);
        }

        $r = \Maybe($response);

        return $r->return_value->val('') == 0 ? true : false;
    }

    /**
     * @param String $phone
     * @param PlacePoint $restaurant
     * @param String $orderDate
     * @param String $orderTime
     * @param String $deliveryType
     * @param Cart[] $dishes
     *
     * @return array
     */
    public function validateCartInNav($phone, $restaurant, $orderDate, $orderTime, $deliveryType, $dishes)
    {
        $rcCode = !empty($restaurant) ? $restaurant->getInternalCode() : '';

        $requestData = [
            'Phone' => str_replace(['370', '371'], "8", $phone),
            'RestaurantNo' => $rcCode,
            'OrderDate' => str_replace("-", ".", $orderDate),
            'OrderTime' => $orderTime,
            'DeliveryType' => ($deliveryType == OrderService::$deliveryDeliver ? 1 : 4)
        ];

        $lineNo = 0;
        foreach ($dishes as $detailKey => $cart) {
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

            $lineMap = [];
            $requestData['Lines'][] = ['Line' => [
                'LineNo' => $lineNo,
                'ParentLineNo' => 0,
                'EntryType' => 0,
                'ItemNo' => $code,
                'Description' => mb_substr($cart->getDishId()->getNameToNav(), 0, 30, 'utf-8'),
                'Quantity' => $cart->getQuantity(),
                'Price' => $cart->getDishSizeId()->getPrice(),
                'Amount' => $cart->getDishSizeId()->getPrice() * $cart->getQuantity()
            ]];
            $lineMap[$lineNo] = [
                'parent' => 0,
                'name' => $cart->getDishId()->getName()
            ];

            $origLineNo = $lineNo;
            foreach ($detailOptions = $cart->getOptions() as $optKey => $option) {
                if ($optKey == $disFromOptions) {
                    continue;
                } else {
                    $optionCode = $option->getDishOptionId()->getCode();
                    $description = "";
                    if ($option->getDishOptionId()->getInfocode()) {
                        $optionCode = $option->getDishOptionId()->getCode();
                        $description = $option->getDishOptionId()->getSubCode();
                    } else {
                        $description = $option->getDishOptionId()->getNameToNav();
                    }
                    $lineNo = $lineNo + 1;
                    if ($option->getDishOptionId()->getFirstLevel()) {
                        $parencyLineNo = 0;
                        $entryType = 0;
                    } else {
                        $parencyLineNo = $origLineNo;
                        $entryType = 1;
                    }


                    $lineMap[$lineNo] = [
                        'parent' => $origLineNo,
                        'name' => $description
                    ];

                    $requestData['Lines'][] = ['Line' => [
                        'LineNo' => $lineNo,
                        'ParentLineNo' => $parencyLineNo,
                        'EntryType' => $entryType,
                        'ItemNo' => $optionCode,
                        'Description' => mb_substr($description, 0, 30, 'utf-8'),
                        'Quantity' => $cart->getQuantity(),
                        'Price' => $option->getDishOptionId()->getPrice(),
                        'Amount' => $option->getDishOptionId()->getPrice() * $cart->getQuantity()
                    ]];

                }
            }
        }
        //~ $this->container->get('logger')->debug('CILI NVB VALIDATE LINES');
        //~ $this->container->get('logger')->debug(print_r($requestData, true));
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
        //~ $this->container->get('logger')->debug('CILI NVB VALIDATE REQUEST');
        //~ $this->container->get('logger')->debug(print_r($requestData, true));
        $response = $this->getWSConnection()->FoodOutValidateOrder(
            [
                'params' => $requestData,
                'errors' => []
            ]
        );
        //~ $this->container->get('logger')->debug('CILI NVB VALIDATE RESPONSE');
        //~ $this->container->get('logger')->debug(print_r($response, true));
        ob_end_clean();

        $prbDish = "";
        if ($response->return_value == 2 || $response->return_value == 3) {
            if ($lineMap[$response->errors->Error->SubCode]['parent'] == 0) {
                $prbDish = $lineMap[$response->errors->Error->SubCode]['name'];
            } else {
                $prbDish = $lineMap[$lineMap[$response->errors->Error->SubCode]['parent']]['name'];
            }
        }

        $returner = [
            'valid' => ($response->return_value == 0 ? true : false),
            'errcode' => [
                'code' => ($response->return_value != 0 && isset($response->errors->Error) ? $response->errors->Error->Code : ''),
                'line' => ($response->return_value != 0 && isset($response->errors->Error) ? $response->errors->Error->SubCode : ''),
                'msg' => ($response->return_value != 0 && isset($response->errors->Error) ? $response->errors->Error->Description : ''),
                'problem_dish' => $prbDish
            ]
        ];

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
            return [];
        }

        $query = sprintf(
            'SELECT woh.[Order No_], woh.[Order Status], woh.[Delivery Status], woh.[Delivery Order No_], dor.[Driver ID]
            FROM %s woh
            LEFT JOIN %s dor ON dor.[ORDER No_] = woh.[Delivery ORDER No_]
            WHERE
                woh.[Order No_] IN (%s)
            ORDER BY woh.[Order No_] DESC',
            $this->getHeaderTable(),
            $this->getDeliveryOrderTable(),
            implode(', ', $orderIds)
        );

        $result = $this->initSqlConn()->query($query);
        if ($result === false) {
            return [];
        }

        $return = [];
        while ($rowRez = $this->container->get('food.mssql')->fetchArray($result)) {
            $return[$this->getOrderIdFromNavId($rowRez['Order No_'])] = $rowRez;
        }

        return $return;
    }

    public function didOrderPlaceChange($orderNo)
    {
        if (empty($orderNo)) {
            return [];
        }

        $mssql = $this->container->get('food.mssql');

        $query = sprintf('
            SELECT
                woh.[Delivery Order No_],
                do.[Order No_],
                pt.[Receipt No_],
                do.[Restaurant No_],
                pt.[Store No_]
            FROM %s woh
            LEFT JOIN [skamb_centras].[dbo].[Čilija Skambučių Centras$Delivery Order] do ON do.[Order No_] = woh.[Delivery Order No_]
            LEFT JOIN [skamb_centras].[dbo].[Čilija Skambučių Centras$POS Transaction] pt ON pt.[Receipt No_] = do.[Order No_]
            WHERE
                do.[Restaurant No_] != pt.[Store No_] AND
                pt.[Store No_] != \'ISC\' AND
                woh.[Order No_] = %s
            ORDER BY woh.[timestamp] DESC',
            $this->getHeaderTable(),
            $orderNo);

        $result = $this->initSqlConn()->query($query);

        if ($result === false) {
            return [];
        }

        $resultList = [];
        while ($row = $mssql->fetchArray($result)) {
            $maybeRow = \Maybe($row);
            $resultList[$this->getOrderIdFromNavId($maybeRow['Order No_']->val(''))] = $row;
        }

        return $resultList;
    }

    /*SELECT TOP 10 woh.[Delivery Order No_], do.[Order No_], pt.[Receipt No_], do.[Restaurant No_], pt.[Store No_]
    FROM [skamb_centras].[dbo].[Čilija Skambučių Centras$Web ORDER Header] woh
    LEFT JOIN [skamb_centras].[dbo].[Čilija Skambučių Centras$Delivery Order] do ON do.[Order No_] = woh.[Delivery Order No_]
    LEFT JOIN [skamb_centras].[dbo].[Čilija Skambučių Centras$POS Transaction] pt ON pt.[Receipt No_] = do.[Order No_]
    WHERE do.[Restaurant No_] != pt.[Store No_] AND pt.[Store No_] != 'ISC'
    ORDER BY woh.[timestamp] DESC;*/

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
            SELECT [Order No_], SUM([Amount] + [Discount Amount]) AS total
            FROM %s
            WHERE
              [Order No_] IN ( %s )
            GROUP BY [Order No_]
            ORDER BY [Order No_] DESC',
            $this->getLineTable(),
            implode(', ', $orderIds)
        );

        $result = $this->initSqlConn()->query($query);
        if ($result === false) {
            throw new \InvalidArgumentException('No wanted orders found in Nav. How is that even possible?');
        }

        $return = [];
        while ($rowRez = $this->container->get('food.mssql')->fetchArray($result)) {
            $return[$this->getOrderIdFromNavId($rowRez['Order No_'])] = $rowRez;
        }

        return $return;
    }

    /**
     * @param Order[] $orders
     *
     * @return array
     */
    public function getImportedOrdersStatus($orders)
    {
        $orderIds = [];
        $orderIdMap = [];

        foreach ($orders as $order) {
            if ($order->getOrderFromNav()) {
                $orderIds[] = $order->getNavDeliveryOrder();
                $orderIdMap[$order->getNavDeliveryOrder()] = $order->getId();
            }
        }

        if (empty($orderIds)) {
            return [];
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
                 ORDER BY [Date] DESC, [TIME] DESC
                 ) AS OrderStatus,
                 (
                    SELECT
                    SUM([Amount])
                    FROM %s pSumTrans
                    WHERE
                        pSumTrans.[Receipt No_] = dOrder.[Order No_]
                        AND pSumTrans.[Deleted] = 0
                        AND pSumTrans.[Entry Status] = 0
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
        if ($result === false) {
            return [];
        }

        $return = [];
        while ($rowRez = $this->container->get('food.mssql')->fetchArray($result)) {
            $return[$orderIdMap[$rowRez['OrderNo']]] = [
                'Order No_' => $rowRez['OrderNo'],
                'Order Status' => null,
                'Delivery Status' => $rowRez['OrderStatus'],
                'Delivery Order No_' => $rowRez['OrderNo'],
                'Driver ID' => $rowRez['Driver ID'],
                'Total Sum' => $rowRez['OrderSum'],
            ];
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
        switch ($navOrder['Delivery Status']) {
            case 1:
            case 4:
            case 5:
                // 7-as - assigned, bet nezinom kuriam vairui, tai darom tik accepta
            case 7:
                if (in_array($order->getOrderStatus(), [OrderService::$status_new, OrderService::$status_preorder])) {
                    $orderService->statusAccepted('cili_nav');
                }
                break;

            case 2:
                // TODO persiuntimas
                $logger->alert('Order #' . $order->getId() . ' was marked as redirected in Cili Nav');
                break;

            case 6:
                if (in_array($order->getOrderStatus(), [OrderService::$status_new, OrderService::$status_preorder])) {
                    // First mark as accepted, for user information
                    $orderService->statusAccepted('cili_nav');
                    $orderService->statusFinished('cili_nav');
                } else if ($orderService->isValidOrderStatusChange($order->getOrderStatus(), OrderService::$status_finished)) {
                    $orderService->statusFinished('cili_nav');
                } else if ($order->getOrderStatus() == OrderService::$status_finished) {
                    // do nothing - it is already finished
                } else {
                    $logger->alert(sprintf(
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
                $status = $order->getOrderStatus();

                $valid1 = $orderService->isValidOrderStatusChange($status, OrderService::$status_canceled);
                $valid2 = $orderService->isValidOrderStatusChangeWhenCompleted($status, OrderService::$status_canceled);

                if ($valid1 || $valid2) {
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

                break;
        }

        // Set delivery order ID if it is not set
        $deliveryOrderId = $order->getNavDeliveryOrder();
        if (empty($deliveryOrderId)) {
            // TODO pasitikrint lauko pavadinima
            $order->setNavDeliveryOrder($navOrder['Delivery Order No_']);
        }
    }

    /**
     * @param Order $order
     * @param string $driverId
     */
    public function setDriverFromNav(Order $order, $driverId)
    {
        $logger = $this->getContainer()->get('logger');
        $logger->alert('-- setDriverFromNav for order id: ' . $order->getId() . ' with Nav ID: ' . $driverId);
        $order->setNavDriverCode($driverId);
        $driver = $this->getDriverByNavId($driverId);

        if ($driver instanceof Driver) {
            $logger->alert('Driver found for order: ' . $order->getId() . ' with driver id: ' . $driver->getId());
            // Its a problematic orders. Please investigate it
            if (!$driver->getId()) {
                $logger->error(
                    '[Nav Status Sync] Order got Driver without ID. Order ID: ' . $order->getId() . ' Driver NAV ID: ' . $driverId
                );

                return;
            }
            $order->setDriver($driver);
        }
    }

    /**
     * @param Order[] $orders
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getNavIdsFromOrders($orders)
    {
        if (!is_array($orders)) {
            throw new \InvalidArgumentException('Not an array given. Can not extract Nav ids');
        }

        $navIds = [];

        foreach ($orders as $order) {
            if (!$order instanceof Order) {
                throw new \InvalidArgumentException('Got a non order object when extracting Nav ids');
            }
            // Skip orders from Nav
            if ($order->getOrderFromNav()) {
                continue;
            }

            $navOrderId = $this->getNavOrderId($order);
            if (is_numeric($navOrderId)) {
                $navIds[] = $this->getNavOrderId($order);
            } else {
                $this->container('logger')->error('NAVISION ORDER ID IS NOT NUMERIC! ' . $navOrderId);
                $this->container('logger')->error('NAVISION ORDER ID IS NOT NUMERIC! ' . $order->getId());
            }

        }

        return $navIds;
    }

    /**
     * @param int $oId
     */
    public function getOrderHeader($oId)
    {
        $orderId = $this->_orderIdModifier + (int)$oId;
        $result = $this->initSqlConn()->query('SELECT * FROM ' . $this->getHeaderTable() . " WHERE [Order No_] = '" . $orderId . "'");
        $result2 = $this->initSqlConn()->query('SELECT * FROM ' . $this->getLineTable() . " WHERE [Order No_] = '" . $orderId . "'");
        if ($result === false) {
            throw new \InvalidArgumentException('Wow Such fail.. Many problems... Such no results?');
        }
        echo "<pre>";
        while ($rowRez = $this->container->get('food.mssql')->fetchArray($result)) {
            var_dump($rowRez);
        }
        while ($rowRez = $this->container->get('food.mssql')->fetchArray($result2)) {
            var_dump($rowRez);
        }
        echo "\n\n----------------------------------";
    }

    /**
     * @param null $date
     *
     * @throws \InvalidArgumentException
     */
    public function syncDisDescription($date = null)
    {
        return; //temp disable
        $result = $this->initSqlConn()->query('SELECT [No_], [Description], [Search Description] FROM ' . $this->getItemsTable() . " WHERE LEN([No_]) > 3 AND [No_] LIKE 'DIS%'");
        if ($result === false) {
            throw new \InvalidArgumentException('Wow Such fail.. Many problems... Such no results?');
        }

        $counter = 0;
        while ($rowRez = $this->container->get('food.mssql')->fetchArray($result)) {
            $query = "REPLACE INTO nav_items VALUES('" . addslashes($rowRez['No_']) . "', '" . addslashes(iconv('cp1257', 'utf-8', $rowRez['Description'])) . "', '" . addslashes(iconv('cp1257', 'utf-8', $rowRez['Search Description'])) . "')";
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
                return 'CC_SEB';
            case 'paysera':
                return 'BANK_PAYSE';
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
     * @param string|null $dateShift
     *
     * @return array
     */
    public function getNewNonFoodoutOrders($dateShift = null)
    {
        if (empty($dateShift)) {
            $dateShift = '-3 hour';
        }
        $theDate = date('Y-m-d', strtotime($dateShift));
        $theTime = '1754-01-01 ' . date("H:i:s", strtotime($dateShift));

        // For weekend recoveries (1 day solid recovery)
        if (date('d', strtotime('-1 day')) > date('d', strtotime($dateShift))) {
            $theOtherDate = date('Y-m-d');
            $theInnerDate = date('Y-m-d', strtotime('-1 day'));
            $datePart = sprintf(
                "(
                (
                dOrder.[Date Created] >= '%s'
                AND dOrder.[Time Created] >= '%s'
                ) OR (
                dOrder.[Date Created] >= '%s'
                AND dOrder.[Time Created] >= '1754-01-01 00:00:00'
                ) OR (
                dOrder.[Date Created] >= '%s'
                AND dOrder.[Time Created] >= '1754-01-01 00:00:00'
                )
                )",
                $theDate,
                $theTime,
                $theInnerDate,
                $theOtherDate
            );
        } elseif (date('d') > date('d', strtotime($dateShift))) {
            $theOtherDate = date('Y-m-d');
            $datePart = sprintf(
                "(
                (
                dOrder.[Date Created] >= '%s'
                AND dOrder.[Time Created] >= '%s'
                ) OR (
                dOrder.[Date Created] >= '%s'
                AND dOrder.[Time Created] >= '1754-01-01 00:00:00'
                )
                )",
                $theDate,
                $theTime,
                $theOtherDate
            );
        } else {
            $datePart = sprintf(
                "dOrder.[Date Created] >= '%s'
                AND dOrder.[Time Created] >= '%s'",
                $theDate,
                $theTime
            );
        }

        $query = sprintf(
            "SELECT
                dOrder.[Order No_] As [OrderNo],
                dOrder.[Phone No_],
                dOrder.[Date Created],
                dOrder.[Time Created],
                dOrder.[Order Date],
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
                        AND pSumTrans.[Deleted] = 0
                        AND pSumTrans.[Entry Status] = 0
                ) AS OrderSum,
                (
                 SELECT TOP 1
                    oStat.[Status]
                 FROM %s oStat
                 WHERE
                    [ORDER No_] = dOrder.[Order No_]
                 ORDER BY [TIME] DESC
                 ) AS OrderStatus,
                 cCustomer.[Name] AS CustomerName,
                 cCustomer.[Address] AS CustomerAddress,
                 cCustomer.[City] AS CustomerCity,
                 cCustomer.[VAT Registration No_] AS CustomerVatNo,
                 cCustomer.[E-mail] AS CustomerEmail,
                 cCustomer.[Registration No_] AS CustomerRegNo
            FROM %s dOrder
            LEFT JOIN %s pTrans ON pTrans.[Receipt No_] = dOrder.[Order No_]
            LEFT JOIN %s cContract ON cContract.[Contract Register No_] = dOrder.[Contract Register No_]
            LEFT JOIN %s cCustomer ON cCustomer.[No_] = cContract.[Customer No_]
            WHERE
                %s
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
            $this->getContractTable(),
            $this->getCustomerTable(),
            $datePart,
            "'Vilnius', 'Kaunas', 'Klaipeda','Ryga'" //todo MULTI-L a čia viskas ok?
        );

        $this->container->get("logger")->alert('Nav import query: ' . $query);

        $result = $this->initSqlConn()->query($query);
        if ($result === false) {
            return [];
        }
        $return = [];
        while ($rowRez = $this->container->get('food.mssql')->fetchArray($result)) {
            $return[$rowRez['OrderNo']] = $rowRez;
        }

        return $return;
    }

    /**
     * @param string $chain
     * @param string $restaurantNo
     *
     * @return PlacePoint
     */
    public function getLocalPlacePoint($chain, $restaurantNo)
    {
        $repo = $this->getContainer()->get('doctrine')->getRepository('FoodDishesBundle:PlacePoint');
        $pPoint = $repo->findOneBy(['internal_code' => $restaurantNo, 'parentId' => null]);

        if (!$pPoint instanceof PlacePoint || $pPoint->getId() == '') {
            return false;
        }

        if ($pPoint->getPlace()->getChain() != $chain) {
            $this->getContainer()->get('logger')
                ->alert(sprintf(
                    'Found placePoint for restaurant no "%s" with id: %d but chain from Nav "%s" does not match Place chain "%s". The point will still be used',
                    $restaurantNo,
                    $pPoint->getId(),
                    $chain,
                    $pPoint->getPlace()->getChain()
                ));
        }

        if ($pPoint->getParentId() !== null) {
            $this->getContainer()->get('logger')
                ->alert(sprintf(
                    'Found placePoint for restaurant no "%s" with id: %d but it has parent. Parent will be selected',
                    $restaurantNo,
                    $pPoint->getId(),
                    $chain,
                    $pPoint->getPlace()->getChain()
                ));

            $pPoint = $repo->find($pPoint->getParentId());
        }

        return $pPoint;
    }

    /**
     * @param string $navDriverId
     *
     * @return mixed
     */
    public function getDriverByNavId($navDriverId)
    {
        $driverRepo = $this->getContainer()->get('doctrine')->getRepository('FoodAppBundle:Driver');

        $driver = $driverRepo->findOneBy([
            'extId' => $navDriverId,
        ]);

        if (empty($driver) || !($driver instanceof Driver) || !$driver->getId()) {
            $nameParts = str_split($navDriverId, 3);

            $driverQueryBuilder = $driverRepo->createQueryBuilder('d')
                ->where('d.name LIKE :first_name_part')
                ->andWhere('d.name LIKE :second_name_part')
                ->setParameters([
                    'first_name_part' => $nameParts[0] . "%",
                    'second_name_part' => "% " . $nameParts[1] . "%"
                ])
                ->orderBy('d.id', 'ASC')
                ->setMaxResults(1);

            $driver = $driverQueryBuilder->getQuery()->getResult();

            if (!empty($driver)) {
                return $driver[0];
            } else {
                return false;
            }
        }

        return $driver;
    }

    public function sendNavInvoice($order, $invoice = null)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        // call SOAP
        $success = $this->createInvoice($order);

        // create sent/error entry for this nav invoice to send
        if (is_null($invoice)) {
            $invoiceToSendNavOnly = new InvoiceToSendNavOnly();
            $invoiceToSendNavOnly->setOrder($order)
                ->setDateAdded(new \DateTime('now'))
                ->setDateSent(new \DateTime('now'));

            $em->persist($invoiceToSendNavOnly);
        } else {
            $invoiceToSendNavOnly = $invoice;
            $invoiceToSendNavOnly->setDateSent(new \DateTime('now'));
        }

        if ($success) {
            $invoiceToSendNavOnly->markSent();
            $em->flush();
        } else {
            $invoiceToSendNavOnly->markError();

            $newInvoice = clone $invoiceToSendNavOnly;
            $newInvoice->markUnsent();

            $em->persist($newInvoice);
            $em->flush();
        }

        return $success;
    }

    public function selectNavInvoice(Order $order)
    {
        if (empty($order)) {
            return false;
        }

        $mssql = $this->container->get('food.mssql');

        $query = '
            SELECT TOP 1
                i.[Order ID],
                i.[Invoice No_],
                i.[Restaurant ID],
                i.[Restaurant Name],
                i.[Driver ID],
                i.[Client Name],
                i.[Registration No_],
                i.[VAT Registration No_],
                i.[Delivery Address],
                i.[City],
                i.[Payment Method Type],
                i.[Payment Method Code],
                i.[Food Amount With VAT],
                i.[Alcohol Amount With VAT],
                i.[Delivery Amount With VAT]
            FROM %s i
            WHERE i.[Order ID] = %s';
        $query = sprintf($query, $this->getInvoiceTable(), $order->getId());

        $result = $this->initTestSqlConn()->query($query);

        if ($result === false) {
            return null;
        }

        $row = (array)$mssql->fetchArray($result);

        $resultList = [];

        foreach ($row as $key => $value) {
            if (is_numeric($key)) {
                continue;
            }

            $resultList[$key] = iconv('cp1257', 'utf-8', $value);
        }

        return $resultList;
    }

    public function compareNavInvoiceWithOrder(array $navInvoice, Order $order)
    {
        $result = ['sameData' => [], 'orderHas' => [], 'invoiceHas' => []];

        if (empty($navInvoice) || empty($order)) {
            return $result;
        }

        $navInvoice['Food Amount With VAT'] = round($navInvoice['Food Amount With VAT'], 2);
        $navInvoice['Alcohol Amount With VAT'] = round($navInvoice['Alcohol Amount With VAT'], 2);
        $navInvoice['Delivery Amount With VAT'] = round($navInvoice['Delivery Amount With VAT'], 2);

        $o = \Maybe($order);

        // client name depends on if client is a company or not
        $clientName = $o->getCompany()->val(false)
            ? $o->getCompanyName()->val('')
            : sprintf('%s %s',
                $o->getUser()->getFirstname()->val(''),
                $o->getUser()->getLastname()->val(''));

        $orderProperties = [
            'Order ID' => $o->getId()->val(''),
            'Invoice No_' => $o->getSfSeries()->val('') . $o->getSfNumber()->val(''),
            'Restaurant ID' => $o->getPlace()->getId()->val(''),
            'Restaurant Name' => $o->getPlaceName()->val(''),
            'Driver ID' => $o->getDriver()->getId()->val(''),
            'Client Name' => $this->normalizeStringForNav($clientName),
            'Registration No_' => $o->getCompanyCode()->val(''),
            'VAT Registration No_' => $o->getVatCode()->val(''),
            'Delivery Address' => $this->normalizeStringForNav($o->getAddressId()->getAddress()->val('')),
            'City' => $o->getPlacePointCity()->val(''),
            'Payment Method Type' => $o->getPaymentMethod()->val(''),
            'Payment Method Code' => $o->getPaymentMethod()->val('') == 'local'
                ? $o->getDriver()->getId()->val('')
                : $this->convertPaymentType($o->getPaymentMethod()->val('')),
            'Food Amount With VAT' => $o->getTotal()->val(0.0) - $o->getDeliveryPriceForNav()->val(0.0),
            'Alcohol Amount With VAT' => 0.0,
            'Delivery Amount With VAT' => $o->getDeliveryPriceForNav()->val('')
        ];

        $result = [
            'sameData' => array_intersect_assoc($navInvoice, $orderProperties),
            'invoiceHas' => array_diff_assoc($navInvoice, $orderProperties),
            'orderHas' => array_diff_assoc($orderProperties, $navInvoice)
        ];

        return $result;
    }

    public function updateNavInvoice(Order $order, array $data)
    {
        if (empty($order) || empty($data)) {
            return false;
        }

        $cols = [];

        foreach ($data as $key => $value) {
            $cols[] = sprintf("[%s] = '%s'", $key, str_replace('\'', '\\\'', $value));
        }

        $cols[] = sprintf('[ReplicationCounter] = (SELECT ISNULL(MAX(ReplicationCounter),0) FROM %s) + 1',
            $this->getInvoiceTable());

        $query = 'UPDATE %s SET %s WHERE [Order ID] = %s';
        $query = sprintf($query,
            $this->getInvoiceTable(),
            implode(', ', $cols),
            $order->getId());

        $result = $this->initTestSqlConn()->query($query);

        return $result;
    }

    /**
     * Get driver code from NAV.
     *
     * @param  int $id orders.nav_delivery_order
     *
     * @return string
     */
    public function getNavDriverCode($id)
    {
        $mssql = $this->container->get('food.mssql');

        $query = '
            SELECT TOP 1 [Driver ID]
            FROM %s
            WHERE [Order No_] = \'%s\'
        ';
        $query = sprintf($query, $this->getDeliveryOrderTable(), $id);

        $result = $this->initSqlConn()->query($query);

        if (empty($result)) {
            return null;
        }

        $row = (array)$mssql->fetchArray($result);

        return !empty($row[0]) ? iconv('cp1257', 'utf-8', $row[0]) : null;
    }

    public function normalizeStringForNav($value)
    {
        return trim(preg_replace('#\s{2,}#', ' ', $value));
    }

    public function getPostedOrders($from, $to)
    {
        $mssql = $this->container->get('food.mssql');

        $query = "
            SELECT
                [Order No_] AS order_no,
                [Order Date] AS order_date,
                [Tender Type] AS tender_type,
                REPLACE([Amount Including VAT],',','.') AS total,
                REPLACE([Delivery Tax Cash Amount],',','.') AS delivery_total,
                REPLACE([Delivery Tax Bank Card Amount],',','.') AS delivery_cc_amount
            FROM %s
            WHERE
                [Order Date] >= '%s' AND
                [Order Date] <= '%s' AND
                [Sales Type] = 'DELIVERY'";
        $query = sprintf($query, $this->getPostedDeliveryOrdersTable(), $from, $to);

        $result = $this->initSqlConn()->query($query);

        if (empty($result)) {
            return null;
        }

        $resultList = [];

        while ($row = $mssql->fetchArray($result)) {
            $newRow = [];

            foreach ($row as $key => $value) {
                if (is_numeric($key)) {
                    continue;
                }

                $newRow[$key] = $value;
            }

            $resultList[] = $newRow;
        }

        return $resultList;
    }

    /**
     * Deletes invoice from replication table in NAV so a new one can be imported, bitches...
     *
     * @param string $sfNumber
     */
    public function deleteInvoiceFromNav($sfNumber)
    {
        $query = "
            DELETE FROM %s WHERE [Invoice No_] = '%s'";
        $query = sprintf($query, $this->getInvoiceTable(), $sfNumber);

        $this->initSqlConn()->query($query);
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function isMissingFromWebHeader($order)
    {
        $existsInHeader = true;
        $existsInLines = true;
        $navId = $this->getNavOrderId($order);

        $query = "
            SELECT
                [Order No_] AS order_no
            FROM %s
            WHERE
                [Order No_] >= '%s'";
        $query = sprintf(
            $query,
            $this->getHeaderTable(),
            $navId
        );

        $result = $this->initSqlConn()->query($query);

        if (empty($result)) {
            $existsInHeader = false;
        }

        $query = "
            SELECT
                [Order No_] AS order_no
            FROM %s
            WHERE
                [Order No_] >= '%s'";
        $query = sprintf(
            $query,
            $this->getLineTable(),
            $navId
        );

        $result = $this->initSqlConn()->query($query);

        if (empty($result)) {
            $existsInLines = false;
        }

        if (!$existsInHeader || !$existsInLines) {
            return false;
        }

        return true;
    }

    public function getOrdersWithoutPlacePointData($daysBack)
    {
        $daysBack = intval($daysBack);
        $query = "select [Order ID] from " . $this->getInvoiceTable() . " WHERE [Production Point Address] = '' AND ([Order Date] = '" . date("Y-m-d", strtotime('-' . $daysBack . ' day')) . "' OR [Order Date] = '" . date("Y-m-d", strtotime('-' . ($daysBack + 1) . ' day')) . "') ";
        $sqlSS = $this->initSqlConn()->query($query);
        $returner = [];
        if ($sqlSS) {
            while ($row = $this->container->get('food.mssql')->fetchArray($sqlSS)) {
                $returner[] = $row['Order ID'];
            }
        }

        return $returner;
    }

    /**
     * @param $orderId
     */
    public function updateOrderPlacePointInfo($orderId, $exceute = false)
    {
        $order = $this->getContainer()->get('doctrine')->getRepository('FoodOrderBundle:Order')->find($orderId);
        if ($order instanceof Order) {
            $query = "UPDATE " . $this->getInvoiceTable() . "
                SET
                    [Production Point Address]='" . $order->getPlacePoint()->getAddress() . "',
                    [Production Point Code]='" . $order->getPlacePoint()->getInternalCode() . "',
                    [ReplicationCounter] = (SELECT ISNULL(MAX(ReplicationCounter),0) FROM " . $this->getInvoiceTable() . ") + 1
                WHERE [Order ID] = " . $orderId;
            if (!$exceute) {
                echo $query . "\n";
            } else {
                $sqlSS = $this->initSqlConn()->query($query);
                if ($sqlSS) {
                    echo $orderId . " - success";
                } else {
                    echo $orderId . " - fail";
                }
            }
        }
    }

    public function getErrorFromNav($orderId)
    {

        $query = sprintf(
            "SELECT * FROM %s WHERE [Order No_] = %d", $this->getMessagesTable(), $orderId
        );

        $result = $this->initSqlConn()->query($query);
        if ($result === false) {
            return [];
        }
        $return = [];
        while ($rowRez = $this->container->get('food.mssql')->fetchAssoc($result)) {
            $return = $rowRez;
        }

        return implode(',',$return);
    }
}
