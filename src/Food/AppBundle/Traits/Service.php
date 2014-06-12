<?php

namespace Food\AppBundle\Traits;

use Symfony\Component\DependencyInjection\ContainerInterface;

trait Service
{
    public function container(ContainerInterface $container = null)
    {
        if ($container) {
            $this->container = $container;
        } else {
            return $this->container;
        }
    }

    public function service($service)
    {
        return $this->container()->get($service);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function em()
    {
        return $this->service('doctrine')->getManager();
    }

    public function repo($entity)
    {
        return $this->em()->getRepository($entity);
    }
}