<?php

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\Debug\Debug;
use Pirminis\Gateway\Swedbank\FullHps\Request;
use Pirminis\Gateway\Swedbank\FullHps\Response;
use Pirminis\Gateway\Swedbank\FullHps\Request\Parameters;
use Pirminis\Gateway\Swedbank\Banklink\Sender;


if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', 'fe80::1', '::1'))
) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';
Debug::enable();

require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();
$request = SymfonyRequest::createFromGlobals();


// --------------------------------
$params = new Parameters();
$params->set('client', '88185002')
       ->set('password', 'aXVdnHfZSJmz')
       ->set('order_id', uniqid())
       ->set('price', '1')
       ->set('transaction_datetime', date('Y-m-d H:i:s'))
       ->set('comment', 'test comment')
       ->set('return_url', 'http://foodout.lt/return')
       ->set('expire_url', 'http://foodout.lt/expire');

$request = new Request($params);
var_dump($request->xml());
$sender = new Sender($request->xml());
$response = new Response($sender->send());

var_dump($response);
