<?php

namespace Food\TcgBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('FoodTcgBundle:Default:index.html.twig', array('name' => $name));
    }
}
