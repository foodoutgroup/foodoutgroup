<?php

namespace Pirminis\Gateway\Swedbank\FullHps;

class Request
{
    public function __construct($client,
                                $password,
                                $merchant_reference,
                                $transaction_datetime,
                                $comment,
                                $return_url,
                                $expire_url,
                                $success_url,
                                $failure_url)
    {
        $subject = $this->fullHPSRequestXml;
        $replacements = array(
            '%client%' => $client,
            '%password%' => $password,
            '%merchant_reference%' => $merchant_reference,
            '%transaction_datetime%' => $transaction_datetime,
            '%comment%' => $comment,
            '%return_url%' => $return_url,
            '%expire_url%' => $expire_url,
            '%success_url%' => $success_url,
            '%failure_url%' => $failure_url
        );

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
    protected $fullHPSRequestXml = <<<FULLHPSREQUEST
<?xml version="1.0" encoding="UTF-8"?>
<Request version="2">
    <Authentication>
        <client>%client%</client>
        <password>%password%</password>
    </Authentication>
    <Transaction>
        <TxnDetails>
            <merchantreference>%merchant_reference%</merchantreference>
            <ThreeDSecure>
                <Browser>
                    <accept_headers>*/*</accept_headers>
                    <user_agent>IE/6.0</user_agent>
                </Browser>
                <merchant_url>http://foodout.lt</merchant_url>
                <purchase_datetime>%transaction_datetime%</purchase_datetime>
                <purchase_desc>%comment%</purchase_desc>
                <verify>yes</verify>
            </ThreeDSecure>
            <capturemethod>ecomm</capturemethod>
            <amount currency="EUR">10.00</amount>
        </TxnDetails>
        <HpsTxn>
            <page_set_id>4</page_set_id>
            <method>setup_full</method>
            <return_url>%return_url%</return_url>
            <expiry_url>%expiry_url%</expiry_url>
        </HpsTxn>
        <CardTxn>
            <method>auth</method>
        </CardTxn>
    </Transaction>
</Request>
FULLHPSREQUEST;
}
