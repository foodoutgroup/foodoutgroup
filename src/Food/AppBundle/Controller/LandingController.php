<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LandingController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function b2bClientAction()
    {
        $locale = $this->container->getParameter('locale');
        return $this->render('FoodAppBundle:Landing:b2b_client_' . $locale . '.html.twig');
    }
}