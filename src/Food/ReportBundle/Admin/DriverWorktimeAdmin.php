<?php
namespace Food\ReportBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;


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
        $collection->clearExcept(array('list', 'export'));
    }

    /**
     * @param DatagridMapper $datagridMapper
     *
     * @codeCoverageIgnore
     */
    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        parent::configureDatagridFilters($filter);
        $filter
            ->add('id', null, array('label' => 'admin.reports.user_id'))
            ->add('type', null, array('label' => 'admin.reports.driver_type'))
        ;
    }

    /**
     * @param ListMapper $listMapper
     *
     * @codeCoverageIgnore
     */
    protected function configureListFields(ListMapper $list)
    {
        parent::configureListFields($list);
        $list
            ->add('id', null, array('label' => 'admin.reports.user_id'))
            ->add('name', null, array('label' => 'admin.reports.firstname'))
        ;
    }
}