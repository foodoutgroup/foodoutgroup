<?php
namespace Food\ReportBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class OrdersByRestaurantAdmin extends SonataAdmin
{
    protected $baseRouteName = 'food_reports_orders_by_restaurant_report';
    protected $baseRoutePattern = 'reports/ordersbyrestaurant';
    protected $datagridValues = array(
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_per_page' => 100,
    );

    public function configureListFields(ListMapper $list)
    {
        parent::configureListFields($list);

        $list->add('filename', null, array('label' => 'admin.report.filename', 'template' => 'FoodReportBundle:Report/OrdersByRestaurant:download.html.twig'))
            ->add('type', 'choice',
                array('choices' => array(
                    1 => $this->trans('restaurant_report_type'),
                    2 => $this->trans('custom_report_type'),
                ), 'label' => 'admin.report.type'
            ))
            ->add('dateFrom', null, array('format' => 'Y-m-d', 'label' => 'admin.report.date_from'))
            ->add('dateTo', null, array('format' => 'Y-m-d', 'label' => 'admin.report.date_to'))
            ->add('restaurants', null, array('label' => 'admin.report.restaurants'))
            ->add('createdAt', null, array('format' => 'Y-m-d H:i:s', 'label' => 'admin.report.date_created'))
        ;
    }

    public function configureRoutes(RouteCollection $collection)
    {
        parent::configureRoutes($collection);

        $collection->clearExcept(array('list'));
    }
}





























