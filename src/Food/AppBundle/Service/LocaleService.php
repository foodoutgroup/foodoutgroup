<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Traits;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\Router;

class LocaleService
{

    /**
     * @var EntityManager
     */
    private $em, $router, $container;

    /**
     * @param $entity
     */
    public function __construct(EntityManager $em, Router $router, Container $container)
    {
        $this->em = $em;
        $this->router = $router;
        $this->container = $container;
    }

    public function getAvailable()
    {
        return array_diff($this->container->getParameter('locales'), $this->container->getParameter('locales_hidden'));
    }


}
