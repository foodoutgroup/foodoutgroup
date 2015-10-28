<?php
@ini_set('memory_limit', '512M');
// Ijungti kai reikia laikinai stabdyti svetaines veikima del kokiu nors priezasciu
//require 'maintenance.php';


// The redirect test from one way to another... or how foodout was sold to delfi
$domain = $_SERVER['HTTP_HOST'];
$method = $_SERVER['REQUEST_METHOD'];
$url = $_SERVER['REQUEST_URI'];


$rdDomains = array('foodout.lt', 'www.foodout.lt');
$newUrl = 'foodout.1000receptu.lt';
//$rdDomains = array('skanu');
//$newUrl = 'so.skanu';

// Check pattern
$pattern = '/\/(api|admin|cart|test|invoice|payments|call_center|newsletter|ajax|js|routing|monitoring|nagios|logistics|order|sitemap|o|r|o\-spr)(\/|$)/i';

// Das logic
if (in_array($domain, $rdDomains) && $method == 'GET' && !preg_match($pattern, $url)) {
    // kol delfis nenukreiptas - isjungiam redirecta
//    header('Location: http://'.$newUrl.$url, null, 302);
//    die();
}

/* Delfi promo end */

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

umask(0002);

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

// Use APC for autoloading to improve performance.
// Change 'sf2' to a unique prefix in order to prevent cache key conflicts
// with other applications also using APC.
/*
$loader = new ApcClassLoader('sf2', $loader);
$loader->register(true);
*/

require_once __DIR__.'/../app/AppKernel.php';
//require_once __DIR__.'/../app/AppCache.php';

ini_set('date.timezone', 'Europe/Vilnius');

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
//$kernel = new AppCache($kernel);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
