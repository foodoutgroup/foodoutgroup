<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Traits;

class Route
{
    use Traits\Service;

    private $locale;
    private $routeName;
    private $routeParams;
    private $queryParams;

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function setRouteName($name)
    {
        $this->routeName = $name;
    }

    public function setRouteParams(array $params)
    {
        $this->routeParams = $params;
    }

    public function setQueryParams(array $params)
    {
        $this->queryParams = $params;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getCurrentUrl(array $params, $absolute = false)
    {
        $router = $this->service('router');
        return $router->generate($this->routeName, array_merge($this->routeParams, $this->queryParams, $params), $absolute);
    }
}