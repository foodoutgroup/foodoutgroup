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
        $filter
            ->add('id', null, array('label' => 'admin.driver.id'))
            ->add('name', null, array('label' => 'admin.driver.type'))
            ->add('type', null, array('label' => 'admin.driver.name'))
        ;
    }

    /**
     * @param ListMapper $listMapper
     *
     * @codeCoverageIgnore
     */
    protected function configureListFields(ListMapper $list)
    {
        $list
            ->add('name', null, array('label' => 'admin.driver.name'))
        ;
        for ($i=1;$i<=31;$i++) {
            $list->add('day'.$i, null, array('label' => $i));
        }
    }
}