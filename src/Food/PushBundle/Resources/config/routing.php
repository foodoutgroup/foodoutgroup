<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('food_push_homepage', new Route('/hello/{name}', array(
    '_controller' => 'FoodPushBundle:Default:index',
)));

return $collection;
