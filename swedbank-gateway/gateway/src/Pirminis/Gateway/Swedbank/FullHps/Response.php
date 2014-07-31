<?php

namespace Pirminis\Gateway\Swedbank\FullHps;

class Response
{
    const HPS_URL_XPATH = '//HpsTxn//hps_url';
    const SESSION_ID_XPATH = '//HpsTxn//session_id';
    const STATUS_XPATH = '//status';

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

    protected function hps_url()
    {
        $hps_urls = $this->dom()
                         ->xpath(static::HPS_URL_XPATH);

        if (empty($hps_urls)) return null;

        $hps_url = (string)reset($hps_urls);

        if (empty($hps_url)) return null;

        return $hps_url;
    }

    protected function session_id()
    {
        $session_ids = $this->dom()
                            ->xpath(static::SESSION_ID_XPATH);

        if (empty($session_ids)) return null;

        $session_id = (string)reset($session_ids);

        if (empty($session_id)) return null;

        return $session_id;
    }

    protected function status()
    {
        $statuses = $this->dom()
                         ->xpath(static::HPS_URL_XPATH);

        if (empty($statuses)) return null;

        $status = (string)reset($statuses);

        if (empty($status)) return null;

        return $status;
    }

    public function redirect_url()
    {
        return sprintf('%s%s%s',
                       $this->hps_url(),
                       $this->hps_url_parameter_name,
                       $this->session_id());
    }
}
