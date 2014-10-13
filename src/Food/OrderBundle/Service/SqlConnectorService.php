<?php

namespace Food\OrderBundle\Service;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerAware;

class SqlConnectorService extends ContainerAware
{
    private $isWin = false;
    private $isNx = false;
    private $conn = null;
    public function init($server, $port, $database, $user, $password)
    {
        if(strpos($_SERVER["SERVER_SOFTWARE"], 'Win')) {
            $this->isWin = true;
            $this->isNx = false;
            $this->_initWin($server, $port, $database, $user, $password);
        } else {
            $this->isWin = false;
            $this->isNx = true;
            $this->_initNx($server, $port, $database, $user, $password);
        }
    }

    private function _initWin($server, $port, $database, $user, $password)
    {
        $serverName = $server.", ".$port;
        $connectionInfo = array( "Database"=> $database, "UID"=>$user, "PWD"=> $password);
        $this->conn = sqlsrv_connect( $serverName, $connectionInfo);

        if( $this->conn === false ) {
            throw new \RuntimeException( print_r( sqlsrv_errors(), true));
        }
    }

    private function _initNx($server, $port, $database, $user, $password)
    {
        $this->conn = mssql_pconnect($server.":".$port, $user, $password);
        mssql_select_db($database, $this->conn);
    }

    public function query($query)
    {
        $query = iconv('UTF-8', 'windows-1257', $query);
        if ($this->isWin) {
            return $this->_queryWin($query);
        } else {
            return $this->_queryNx($query);
        }
    }

    private function _queryWin($query)
    {
        $rez = sqlsrv_query ( $this->conn , $query);

        if( $rez === false) {
            throw new \RuntimeException( print_r( sqlsrv_errors(), true));
        }
        return $rez;
    }

    private function _queryNx($query)
    {
        $rez = mssql_query($query, $this->conn);
        if (!$rez) {
            throw new \RuntimeException(mssql_get_last_message());
        }
        return $rez;
    }

    public function fetchArray($res)
    {
        var_dump($res);
        if ($this->isWin) {
            return sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
        } else {
            return mssql_fetch_array($res);
        }
    }
}