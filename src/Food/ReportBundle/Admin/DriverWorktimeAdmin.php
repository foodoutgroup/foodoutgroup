<?php
namespace Food\ReportBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;


class DriverWorktimeAdmin extends SonataAdmin
{
    protected $baseRouteName = 'food_reports_driver_worktime';
    protected $baseRoutePattern = 'reports/driverworktime';

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