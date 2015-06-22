<?php

// The redirect test from one way to another... or how foodout was sold to delfi
$domain = $_SERVER['HTTP_HOST'];
$method = $_SERVER['REQUEST_METHOD'];
$url = $_SERVER['REQUEST_URI'];


$rdDomains = array('foodout.lt', 'www.foodout.lt');
$newUrl = 'foodout.1000receptu.lt';
//$rdDomains = array('skanu');
//$newUrl = 'so.skanu';

// Check pattern
$pattern = '/\/(api|admin|test|invoice|payments|call_center|newsletter|ajax|js|routing|monitoring|nagios|logistics|order|sitemap|success|o|r|o\-spr)\//i';

// Das logic
if (in_array($domain, $rdDomains) && $method == 'GET' && !preg_match($pattern, $url)) {
    header('Location: http://'.$newUrl.$url, null, 302);
    die();
}

/* Delfi promo end */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
umask(0002);

// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.
if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', 'fe80::1', '::1'))
) {
   // header('HTTP/1.0 403 Forbidden');
    // exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';
Debug::enable();

require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
