<?php

namespace Food\OrderBundle\Service\Banklink;

use Food\OrderBundle\Service\Banklink\AbstractBanklink;

class Seb extends AbstractBanklink
{
    const REDIRECT_SERVICE = 1001;
    const SUCCESS_SERVICE = 1101;
    const FAILURE_SERVICE = 1901;

    private $fields = [
        self::REDIRECT_SERVICE => ['VK_SERVICE',
                                   'VK_VERSION',
                                   'VK_SND_ID',
                                   'VK_STAMP',
                                   'VK_AMOUNT',
                                   'VK_CURR',
                                   'VK_ACC',
                                   'VK_NAME',
                                   'VK_REF',
                                   'VK_MSG'],
        self::SUCCESS_SERVICE => ['VK_SERVICE',
                                  'VK_VERSION',
                                  'VK_SND_ID',
                                  'VK_REC_ID',
                                  'VK_STAMP',
                                  'VK_T_NO',
                                  'VK_AMOUNT',
                                  'VK_CURR',
                                  'VK_ACC',
                                  'VK_REC_NAME',
                                  'VK_SND_ACC',
                                  'VK_SND_NAME',
                                  'VK_REF',
                                  'VK_MSG',
                                  'VK_T_DATE'],
        self::FAILURE_SERVICE => ['VK_SERVICE',
                                  'VK_VERSION',
                                  'VK_SND_ID',
                                  'VK_REC_ID',
                                  'VK_STAMP',
                                  'VK_REF',
                                  'VK_MSG']
    ];

    public function getBankUrl()
    {
        return 'https://ebankas.seb.lt/cgi-bin/vbint.sh/vbnet.w';
    }

    public function mac($data = array(), $vkService = 0)
    {
        if (!in_array($vkService, array_keys($this->fields))) return '';

        $mac = '';
        $fields = $this->fields[$vkService];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new \Exception("Cannot generate MAC, because data array has no `$field` field.");
            }

            $value = $data[$field];
            $mac .= str_pad(strlen($value), 3, '0', STR_PAD_LEFT) . $value;
        }

        return $mac;
    }
}
