<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('food_tcg_homepage', new Route('/hello/{name}', array(
    '_controller' => 'FoodTcgBundle:Default:index',
)));

return $collection;
