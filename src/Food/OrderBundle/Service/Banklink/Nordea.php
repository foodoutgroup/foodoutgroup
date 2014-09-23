<?php

namespace Food\OrderBundle\Service\Banklink;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class Nordea
{
    protected $requestFields = ['VERSION',
                                'STAMP',
                                'RCV_ID',
                                'AMOUNT',
                                'REF',
                                'DATE',
                                'CUR'];

    protected $responseFields = ['RETURN_VERSION',
                                 'RETURN_STAMP',
                                 'RETURN_REF',
                                 'RETURN_PAID'];

    protected $salt;
    protected $router;
    protected $factory;

    public function __construct($salt, $router, $factory)
    {
        $this->salt = $salt;
        $this->router = $router;
        $this->factory = $factory;
    }

    /**
     * Real bank URL.
     * @return string
     */
    public function getBankUrl()
    {
        return 'https://netbank.nordea.com/pnbepay/epayn.jsp';
    }

    /**
     * Test bank URL.
     * @return string
     */
    public function getTestBankUrl()
    {
        return 'https://netbank.nordea.com/pnbepaytest/epayn.jsp';
    }

    /**
     * Verify data from Nordea. One thing to consider:
     * if required field is empty, we still use empty string . '&'.
     * @param  array  $data Request data.
     * @return boolean
     */
    public function verify(array $data)
    {
        $returnMac = !empty($data['RETURN_MAC']) ? $data['RETURN_MAC'] : '';
        $signature = $this->encode($this->generateMac($data,
                                                      $this->responseFields));

        return $signature === $returnMac;
    }

    public function getMacSignature(array $data)
    {
        return $this->encode($this->generateMac($data,
                                                $this->requestFields));
    }

    protected function generateMac(array $data, array $fields)
    {
        $mac = '';

        foreach ($fields as $field) {
            $mac .= !empty($data[$field]) ? $data[$field] : '';
            $mac .= '&';
        }

        $mac .= $this->salt . '&';

        return $mac;
    }

    protected function encode($value)
    {
        return strtoupper(md5($value));
    }
}
