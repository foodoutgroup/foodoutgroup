<?php

namespace Food\OrderBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;

class NavService extends ContainerAware
{

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
}
?>