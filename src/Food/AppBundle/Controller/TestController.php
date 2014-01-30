<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class TestController extends Controller
{
    public function indexAction()
    {
        $gisService = $this->get('food.gis');
        $resp = $gisService->getCoordsOfPlace('Vivulskio 21, Vilnius');
        var_dump($resp);
        return new Response('Uber');
    }
}