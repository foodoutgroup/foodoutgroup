<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class ApplicationController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        return $this->render(
            'FoodAppBundle:Application:index.html.twig'
        );
    }
}