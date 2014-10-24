<?php

namespace Food\OrderBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;

class SqlConnectorService extends ContainerAware
{
    private $isWin = false;
    private $isNx = false;
    private $conn = null;

    public function init($server, $port, $database, $user, $password)
    {
        if (
            (isset($_SERVER["SERVER_SOFTWARE"]) && strpos($_SERVER["SERVER_SOFTWARE"], 'Win') !== false)
            || (isset($_SERVER["OS"]) && strpos($_SERVER["OS"], 'Win') !== false)
        ) {
            $this->isWin = true;
            $this->isNx = false;
            return $this->_initWin($server, $port, $database, $user, $password);
        } else {
            $this->isWin = false;
            $this->isNx = true;
            return $this->_initNx($server, $port, $database, $user, $password);
        }
    }

    private function _initWin($server, $port, $database, $user, $password)
    {
        // services
        $logger = $this->container->get('logger');

        $serverName = $server.", ".$port;
        $connectionInfo = array( "Database"=> $database, "UID"=>$user, "PWD"=> $password);
        $this->conn = @sqlsrv_connect( $serverName, $connectionInfo);

        if (false === $this->conn) {
            $logger->crit('Windows: cannot connect to NAV SQL server.');
            return false;
        }

        return true;
    }

    private function _initNx($server, $port, $database, $user, $password)
    {
        // services
        $logger = $this->container->get('logger');

        $this->conn = @mssql_pconnect($server.":".$port, $user, $password);

        if (false === $this->conn) {
            $logger->crit('Unix: cannot connect to NAV SQL server.');
            return false;
        }

        mssql_select_db($database, $this->conn);
        return true;
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

    /**
     * @return resource
     */
    private function _queryWin($query)
    {
        // services
        $logger = $this->container->get('logger');

        $rez = @sqlsrv_query($this->conn , $query);

        if (false === $rez) {
            $logger->crit('Windows: query(' . $query . ') failed: ' .
                          print_r(sqlsrv_errors(), true));
        }

        return $rez;
    }

    /**
     * @return resource|true
     */
    private function _queryNx($query)
    {
        // services
        $logger = $this->container->get('logger');

        $rez = @mssql_query($query, $this->conn);

        if (false === $rez) {
            $logger->crit('Unix: query(' . $query . ') failed: ' .
                          print_r(mssql_get_last_message(), true));
        }

        return $rez;
    }

    public function fetchArray($res)
    {
        if ($this->isWin) {
            return sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
        } else {
            return mssql_fetch_array($res);
        }
    }
}
