<?php

namespace Pirminis\Gateway\Swedbank\FullHps;

class ResponseForm
{
    public function __construct($full_hps_response_xml)
    {
        $dom = simplexml_load_string($full_hps_response_xml);

        $this->process_hps_data($dom);
    }

    public function hps_url()
    {
        return $this->hps_url;
    }

    public function hps_url_parameter_name()
    {
        return $this->hps_url_parameter_name;
    }

    public function session_id()
    {
        return $this->session_id;
    }

    public function url()
    {
        return sprintf('%s%s%s', $this->hps_url(), $this->hps_url_parameter_name(), $this->session_id());
    }

    private function process_hps_data($dom)
    {
        $this->hps_url = (string)$dom->HpsTxn->hps_url;
        $this->session_id = (string)$dom->HpsTxn->session_id;
    }

    protected $hps_url = '';
    protected $hps_url_parameter_name = '?HPS_SessionID=';
    protected $session_id = '';
}
