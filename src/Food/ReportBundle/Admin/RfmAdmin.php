<?php
namespace Food\ReportBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;


class RfmAdmin extends SonataAdmin
{
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
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('userId', null, array('label' => 'admin.reports.user_id'))
            ->add('email', null, array('label' => 'admin.reports.email'))
            ->add('phone', null, array('label' => 'admin.reports.phone'))
            ->add('firstname', null, array('label' => 'admin.reports.firstname'))
            ->add('lastname', null, array('label' => 'admin.reports.lastname'))
            ->add('isBusinessClient', null, array('label' => 'admin.reports.is_business_client'))
            ->add('companyName', null, array('label' => 'admin.reports.company_name'))
            ->add('firstOrderDate', 'doctrine_orm_date_range', array('label' => 'admin.reports.first_order_date'), null, array('widget' => 'single_text', 'required' => false,  'attr' => array('class' => 'datepicker2')))
            ->add('lastOrderDate', 'doctrine_orm_date_range', array('label' => 'admin.reports.last_order_date'), null, array('widget' => 'single_text', 'required' => false,  'attr' => array('class' => 'datepicker2')))
            ->add('recencyScore', null, array('label' => 'admin.reports.recency_score'))
            ->add('frequencyScore', null, array('label' => 'admin.reports.frequency_score'))
            ->add('monetaryScore', null, array('label' => 'admin.reports.monetary_score'))
            ->add('totalRfmScore', null, array('label' => 'admin.reports.total_rfm_score'))
        ;
    }

    /**
     * @param ListMapper $listMapper
     *
     * @codeCoverageIgnore
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('userId', null, array('label' => 'admin.reports.user_id'))
            ->add('email', 'string', array('label' => 'admin.reports.email', 'editable' => false,))
            ->add('phone', 'string', array('label' => 'admin.reports.phone'))
            ->add('isBusinessClient', null, array('label' => 'admin.reports.is_business_client'))
            ->add('recencyScore', 'string', array('label' => 'admin.reports.recency_score'))
            ->add('frequencyScore', 'string', array('label' => 'admin.reports.frequency_score'))
            ->add('monetaryScore', 'string', array('label' => 'admin.reports.monetary_score'))
            ->add('totalRfmScore', 'string', array('label' => 'admin.reports.total_rfm_score'))
        ;
    }
}