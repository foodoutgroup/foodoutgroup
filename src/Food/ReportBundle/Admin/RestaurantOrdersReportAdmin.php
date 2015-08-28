<?php
namespace Food\ReportBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;


class RestaurantOrdersReportAdmin extends SonataAdmin
{
    protected $baseRouteName = 'food_reports_restaurant_orders_report';
    protected $baseRoutePattern = 'restaurant_reports';

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





























