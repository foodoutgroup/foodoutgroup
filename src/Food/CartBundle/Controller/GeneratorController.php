<?php

namespace Food\CartBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class GeneratorController extends Controller
{
    public function generatorAction($oid)
    {
        $ois = $this->get('food.order');
        $ois->generateCsv($oid);
        return new Response($oid);
    }
}
