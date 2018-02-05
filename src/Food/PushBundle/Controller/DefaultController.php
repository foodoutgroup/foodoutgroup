<?php

namespace Food\PushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('FoodPushBundle:Default:index.html.twig', array('name' => $name));
    }
}
