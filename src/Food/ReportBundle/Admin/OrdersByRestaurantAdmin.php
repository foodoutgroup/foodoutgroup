<?php
namespace Food\ReportBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;

class OrdersByRestaurantAdmin extends SonataAdmin
{
    protected $baseRouteName = 'food_reports_orders_by_restaurant_report';
    protected $baseRoutePattern = 'reports/ordersbyrestaurant';

    /**
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list'));
        $collection->add('generate');
    }
}





























