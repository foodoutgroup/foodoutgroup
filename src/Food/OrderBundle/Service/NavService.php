<?php

namespace Food\OrderBundle\Service;

use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderDetails;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\OrderBundle\Common;

class NavService extends ContainerAware
{

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

    //private $headerTable = 'prototipas6.dbo."PROTOTIPAS Skambuciu Centras$Web ORDER Header"';

    //private $lineTable = 'prototipas6.dbo."PROTOTIPAS Skambuciu Centras$Web ORDER Lines"';

    private $headerTable = 'skamb_centras.dbo."Čilija Skambučių Centras$Web ORDER Header"';

    private $lineTable = 'skamb_centras.dbo."Čilija Skambučių Centras$Web ORDER Lines"';

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
     * @return false|resource
     */
    public function getConnection()
    {
        if ($this->conn == null) {
            $serverName = "213.190.40.38, 5566"; //serverName\instanceName, portNumber (default is 1433)
            //$connectionInfo = array( "Database"=>"prototipas6", "UID"=>"fo_order", "PWD"=>"peH=waGe?zoOs69");
            //$connectionInfo = array( "Database"=>"skamb_centras", "UID"=>"nas", "PWD"=>"c1l1j@");
            $connectionInfo = array( "Database"=>"skamb_centras", "UID"=>"fo_order", "PWD"=>"peH=waGe?zoOs69");
            $this->conn = sqlsrv_connect( $serverName, $connectionInfo);

            if( $this->conn === false ) {
                die( print_r( sqlsrv_errors(), true));
            }
        }
        return $this->conn;
    }

