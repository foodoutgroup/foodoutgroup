<?php
namespace Food\AppBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;


class DispatcherAdmin extends SonataAdmin
{
    protected $baseRouteName = 'food_dispatcher_admin';
    protected $baseRoutePattern = 'dispatcher';
    /**
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     * @inheritdoc
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'show'));
    }
}





























