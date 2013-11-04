<?php

namespace Food\CartBundle\Service;


class Cart {
    private $container;
    private $userId;

    public function __construct(\Symfony\Component\DependencyInjection\Container $container, $userId)
    {
        $this->container = $container;
        $this->userId = $userId;
    }
}