<?php

namespace Pirminis\Gateway\Swedbank\Banklink;

class Response
{
    const TRANSACTION_ID = 'TransactionId';

    // when you make successful payment
    const AUTHORISED_STATUS = 1;

    // when you must redirect user
    const REDIRECT_STATUS = 2053;

    // when you get "processing transaction"
    const REQUIRES_INVESTIGATION_STATUS = 2066;

    // error occured
    const ERROR_STATUS = 2052;

    // when you cancel
    const CANCELLED_STATUS = 2054;

    // when there is a communication error
    const COMMUNICATION_ERROR_STATUS = 2062;

    // event: authorized
    const EVENT_AUTHORISED = 'AUTHORISED';
    const EVENT_REQUIRES_INVESTIGATION = 'REQUIRES_INVESTIGATION';
    const EVENT_CANCELLED = 'CANCELLED';

    // xpaths
    const REDIRECT_URL_XPATH = '//APMTxn//Purchase//RedirectURL';
    const STATUS_XPATH = '//status';
    const MERCHANT_REFERENCE_XPATH = '//merchantreference';
    const PURCHASE_XPATH = '//APMTxn//Purchase';
    const EVENT_STATUS_XPATH = '//Event//Purchase//Status';

    protected $xml;
    protected $dom;

    public function __construct($purchase_response_xml)
    {
        $this->xml = $purchase_response_xml;
        $this->dom = simplexml_load_string($purchase_response_xml);
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
     * @return boolean
     */
    public function is_authorized()
    {
        return $this->status() === static::AUTHORISED_STATUS;
    }

    /**
     * @return boolean
     */
    public function is_redirect()
    {
        return $this->status() === static::REDIRECT_STATUS;
    }

    /**
     * @return boolean
     */
    public function requires_investigation()
    {
        return $this->status() === static::REQUIRES_INVESTIGATION_STATUS;
    }

    /**
     * @return boolean
     */
    public function is_error()
    {
        return $this->status() === static::ERROR_STATUS;
    }

    /**
     * @return boolean
     */
    public function is_cancelled()
    {
        return $this->status() === static::CANCELLED_STATUS;
    }

    /**
     * @return boolean
     */
    public function communication_error()
    {
        return $this->status() === static::COMMUNICATION_ERROR_STATUS;
    }

    /**
     * @return string
     */
    public function redirect_url()
    {
        if (!$this->dom()) return '';

        $redirect_urls = $this->dom()
                              ->xpath(static::REDIRECT_URL_XPATH);

        if (empty($redirect_urls)) return null;

        $redirect_url = (string)reset($redirect_urls);

        return $redirect_url;
    }

    /**
     * @return string
     */
    public function order_id()
    {
        if (!$this->dom()) return '';

        $purchases = $this->dom()
                          ->xpath(static::PURCHASE_XPATH);

        if (empty($purchases)) return null;

        $purchase = reset($purchases);

        if (empty($purchase)) return null;

        $attributes = (array)$purchase->attributes();
        $transaction_id = $attributes['@attributes'][static::TRANSACTION_ID];

        if (empty($transaction_id)) return null;

        return $transaction_id;
    }

    public function is_event()
    {
        $status = $this->event_status();

        return empty($status) ? false : true;
    }

    protected function event_status()
    {
        if (!$this->dom()) return '';

        $statuses = $this->dom()
                       ->xpath(static::EVENT_STATUS_XPATH);

        if (empty($statuses)) return null;

        $status = (string)reset($statuses);

        if (empty($status)) return null;

        return $status;
    }

    public function is_event_authorized()
    {
        return $this->event_status() === static::EVENT_AUTHORISED;
    }

    public function event_requires_investigation()
    {
        return $this->event_status() === static::EVENT_REQUIRES_INVESTIGATION;
    }

    public function is_event_cancelled()
    {
        return $this->event_status() === static::EVENT_CANCELLED;
    }

    /**
     * @return integer
     */
    protected function status()
    {
        if (!$this->dom()) return 0;

        $statuses = $this->dom()
                         ->xpath(static::STATUS_XPATH);

        if (empty($statuses)) return null;

        $status = (string)reset($statuses);

        if (empty($status)) return null;

        return (int)$status;
    }
}
