<?php

namespace Pirminis\Gateway\Swedbank\Banklink;

class Sender
{
    //const DESTINATION = 'https://mars.transaction.datacash.com/Transaction';
    // URL for testing below
    // const DESTINATION = 'https://accreditation.datacash.com/Transaction/swedrep_i';
    // const DESTINATION = 'https://accreditation.datacash.com/Transaction/acq_a';

    protected $request_xml = '';

    public function __construct($request_xml, $url = 'https://mars.transaction.datacash.com/Transaction')
    {
        $this->request_xml = $request_xml;
        $this->url = $url;
    }

    public function send()
    {
        $ch = curl_init($this->url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request_xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);

        return $result;
    }
}
