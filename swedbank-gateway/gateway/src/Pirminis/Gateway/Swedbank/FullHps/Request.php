<?php

namespace Pirminis\Gateway\Swedbank\FullHps;

use Pirminis\Gateway\Swedbank\FullHps\Request\Parameters;

class Request
{
    public function __construct(Parameters $params)
    {
        $subject = $this->fullHPSRequestXml;
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
    protected $fullHPSRequestXml = <<<FULLHPSREQUEST
<?xml version="1.0" encoding="UTF-8"?>
<Request version="2">
    <Authentication>
        <client>%client%</client>
        <password>%password%</password>
    </Authentication>
    <Transaction>
        <TxnDetails>
            <Risk>
                <Action service="1">
                    <MerchantConfiguration>
                        <channel>W</channel> <--
                    </MerchantConfiguration>
                    <CustomerDetails>
                        <OrderDetails>
                            <BillingDetails>
                                <country>%country%</country> <--
                            </BillingDetails>
                        </OrderDetails>
                        <PersonalDetails>
                            <surname>%surname%</surname> <--
                            <first_name>%name%</first_name> <--
                            <telephone>%telephone%</telephone> <--
                        </PersonalDetails>
                        <PaymentDetails>
                            <payment_method>CC</payment_method> <--
                        </PaymentDetails>
                        <RiskDetails>
                            <email_address>%email%</email_address> <--
                            <ip_address>%ip%</ip_address> <--
                        </RiskDetails>
                    </CustomerDetails>
                </Action>
            </Risk>
            <merchantreference>%order_id%</merchantreference>
            <ThreeDSecure>
                <merchant_url>http://foodout.lt</merchant_url>
                <purchase_datetime>%transaction_datetime%</purchase_datetime>
                <purchase_desc>%comment%</purchase_desc>
                <verify>yes</verify>
            </ThreeDSecure>
            <capturemethod>ecomm</capturemethod>
            <amount currency="LTL">%price%</amount>
        </TxnDetails>
        <HpsTxn>
            <!-- <page_set_id>1360</page_set_id> -->
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
