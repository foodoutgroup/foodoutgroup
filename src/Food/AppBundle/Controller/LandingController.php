<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LandingController extends Controller
{
    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function b2bClientAction()
    {
        return $this->render('FoodAppBundle:Landing:b2b_client.html.twig');
    }
}