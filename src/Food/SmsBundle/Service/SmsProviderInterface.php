<?php

namespace Food\SmsBundle\Service;

interface SmsProviderInterface
{

    /**
     * @param string $username
     * @param string $password
     *
     * @return void
     */
    public function authenticate($username, $password);

    /**
     * @param string $sender
     * @param string $recipient
     * @param string $message
     */
    public function sendMessage($sender, $recipient, $message);

    /**
     * @return double
     */
    public function getAccountBalance();

    /**
     * @param string $dlrData
     *
     * @return mixed
     */
    public function parseDeliveryReport($dlrData);

    /**
     * @param string $url
     *
     * @return void
     */
    public function setApiUrl($url);

}