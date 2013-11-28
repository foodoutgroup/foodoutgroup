<?php

namespace Food\SmsBundle\Service;

interface SmsProviderInterface {

    public function authenticate();

    public function sendMessage();

    function parseResponse();

    public function getMessageStatus();

    public function getAccountBalance();

}