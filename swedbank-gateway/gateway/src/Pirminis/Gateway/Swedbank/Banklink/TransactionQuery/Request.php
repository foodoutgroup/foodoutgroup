<?php

namespace Pirminis\Gateway\Swedbank\Banklink\TransactionQuery;

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
    <APMTxn>
      <method>transaction_query</method>
      <dpg_reference_id>%dpg_reference_id%</dpg_reference_id>
      <AlternativePayment version="2" />
    </APMTxn>
  </Transaction>
</Request>
TRANSACTION_REQUEST;

    public function __construct($client,
                                $password,
                                $dpg_reference_id)
    {
        $subject = $this->transactionRequestXml;
        $replacements = array('%client%' => $client,
                              '%password%' => $password,
                              '%dpg_reference_id%' => $dpg_reference_id);

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
