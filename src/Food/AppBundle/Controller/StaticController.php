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
            // TODO normalus error page'ai https://basecamp.com/2470154/projects/4420182-skanu-lt-gamyba/todos/72718077-pasidaryti-custom#events_todo_72718077
            throw new NotFoundHttpException('Sorry not existing!');
        }

        return $this->render(
            'FoodAppBundle:Static:index.html.twig',
            array('staticPage' => $staticPage)
        );
    }
}