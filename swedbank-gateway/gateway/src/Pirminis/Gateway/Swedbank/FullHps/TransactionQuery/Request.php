<?php

namespace Pirminis\Gateway\Swedbank\FullHps\TransactionQuery;

class Request
{
    protected $finalXml = '';
    protected $transactionRequestXml = <<<TRANSACTION_REQUEST
<?xml version="1.0" encoding="UTF-8"?>
<Request>
  <Authentication>
    <client>%client%</client>
    <password>%password%</password>
  </Authentication>
  <Transaction>
    <HistoricTxn>
      <method>query</method>
      <reference>%dts_reference%</reference>
    </HistoricTxn>
  </Transaction>
</Request>
TRANSACTION_REQUEST;

    public function __construct($client,
                                $password,
                                $dts_reference)
    {
        $subject = $this->transactionRequestXml;
        $replacements = array('%client%' => $client,
                              '%password%' => $password,
                              '%dts_reference%' => $dts_reference);

        foreach ($replacements as $search => $replace) {
            $subject = str_replace($search, $replace, $subject);
        }

        $this->finalXml = $subject;
    }

    public function xml()
    {
        return $this->finalXml;
    }
}
