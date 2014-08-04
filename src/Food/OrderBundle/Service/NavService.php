<?php

namespace Food\OrderBundle\Service;

use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderDetails;
use Symfony\Component\DependencyInjection\ContainerAware;

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

    private $headerTable = 'prototipas6.dbo."PROTOTIPAS Skambuciu Centras$Web ORDER Header"';

    private $lineTable = 'prototipas6.dbo."PROTOTIPAS Skambuciu Centras$Web ORDER Lines"';

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
            $connectionInfo = array( "Database"=>"prototipas6", "UID"=>"fo_order", "PWD"=>"peH=waGe?zoOs69");
            $this->conn = sqlsrv_connect( $serverName, $connectionInfo);

            if( $this->conn === false ) {
                die( print_r( sqlsrv_errors(), true));
            }
        }
        return $this->conn;
    }

    public function getLastOrders()
    {
        $rez = sqlsrv_query ( $this->getConnection() , 'SELECT TOP 10 * FROM '.$this->getHeaderTable().' ORDER BY timestamp DESC');

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
            'Order No_' => '10002',
            'Phone' => '37061544121',
            'ZipCode' => '03115',
            'City' => '',
            'Street' => '',
            'Street No_' => '',
            'Floor' => '',
            'Grid' => '',
            'Chain' => 'CILI',
            'Name' => 'FoodOut',
            'Delivery Type' => '1',
            'Restaurant No_' => '64',
            'Order Date' => date("Y-m-d"),
            'Order Time' => date("H:i:s"),
            'Takeout Time' => date("H:i:s", strtotime('+20 minutes')),
            'Directions' => '',
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

    public function putTheOrderToTheNAV(Order $order)
    {
        $orderNewId = $this->getNavOrderId($order);
        $dataToPut = array(
            'Order No_' => $orderNewId,
            'Phone' => $order->getUser()->getPhone(),
            'ZipCode' => '',
            'City' => $order->getAddressId()->getCity(),
            'Street' => $order->getAddressId()->getAddress(),
            'Street No_' => $order->getAddressId()->getAddress(),
            'Floor' => '',
            'Grid' => '',
            'Chain' => $order->getPlace()->getChain(),
            'Name' => $order->getUser()->getNameForOrder(),
            'Delivery Type' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? 1 : 4),
            'Restaurant No_' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? '' : $order->getPlacePoint()->getInternalCode()),
            'Order Date' => $order->getOrderDate()->format("Y-m-d"),
            'Order Time' => $order->getOrderDate()->format("H:i:s"),
            'Takeout Time' => $order->getDeliveryTime()->format("H:i:s"),
            'Directions' => '',
            'Discount Card No_' => '',
            'Order Status' => 0,
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

        $rez = sqlsrv_query ( $this->getConnection() , 'INSERT INTO '.$this->getHeaderTable().' ('.$queryPart['keys'].') VALUES('.$queryPart['values'].')');
        if( $rez === false) {
            die( print_r( sqlsrv_errors(), true) );
        }
        $this->_processLines($order, $orderNewId);
    }

    private function _processLines(Order $order, $orderNewId)
    {
        foreach ($order->getDetails() as $key=>$detail) {
            $this->_processLine($order, $orderNewId, ($key + 1));
        }
    }

    private function _processLine(OrderDetails $detail, $orderNewId, $key)
    {
        $dishSize = $this->getContainer()->get('doctrine')->getRepository('FoodDishesBundle:DishSize')->find($detail->getDishSizeCode());
        $dataToPut = array(
            'Order No_' => $orderNewId,
            'Line No_' => $key,
            'Entry Type' => 0,
            'No_' => $dishSize->getCode(),
            'Description' => '',
            'Quantity' => $detail->getQuantity(),
            'Price' => $detail->getPrice(), // @todo test the price. Kaip gula. Total ar ne.
            'Parent Line' => '', // @todo kaip optionsai sudedami. ar prie pirmines kainos ar ne
            'Amount' => '',// @todo test the price. Kaip gula. Total ar ne.
            'Discount Amount' => '',
            'Payment' => '',
            'Value' => ''
        );

        $queryPart = $this->generateQueryPart($dataToPut);

        $rez = sqlsrv_query ( $this->getConnection() , 'INSERT INTO '.$this->getLineTable().' ('.$queryPart['keys'].') VALUES('.$queryPart['values'].')');
        if( $rez === false) {
            die( print_r( sqlsrv_errors(), true) );
        }
    }

    public function getNavOrderId(Order $order)
    {
        return $this->_orderIdModifier + $order->getId();
    }
}
?>