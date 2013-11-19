<?php

namespace Fish\ParadoBundle\Utils\Slug;

abstract class AbstractStrategy
{
    abstract public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container);
    abstract public function generate($langId);
}
