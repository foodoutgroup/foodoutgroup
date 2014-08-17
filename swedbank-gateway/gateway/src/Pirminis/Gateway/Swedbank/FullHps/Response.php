<?php

namespace Pirminis\Gateway\Swedbank\FullHps;

use Pirminis\XPath;

class Response
{
    use XPath;

    const QUERY_STATUS_SUCCESS = '1';
    const DC_RESPONSE_SUCCESS = '1';

    const HPS_URL_XPATH = '//HpsTxn//hps_url';
    const SESSION_ID_XPATH = '//HpsTxn//session_id';
    const STATUS_XPATH = '//status';
    const HPS_STATUS_XPATH = '//HpsTxn//AuthAttempts//Attempt//dc_response';
    const HPS_AUTH_ATTEMPT_DC_RESPONSE_XPATH = '//HpsTxn//AuthAttempts//Attempt//datacash_reference';
    const QUERY_STATUS_XPATH = '//status';
    const QUERY_HPS_DC_REFERENCE_XPATH = '//HpsTxn//datacash_reference';
    const QUERY_PAYMENT_STATUS_XPATH = '//QueryTxnResult//status';
    const QUERY_MERCHANT_REFERENCE = '//QueryTxnResult//merchant_reference';

    protected $xml;
    protected $dom;

    protected $hps_url_parameter_name = '?HPS_SessionID=';
    protected $hps_url;
    protected $session_id;
    protected $redirect_url;

    public function __construct($xml)
    {
        $this->xml = $xml;
        $this->dom = simplexml_load_string($xml);
    }

    /**
     * @return string
     */
    public function xml()
    {
        return $this->xml;
    }

    /**
     * @return SimpleXMLElement
     */
    public function dom()
    {
        return $this->dom;
    }

    /**
     * One important note here: QUERY_STATUS_SUCCESS marks
     * succes of query, not payment transaction.
     *
     * @return boolean
     */
    public function query_succeeded()
    {
        $status = $this->xpath_first($this->dom(), static::QUERY_STATUS_XPATH);
        return $status === static::QUERY_STATUS_SUCCESS;
    }

    public function query_merchant_reference()
    {
        $merchant_reference = $this->xpath_first(
            $this->dom(),
            static::QUERY_MERCHANT_REFERENCE);
        return $merchant_reference;
    }

    /**
     * @return string
     */
    public function dc_reference()
    {
        $dc_ref = $this->xpath_first($this->dom(),
                                     static::QUERY_HPS_DC_REFERENCE_XPATH);
        return $dc_ref;
    }

    /**
     * @return boolean
     */
    public function is_authenticated()
    {
        $dc_response = $this->dc_response();
        $dc_reference = $this->dc_reference();
        $dc_attempt_reference = $this->dc_attempt_reference();

        return $dc_response === static::DC_RESPONSE_SUCCESS &&
               $dc_reference === $dc_attempt_reference &&
               !empty($dc_reference) &&
               !empty($dc_attempt_reference)
        ;
    }

    /**
     * @return integer
     */
    protected function dc_response()
    {
        $dc_response = $this->xpath_first($this->dom(),
                                          static::HPS_STATUS_XPATH);
        return $dc_response;
    }

    /**
     * @return string
     */
    protected function dc_attempt_reference()
    {
        $a_ref = $this->xpath_first($this->dom(),
                                    static::HPS_AUTH_ATTEMPT_DC_RESPONSE_XPATH);
        return $a_ref;
    }

    /**
     * @return string
     */
    protected function hps_url()
    {
        $hps_url = $this->xpath_first($this->dom(), static::HPS_URL_XPATH);
        return $hps_url;
    }

    /**
     * @return string
     */
    protected function session_id()
    {
        $id = $this->xpath_first($this->dom(), static::SESSION_ID_XPATH);
        return $id;
    }

    /**
     * @return string
     */
    public function redirect_url()
    {
        return sprintf('%s%s%s',
                       $this->hps_url(),
                       $this->hps_url_parameter_name,
                       $this->session_id());
    }
}
