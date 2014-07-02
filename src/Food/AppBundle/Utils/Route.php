<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Traits;
use Symfony\Component\DependencyInjection\Container;

class Route
{
    use Traits\Service;

    /**
     * @var string
     */
    private $locale;
    private $routeName;

    /**
     * @var array
     */
    private $routeParams;

    /**
     * @var array
     */
    private $queryParams;

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return Container
     */
    public function getContaner()
    {
        return $this->container;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @param string $name
     */
    public function setRouteName($name)
    {
        $this->routeName = $name;
    }

    /**
     * @param array $params
     */
    public function setRouteParams(array $params)
    {
        $this->routeParams = $params;
    }

    /**
     * @param array $params
     */
    public function setQueryParams(array $params)
    {
        $this->queryParams = $params;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param array $params
     * @param bool $absolute
     * @return mixed
     */
    public function getCurrentUrl(array $params, $absolute = false)
    {
        $router = $this->service('router');
        return $router->generate($this->routeName, array_merge($this->routeParams, $this->queryParams, $params), $absolute);
    }
}