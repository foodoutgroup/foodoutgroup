<?php

namespace Food\AppBundle\Traits;

use Symfony\Component\DependencyInjection\ContainerInterface;

trait Service
{
    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @return ContainerInterface
     */
    public function container(ContainerInterface $container = null)
    {
        if ($container) {
            $this->container = $container;
        } else {
            return $this->container;
        }
    }

    /**
     * @param string $service
     * @return object
     */
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

    /**
     * @param string $entity
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function repo($entity)
    {
        return $this->em()->getRepository($entity);
    }
}