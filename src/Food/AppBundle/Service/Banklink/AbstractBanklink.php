<?php

namespace Food\AppBundle\Service\Banklink;

abstract class AbstractBanklink
{
    abstract public function getBankUrl();
    abstract public function mac($data = array(), $vkService = 0);

    public function sign($mac, $privateKey)
    {
        $signature = '';
        $key = openssl_pkey_get_private($privateKey);

        if (!openssl_sign($mac, $signature, $key)) {
            throw new \Exception('Cannot sign MAC with private key.');
        }

        return base64_encode($signature);
    }

    public function verify($myGeneratedMac, $bankMac, $publicKey)
    {
        $key = openssl_pkey_get_public($publicKey);
        $verified = openssl_verify($myGeneratedMac, base64_decode($bankMac), $key);
        openssl_free_key($key);

        return $verified ? true : false;
    }
}
