<?php

namespace Food\SmsBundle\Service;

interface SmsProviderInterface {

    public function authenticate($username, $password);

    public function sendMessage($sender, $recipient, $message);

    public function getMessageStatus($message);

    public function getAccountBalance();

    public function setApiUrl($url);

}