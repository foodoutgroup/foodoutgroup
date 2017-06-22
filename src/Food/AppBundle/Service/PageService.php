<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Router;

class PageService {

    private $em, $router;

    public function __construct(EntityManager $em, Router $router)
    {
        $this->em = $em;
        $this->router = $router;
    }

    public function getByParam($param)
    {
        if ($pageIdParam = $this->em->getRepository('FoodAppBundle:Param')->findOneBy(['param' => $param])) {
            $pageId = (int) $pageIdParam->getValue();
            if ($pageId) {
                $page = $this->em->getRepository('FoodAppBundle:StaticContent')->findOneBy(['id' => $pageId]);
                if($page) {
                    return $page;
                }
            }
        }
        return false;
    }
}
