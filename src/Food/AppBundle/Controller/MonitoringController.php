<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;


class MonitoringController extends Controller
{
    public function indexAction()
    {
        return new Response('nothing here :P');
    }

    public function nagiosAction()
    {
        return new RedirectResponse('http://ec2-54-72-211-80.eu-west-1.compute.amazonaws.com/nagios/', 302);

    }
}