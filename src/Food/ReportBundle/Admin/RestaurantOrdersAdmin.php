<?php
namespace Food\ReportBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;


class RestaurantOrdersAdmin extends SonataAdmin
{
    protected $baseRouteName = 'food_reports_restaurant_orders';
    protected $baseRoutePattern = 'reports';

    /**
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list'));
    }
}





























