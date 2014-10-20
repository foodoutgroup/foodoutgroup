<?php

namespace Food\OrderBundle\Service;

use Food\CartBundle\Entity\Cart;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderDetails;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\OrderBundle\Common;
use Food\OrderBundle\Service\NavService\OrderDataForNavDecorator;

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

    private $headerTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Web ORDER Header]';

    private $lineTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Web ORDER Lines]';

    private $orderTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$FoodOut Order]';

    private $messagesTable = '[skamb_centras].[dbo].[Čilija Skambučių Centras$Web Order Messages]';


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
        $sqlSS->init(
            '213.190.40.38',
            5566,
            'skamb_centras',
            'fo_order',
            'peH=waGe?zoOs69'
        );
        return $sqlSS;
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
        $sqlSS->init(
            '213.190.40.38',
            5566,
            'prototipas6',
            'Neotest',
            'NewNeo@123'
        );
        return $sqlSS;
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
            'Takeout Time' => date("Y-m-d H:i:s", (strtotime('+20 minutes') - (3600 * 3))),
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



        $orderDate = $order->getOrderDate();
        $orderDate->sub(new \DateInterval('P0DT3H'));
        $deliveryDate = $order->getDeliveryTime();
        $deliveryDate->sub(new \DateInterval('P0DT3H'));
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
            'Name' => 'FO:'.$order->getUser()->getNameForOrder(),
            'Delivery Type' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? 1 : 4),
            //'Restaurant No_' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? '':  $order->getPlacePoint()->getInternalCode()),
            'Restaurant No_' => $order->getPlacePoint()->getInternalCode(),
            'Order Date' => $orderDate->format("Y-m-d"),
            'Order Time' => '1754-01-01 '.$orderDate->format("H:i:s"),
            'Takeout Time' => $deliveryDate->format("Y-m-d H:i:s"),
            'Directions' => $order->getComment(),
            'Discount Card No_' => '',
            'Order Status' => 4,
            'Delivery Order No_' => '',
            'Error Description' => '',
            'Flat No_' => $flatNr, //($order->getDeliveryType() == OrderService::$deliveryDeliver ? $flatNr: ''),
            'Entrance Code' => '',
            'Region Code' => mb_strtoupper($order->getPlacePoint()->getCity()), //$order->getDeliveryType() == OrderService::$deliveryDeliver ? $orderRow->getDeliveryRegion() : ''),
            'Delivery Status' => 12,
            'In Use By User' => '',
            'Loyalty Card No_' => '',
            'Order with Alcohol' => '0'
        );
        $queryPart = $this->generateQueryPart($dataToPut);
        $query = 'INSERT INTO '.$this->getHeaderTable().' ('.$queryPart['keys'].') VALUES('.$queryPart['values'].')';
        $sqlSS = $this->initSqlConn()->query($query);

        $this->_processLines($order, $orderNewId);
    }

    private function _processLines(Order $order, $orderNewId)
    {
        foreach ($order->getDetails() as $key=>$detail) {
            $this->_processLine($detail, $orderNewId, ($key + 1));
        }
    }

    private function _processLine(OrderDetails $detail, $orderNewId, $key)
    {
        $this->container->get('doctrine')->getManager()->refresh($detail);
        $code = $detail->getDishSizeCode();
        if (empty($code)) {
            $detailOptions = $detail->getOptions();
            if (!empty($detailOptions)) {
                $code = $detailOptions[0]->getDishOptionCode();
            }
        }

        $dataToPut = array(
            'Order No_' => $orderNewId,
            'Line No_' => $key,
            'Entry Type' => 0,
            'No_' => "'".$code."'",
            'Description' => "'".substr($detail->getDishName(), 0, 29)."'",
            'Quantity' => $detail->getQuantity(),
            'Price' => $detail->getPrice(), // @todo test the price. Kaip gula. Total ar ne.
            'Parent Line' => 0, // @todo kaip optionsai sudedami. ar prie pirmines kainos ar ne
            'Amount' => $detail->getPrice() * $detail->getQuantity(),// @todo test the price. Kaip gula. Total ar ne.
            'Discount Amount' => 0,
            'Payment' => $detail->getPrice() * $detail->getQuantity(),
            'Value' => "''"
        );

        $queryPart = $this->generateQueryPartNoQuotes($dataToPut);

        $query = 'INSERT INTO '.$this->getLineTable().' ('.$queryPart['keys'].') VALUES('.$queryPart['values'].')';
        $sqlSS = $this->initSqlConn()->query($query);
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
        //$clientUrl2 = "http://213.190.40.38:7059/DynamicsNAV/WS/PROTOTIPAS%20Skambuciu%20Centras/Codeunit/WEB_Service2";
        $clientUrl2 = "http://213.190.40.38:7055/DynamicsNAV/WS/Čilija%20Skambučių%20Centras/Codeunit/WEB_Service2";

        stream_wrapper_unregister('http');
        stream_wrapper_register('http', '\Food\OrderBundle\Common\FoNTLMStream') or die("Failed to register protocol");

        $url = $clientUrl2;
        //$options = array('trace'=>1, 'login' =>'CILIJA\fo_order', 'password' => 'peH=waGe?zoOs69');
        $options = array('trace'=>1, 'cache_wsdl' => WSDL_CACHE_NONE, 'login' =>'CILIJA\nas', 'password' => 'c1l1j@');
        $client = new Common\FoNTLMSoapClient($url, $options);
        stream_wrapper_restore('http');
        return $client;
    }

    public function updatePricesNAV(Order $order)
    {
        $orderId = $this->getNavOrderId($order);
        $client = $this->getWSConnection();
        $return = $client->FoodOutUpdatePrices(array('pInt' =>(int)$orderId));
        return $return;
    }

    public function processOrderNAV(Order $order)
    {
        $orderId = $this->getNavOrderId($order);

        $query = 'UPDATE '.$this->getHeaderTable().' SET [Order Status]=0, [Delivery Status]=0 WHERE [Order No_] = '.$orderId;

        $sqlSS = $this->initSqlConn()->query($query);

        $client = $this->getWSConnection();
        $return = $client->FoodOutProcessOrder(array('pInt' =>(int)$orderId));
        return $return;
    }

    /**
     * @param String $phone
     * @param PlacePoint $restaurant
     * @param String $orderDate
     * @param String $orderTime
     * @param String $deliveryType
     * @param Cart[] $dishes
     */
    public function validateCartInNav($phone, $restaurant, $orderDate, $orderTime, $deliveryType, $dishes)
    {
        $rcCode = $restaurant->getInternalCode();

        $requestData = array(
            array('Lines' => array())
        );
        $requestXml = "<Phone>".str_replace("370", "8", $phone)."</Phone>\n";
        $requestXml.= "<RestaurantNo>".$rcCode."</RestaurantNo>\n";
        $requestXml.= "<OrderDate>".str_replace("-", ".", $orderDate)."</OrderDate>\n";
        $requestXml.= "<OrderTime>".$orderTime."</OrderTime>\n";
        $requestXml.= "<DeliveryType>".($deliveryType == OrderService::$deliveryDeliver ? 1: 4)."</DeliveryType>\n";
        $requestXml.= "<Lines>\n";

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
            $disFromOptions = false;
            if (empty($code)) {
                $detailOptions = $cart->getOptions();
                if (!empty($detailOptions)) {
                    $code = $detailOptions[0]->getDishOptionId()->getCode();
                    $disFromOptions = true;
                }
            }

            $requestXml.= "\t<Line>\n";
            $requestXml.= "\t\t<LineNo>".$lineNo."</LineNo>\n";
            $requestXml.= "\t\t<ParentLineNo>0</ParentLineNo>\n";
            $requestXml.= "\t\t<EntryType>0</EntryType>\n";
            $requestXml.= "\t\t<ItemNo>".$code."</ItemNo>\n";
            $requestXml.= "\t\t<Description>za</Description>\n";
            $requestXml.= "\t\t<Quantity>".$cart->getQuantity()."</Quantity>\n";
            $requestXml.= "\t\t<Price>".$cart->getDishSizeId()->getPrice()."</Price>\n";
            $requestXml.= "\t\t<Amount>".$cart->getDishSizeId()->getPrice() * $cart->getQuantity()."</Amount>\n";
            $requestXml.= "\t</Line>\n";


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

            $origLineNo = $lineNo;
            foreach ( $detailOptions = $cart->getOptions() as $optKey => $option) {
                if ($disFromOptions) {
                    continue;
                } else {
                    $optionCode = $option->getDishOptionId()->getCode();
                    $description = "";
                    if(strpos($optionCode, '--') !== false) {
                        list($preCode, $posCode) = explode("--", $optionCode);
                        $optionCode = $preCode;
                        $description = $posCode;
                    } else {
                        $description = $option->getDishOptionId()->getName();
                    }
                    $lineNo = $lineNo + 1;
                    $requestXml.= "\t<Line>\n";
                    $requestXml.= "\t\t<LineNo>".$lineNo."</LineNo>\n";
                    $requestXml.= "\t\t<ParentLineNo>".$origLineNo."</ParentLineNo>\n";
                    $requestXml.= "\t\t<EntryType>1</EntryType>\n";
                    $requestXml.= "\t\t<ItemNo>".$optionCode."</ItemNo>\n";
                    $requestXml.= "\t\t<Description></Description>\n";
                    $requestXml.= "\t\t<Quantity>".$cart->getQuantity()."</Quantity>\n";
                    $requestXml.= "\t\t<Price>".$option->getDishOptionId()->getPrice()."</Price>\n";
                    $requestXml.= "\t\t<Amount>".$option->getDishOptionId()->getPrice() * $cart->getQuantity()."</Amount>\n";
                    $requestXml.= "\t</Line>\n";


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
        $requestXml.= "</Lines>\n";

        $requestXml = iconv('utf-8', 'cp1257', $requestXml);
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
        echo "<pre>";
        var_dump($returner);
        var_dump($requestData);
        var_dump($response);
        echo "</pre>";
        return $returner;
    }

    /**
     * @param Order[] $orders
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function getRecentNavOrders($orders)
    {
        $orderIds = $this->getNavIdsFromOrders($orders);

        $query = sprintf(
            'SELECT [Order No_], [Order Status], [Delivery Status]
            FROM %s
            WHERE
                [Order No_] IN (%s)
            ORDER BY [Order No_] DESC',
            $this->getHeaderTable(),
            implode(', ', $orderIds)
        );

        $result = $this->initSqlConn()->query($query);
        if( $result === false) {
            throw new \InvalidArgumentException('No wanted orders found in Nav. How is that even possible?');
        }

        $return = array();
        while ($rowRez = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            $return[$this->getOrderIdFromNavId($rowRez['Order No_'])] = $rowRez;
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
                if ($order->getDeliveryType() == OrderService::$deliveryPickup
                    && $orderService->isValidOrderStatusChange($order->getOrderStatus(), OrderService::$status_completed)) {
                        $orderService->statusCompleted('cili_nav');
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
                // do nothing
                break;
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

            $navIds[] = $this->getNavOrderId($order);
        }

        return $navIds;
    }
}
