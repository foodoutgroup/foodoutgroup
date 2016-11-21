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

        $this->page->setContent(str_replace("&quot;", '"', $this->page->getContent()));
        $serviceCollection = $this->initServiceTag();
        foreach ($serviceCollection as $s){
            $serviceContent = $s['service'];
            if(is_array($serviceContent)) {
                if(isset($serviceContent['template'])) {
                    return $this->render($serviceContent['template'], isset($serviceContent['params']) ? $serviceContent['params'] : []);
                }
            } else {
                $this->page->setContent(str_replace($s["replace"], $serviceContent, $this->page->getContent()));
            }
        }

        return $this->render('FoodAppBundle:StaticPage:index.html.twig', ['page' => $this->page]);
    }

    private function initServiceTag()
    {

        $serviceCollection = [];
        preg_match_all('/\[s=\"(.*)\"\]/', htmlspecialchars_decode($this->page->getContent()), $matches);
        if(count($matches[1])) {
            for($i=0;$i<count($matches[1]); $i++) {
                $service = $matches[1][$i];
                $serviceDetail = explode(":", $service);
                try {
                    $serviceCollection[] = [
                        'replace' => $matches[0][$i],
                        'service' => $this->get($serviceDetail[0])->build($serviceDetail),
                    ];
                } catch (\Exception $ignore) {}
            }
        }

        return $serviceCollection;
    }
}
