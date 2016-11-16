<?php

namespace Pirminis\Gateway\Swedbank\Banklink;

use Pirminis\Gateway\Swedbank\Banklink\Request\Parameters;

class Request
{
    public function __construct(Parameters $params)
    {
        $subject = $this->purchaseRequestXml;
        $replacements = [];

        foreach ($params->mandatory_params() as $param) {
            $replacements["%{$param}%"] = $params->get($param);
        }

        foreach ($replacements as $search => $replace) {
            $subject = str_replace($search, $replace, $subject);
        }

        $this->finalXml = $subject;
    }

    public function xml()
    {
        return $this->finalXml;
    }

    protected $finalXml = '';
    protected $purchaseRequestXml = <<<PURCHASE_REQUEST
<?xml version="1.0" encoding="UTF-8"?>
<Request version="2">
  <Authentication>
    <client>%client%</client>
    <password>%password%</password>
  </Authentication>
  <Transaction>
    <TxnDetails>
      <merchantreference>%order_id%</merchantreference>
    </TxnDetails>
    <APMTxn>
      <method>purchase</method>
      <payment_method>SW</payment_method>
      <AlternativePayment version="2">
        <TransactionDetails>
          <Description>%comment%</Description>
          <TransactionDateTime>%transaction_datetime%</TransactionDateTime>
          <SuccessURL>%success_url%</SuccessURL>
          <FailureURL>%failure_url%</FailureURL>
          <Language>%language%</Language>
          <PersonalDetails>
            <Email>%email%</Email>
          </PersonalDetails>
          <BillingDetails>
            <AmountDetails>
              <Amount>%price%</Amount>
              <Exponent>2</Exponent>
              <CurrencyCode>978</CurrencyCode>
            </AmountDetails>
          </BillingDetails>
        </TransactionDetails>
        <MethodDetails>
          <ServiceType>%service_type%</ServiceType>
        </MethodDetails>
      </AlternativePayment>
    </APMTxn>
  </Transaction>
</Request>
PURCHASE_REQUEST;
}
