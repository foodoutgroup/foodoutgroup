<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StaticController extends Controller
{
    public function indexAction($id)
    {
        $staticService = $this->get('food.static');
        $staticPage = $staticService->getPage($id);
        $cities = $staticService->getPlacesWithOurLogistic(true);
        $places = $staticService->getPlacesWithOurLogistic();

        if (!$staticPage) {
            throw new NotFoundHttpException('Sorry not existing!');
        }

        $video = '<iframe width="560" height="315" src="'.$this->container->getParameter('yt_embeded').'" frameborder="0" allowfullscreen></iframe>';
        $cont = $staticPage->getContent();
        $cont = str_replace("{{ faq_video }}", $video, $cont);
        $staticPage->setContent($cont);
        return $this->render(
            'FoodAppBundle:Static:index.html.twig',
            array(
                'staticPage' => $staticPage,
                'cities' => $cities,
                'places' => $places,
            )
        );
    }
}
