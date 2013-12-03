<?php

namespace Food\SmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('FoodSmsBundle:Default:index.html.twig', array('name' => $name));
    }
}
