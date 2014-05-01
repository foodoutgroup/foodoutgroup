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

        $video = '<iframe width="560" height="315" src="//www.youtube-nocookie.com/embed/3zFW6hnuvJY" frameborder="0" allowfullscreen></iframe>';
        $cont = $staticPage->getContent();
        $cont = str_replace("{{ faq_video }}", $video, $cont);
        $staticPage->setContent($cont);
        return $this->render(
            'FoodAppBundle:Static:index.html.twig',
            array('staticPage' => $staticPage)
        );
    }
}