<?php

namespace Food\AppBundle\Controller;

use Food\AppBundle\Entity\StaticContent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StaticPageController extends Controller
{
    /**
     * @var StaticContent
     */
    private $page;

    public function indexAction($id)
    {
        $this->page = $this->get('food.static')->getPage($id);

        if (!$this->page ) {
            throw new NotFoundHttpException('Sorry not existing!');
        }

        $this->findService();


        return $this->render('FoodAppBundle:StaticPage:index.html.twig', ['page' => $this->page]);
    }

    private function findService()
    {
        preg_match_all('/\[s=\"(.*)\"\]/', $this->page->getContent(), $matches);

        var_dump($matches);

    }
}
