<?php

namespace Food\PlacesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class DefaultController extends Controller
{
    public function citiesAction()
    {
        $cities = $this->get('food.places')->getAvailableCities();
        return new Response(json_encode($cities));
    }
}