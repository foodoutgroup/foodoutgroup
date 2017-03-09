<?php

namespace Food\OrderBundle\Service\Banklink;

use Food\OrderBundle\Service\Banklink\AbstractBanklink;

class Seb extends AbstractBanklink
{
    private $container;

    private $fields = [];

    public function setContainer($container)
    {
        $this->container = $container;

        $this->initFields();
    }

    private function initFields()
    {
        $config = $this->container->getParameter('seb');

        $this->fields[$config['REDIRECT_SERVICE']] = [
            'VK_SERVICE',
            'VK_VERSION',
            'VK_SND_ID',
            'VK_STAMP',
            'VK_AMOUNT',
            'VK_CURR',
            'VK_ACC',
            'VK_NAME',
            'VK_REF',
            'VK_MSG'
        ];
        $this->fields[$config['SUCCESS_SERVICE']] = [
            'VK_SERVICE',
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
            'VK_T_DATE'
        ];
        $this->fields[$config['FAILURE_SERVICE']] = [
            'VK_SERVICE',
            'VK_VERSION',
            'VK_SND_ID',
            'VK_REC_ID',
            'VK_STAMP',
            'VK_REF',
            'VK_MSG'
        ];
        $this->fields[$config['WAITING_SERVICE']] = [
            'VK_SERVICE',
            'VK_VERSION',
            'VK_SND_ID',
            'VK_REC_ID',
            'VK_STAMP',
            'VK_AMOUNT',
            'VK_CURR',
            'VK_ACC',
            'VK_REC_NAME',
            'VK_SND_ACC',
            'VK_SND_NAME',
            'VK_REF',
            'VK_MSG'
        ];
    }

    public function getBankUrl()
    {
        $config = $this->container->getParameter('seb');
        return $config['entrypoint'];
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