    public function getLastOrders()
    {
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
            'Order Time' => '1754-01-01 '.date("H:i:s"),
            'Takeout Time' => date("Y-m-d H:i:s", strtotime('+20 minutes')),
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
            'keys' => '"'.implode('","', $arrayKeys).'"',
            'values' => "'".implode("','", $values)."'",
        );
    }

    private function generateQueryPartNoQuotes($data)
    {
        $arrayKeys = array_keys($data);
        $values = array_values($data);
        return array(
            'keys' => '"'.implode('","', $arrayKeys).'"',
            'values' => implode(',', $values)
        );
    }

    public function putTheOrderToTheNAV(Order $order)
    {
        echo "<pre>";
        $orderNewId = $this->getNavOrderId($order);

        $target = $order->getAddressId()->getAddress();
        preg_match('/(([0-9]{1,3})[-|\s]{0,4}([0-9]{0,3}))$/i', $target, $errz);
        $street = trim(str_replace($errz[0], '', $target));
        $houseNr = (!empty($errz[2]) ? $errz[2] : '');
        $flatNr = (!empty($errz[3]) ? $errz[3] : '');

        $orderRow = $this->container->get('doctrine')->getRepository('FoodAppBundle:Streets')->findOneBy(
            array(
                'name' => $street,
                'numberFrom' => $houseNr,
                'deliveryRegion' => $order->getAddressId()->getCity()
            )
        );


        echo $order->getPlacePoint()->getAddress();
        $dataToPut = array(
            'Order No_' => $orderNewId,
            'Phone' => str_replace('370', '8', $order->getUser()->getPhone()),
            'ZipCode' => $orderRow->getZipCode(),
            'City' => $order->getAddressId()->getCity(),
            'Street' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? iconv('utf-8', 'cp1257',$orderRow->getStreetName()): ''),
            'Street No_' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? $orderRow->getNumberFrom(): ''),
            'Floor' => '',
            'Grid' => $orderRow->getGrid(),
            'Chain' => $order->getPlace()->getChain(),
            'Name' => 'FO:'.$order->getUser()->getNameForOrder(),
            'Delivery Type' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? 1 : 4),
            'Restaurant No_' => '', //$order->getPlacePoint()->getInternalCode(),
            'Order Date' => $order->getOrderDate()->format("Y-m-d"),
            'Order Time' => '1754-01-01 '.$order->getOrderDate()->format("H:i:s"),
            'Takeout Time' => $order->getDeliveryTime()->format("Y-m-d H:i:s"),
            'Directions' => 'NEGAMINTI.TEST.'.iconv('utf-8', 'cp1257',$order->getComment()),
            'Discount Card No_' => '',
            'Order Status' => 4,
            'Delivery Order No_' => '',
            'Error Description' => '',
            'Flat No_' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? $flatNr: ''),
            'Entrance Code' => '',
            'Region Code' => $orderRow->getDeliveryRegion(),
            'Delivery Status' => 12,
            'In Use By User' => '',
            'Loyalty Card No_' => '',
            'Order with Alcohol' => '0'
        );
        $queryPart = $this->generateQueryPart($dataToPut);

        $query = 'INSERT INTO '.iconv('utf-8', 'cp1257',$this->getHeaderTable()).' ('.$queryPart['keys'].') VALUES('.$queryPart['values'].')';
        echo $query."\n\n";

        $rez = sqlsrv_query ( $this->getConnection() , $query);
        if( $rez === false) {
            echo "\n\n\nKLAIDA:\n";
            die( print_r( sqlsrv_errors(), true) );
        }
        $this->_processLines($order, $orderNewId);
    }

    private function _processLines(Order $order, $orderNewId)
    {
        echo "\nIN PROCESS LINES\n-----------------\n";
        echo "SIZE: ".sizeof($order->getDetails())."\n";
        foreach ($order->getDetails() as $key=>$detail) {
            echo "Process: ".$key."\n";
            $this->_processLine($detail, $orderNewId, ($key + 1));
        }
    }

    private function _processLine(OrderDetails $detail, $orderNewId, $key)
    {
        $dataToPut = array(
            'Order No_' => $orderNewId,
            'Line No_' => $key,
            'Entry Type' => 0,
            'No_' => "'".$detail->getDishSizeCode()."'",
            'Description' => "'".substr(iconv('utf-8', 'cp1257', $detail->getDishName()), 0, 29)."'",
            'Quantity' => $detail->getQuantity(),
            'Price' => $detail->getPrice(), // @todo test the price. Kaip gula. Total ar ne.
            'Parent Line' => 0, // @todo kaip optionsai sudedami. ar prie pirmines kainos ar ne
            'Amount' => $detail->getPrice() * $detail->getQuantity(),// @todo test the price. Kaip gula. Total ar ne.
            'Discount Amount' => 0,
            'Payment' => $detail->getPrice() * $detail->getQuantity(),
            'Value' => "''"
        );

        $queryPart = $this->generateQueryPartNoQuotes($dataToPut);

        $query = 'INSERT INTO '.iconv('utf-8', 'cp1257',$this->getLineTable()).' ('.$queryPart['keys'].') VALUES('.$queryPart['values'].')';
        echo $query."\n";
        $rez = sqlsrv_query ( $this->getConnection() , $query);
        if( $rez === false) {
            die( print_r( sqlsrv_errors(), true) );
        }
    }

    public function getNavOrderId(Order $order)
    {
        return $this->_orderIdModifier + (int)$order->getId();

    }

    private function getWSConnection()
    {

        $clientUrl = "http://213.190.40.38:7059/DynamicsNAV/WS/Codeunit/WEB_Service2?wsdl";
        //$clientUrl2 = "http://213.190.40.38:7059/DynamicsNAV/WS/PROTOTIPAS%20Skambuciu%20Centras/Codeunit/WEB_Service2";
        $clientUrl2 = "http://213.190.40.38:7055/DynamicsNAV/WS/Čilija Skambučių Centras/Codeunit/WEB_Service2";

        stream_wrapper_unregister('http');
        stream_wrapper_register('http', '\Food\OrderBundle\Common\FoNTLMStream') or die("Failed to register protocol");
        //$url = "http://213.190.40.38:7059/DynamicsNAV/WS/PROTOTIPAS Skambuciu Centras/Codeunit/WEB_Service2";
        $url = $clientUrl2;
        $options = array('trace'=>1, 'login' =>'CILIJA\fo_order', 'password' => 'peH=waGe?zoOs69');
        //$options = array('login' =>'CILIJA\nas', 'password' => 'c1l1j@');
        $client = new Common\FoNTLMSoapClient($url, $options);
        stream_wrapper_restore('http');
        return $client;
    }

    public function updatePricesNAV(Order $order)
    {
        $orderId = $this->getNavOrderId($order);
        var_dump($orderId);
        $client = $this->getWSConnection();
        $return = $client->UpdatePrices(array('pInt' =>(int)$orderId));
        return $return;
    }

    public function processOrderNAV(Order $order)
    {
        $orderId = $this->getNavOrderId($order);

        $query = 'UPDATE '.iconv('utf-8', 'cp1257',$this->getHeaderTable()).' SET [Order Status]=0, [Delivery Status]=0 WHERE [Order No_] = '.$orderId;

        $rez = sqlsrv_query ( $this->getConnection() , $query);
        if( $rez === false) {
            die( print_r( sqlsrv_errors(), true) );
        }

        $client = $this->getWSConnection();
        $return = $client->ProcessOrder(array('pInt' =>(int)$orderId));
        return $return;
    }
}
?>