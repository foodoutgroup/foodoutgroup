<?php

namespace Food\AppBundle\Controller;

use Food\AppBundle\Entity\StaticContent;
use \Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StaticPageController extends Controller
{
    /**
     * @var StaticContent
     */
    private $page;

    public function indexAction($id, $slug, $params)
    {

        $this->page = $this->get('food.static')->getPage($id);
        if (!$this->page ) {
            throw new NotFoundHttpException('Sorry not existing!');
        }

        $this->page->setContent(str_replace("&quot;", '"', $this->page->getContent()));
        $serviceCollection = $this->initServiceTag($params);
        foreach ($serviceCollection as $s){
            $serviceContent = $s['service'];
            $serviceObj = $s['obj'];

            if(is_array($serviceContent)) {
                if(isset($serviceContent['template'])) {

                    $response = null;
                    if(method_exists($serviceObj, 'getResponse')) {
                        $response = $serviceObj->getResponse();
                    }
                    return $this->render($serviceContent['template'], isset($serviceContent['params']) ? $serviceContent['params'] : [], $response);
                }
            } else {
                $this->page->setContent(str_replace($s["replace"], $serviceContent, $this->page->getContent()));
            }
        }

        return $this->render('FoodAppBundle:StaticPage:index.html.twig', ['page' => $this->page]);
    }

    private function initServiceTag($params)
    {

        $serviceCollection = [];
        preg_match_all('/\[s=\"(.*)\"\]/', htmlspecialchars_decode($this->page->getContent()), $matches);
        if(count($matches[1])) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $service = $matches[1][$i];
                $serviceDetail = explode(":", $service);
                try {
                    $obj = $this->get($serviceDetail[0]);

                    if (method_exists($obj, 'setParams')) {
                        $obj->setParams($params);
                    }

                    $serviceCollection[] = [
                        'replace' => $matches[0][$i],
                        'params' => $serviceDetail,
                        'service' => $this->get($serviceDetail[0])->build($serviceDetail),
                        'obj' => $obj,
                    ];
                } catch (NotFoundHttpException $e) {
                    throw new NotFoundHttpException($e->getMessage());
                } catch (\Exception $ignore) {
                    die($ignore->getMessage());
                }
            }
        }

        return $serviceCollection;
    }
}
