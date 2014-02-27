<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('FoodAppBundle:Default:index.html.twig');
    }

    public function footerAction()
    {
        $topRatedPlaces = $this->get('food.places')->getTopRatedPlaces(10);
        $staticPages = $this->get('food.static')->getActivePages(10);
        return $this->render(
            'FoodAppBundle:Default:footer_links.html.twig',
            array(
                'topRatedPlaces' => $topRatedPlaces,
                'staticPages' => $staticPages,
            )
        );
    }
}
