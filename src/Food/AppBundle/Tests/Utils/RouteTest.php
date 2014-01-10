<?php

namespace Food\AppBundle\Tests\Utils;

use Food\AppBundle\Utils\Route;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetContainers()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');
        $util = new Route();

        $locale = 'en';
        $queryParams = array(
            'lang' => 'lt',
            'page' => 62,
        );
        $routeParams = array(
            'page' => 47,
            'order' => 19,
        );

        $util->setContainer($container);
        $gotContainer = $util->getContaner();
        $this->assertEquals($container, $gotContainer);

        $util->setLocale($locale);
        $gotLocale = $util->getLocale();
        $this->assertEquals($locale, $gotLocale);
    }

    public function testGetCurrentUrl()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');
        $router = $this->getMockBuilder('\Symfony\Component\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();
        $util = new Route();

        $locale = 'en';
        $routeName = 'omgRoute';
        $queryParams = array(
            'lang' => 'lt',
            'page' => 62,
        );
        $routeParams = array(
            'page' => 47,
            'order' => 19,
        );
        $params = array('viens du' => '1 2');
        $absolute = false;
        $allParams = array_merge($routeParams, $queryParams, $params);

        $util->setContainer($container);
        $util->setLocale($locale);
        $util->setRouteName($routeName);
        $util->setQueryParams($queryParams);
        $util->setRouteParams($routeParams);

        $router->expects($this->once())
            ->method('generate')
            ->with($routeName, $allParams, $absolute)
            ->will($this->returnValue('zupa-project.lt'));

        $container->expects($this->once())
            ->method('get')
            ->with('router')
            ->will($this->returnValue($router));

        $dasUrl = $util->getCurrentUrl($params, $absolute);
        $this->assertEquals('zupa-project.lt', $dasUrl);
    }

}
