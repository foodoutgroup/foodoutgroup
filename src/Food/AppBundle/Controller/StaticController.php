<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StaticController extends Controller
{
    public function indexAction($id)
    {
        $staticPage = $this->get('food.static')->getPage($id);

        if (!$staticPage) {
            throw new NotFoundHttpException('Sorry not existing!');
        }

        return $this->render(
            'FoodAppBundle:Static:index.html.twig',
            array('staticPage' => $staticPage)
        );
    }
}