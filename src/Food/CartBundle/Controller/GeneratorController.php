<?php

namespace Food\CartBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GeneratorController extends Controller
{
    public function generatorAction($oid)
    {
        $ois = $this->get('food.order');
        $ois->generateCsv($oid);
    }
}
