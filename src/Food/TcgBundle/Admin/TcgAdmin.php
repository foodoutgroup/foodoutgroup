<?php

namespace Food\TcgBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;


class TcgAdmin extends SonataAdmin
{
    public function __construct($code, $class, $baseControllerName)
    {
        parent::__construct($code, $class, $baseControllerName);

        if (!$this->hasRequest()) {
            $this->datagridValues = array(
                '_page' => 1,
                '_sort_order' => 'DESC',
                '_sort_by' => 'createdAt'
            );
        }
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('order.id', null, array('required' => false, 'label' => 'admin.tcg.orderid'))
            ->add('createdAt', null, array('required' => false, 'label' => 'admin.tcg.created_at'))
            ->add('submittedAt', null, array('required' => false, 'label' => 'admin.tcg.submitted_at'))
            ->add('error', null, array('required' => false, 'label' => 'admin.tcg.error'))
            ->add('sent', null, array('required' => false, 'label' => 'admin.tcg.sent'))
            ->add('phone', null, array('required' => false, 'label' => 'admin.tcg.phone'));
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('order.id', null, array('label' => 'admin.tcg.order'))
            ->add('sent', null, array('label' => 'admin.sms.sent'))
            ->add('createdAt', 'doctrine_orm_date_range', array('label' => $this->trans('admin.tcg.created_at')), null, array('widget' => 'single_text', 'required' => false, 'attr' => array('class' => 'datepicker2')))
            ->add('submitedAt', 'doctrine_orm_date_range', array('label' => $this->trans('admin.tcg.submitted_at')), null, array('widget' => 'single_text', 'required' => false, 'attr' => array('class' => 'datepicker2')));

    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('order.id', null, array('required' => false, 'label' => 'admin.tcg.orderid'))
            ->add('createdAt', null, array('required' => false, 'label' => 'admin.tcg.created_at'))
            ->add('submittedAt', null, array('required' => false, 'label' => 'admin.tcg.submitted_at'))
            ->add('error', null, array('required' => false, 'label' => 'admin.tcg.error'))
            ->add('sent', null, array('required' => false, 'label' => 'admin.tcg.sent'))
            ->add('phone', null, array('required' => false, 'label' => 'admin.tcg.phone'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),

                ),
                'label' => 'admin.actions'
            ));
    }

    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(\Sonata\AdminBundle\Show\ShowMapper $showMapper)
    {
        $showMapper
            ->add('order.id', null, array('required' => false, 'label' => 'admin.tcg.orderid'))
            ->add('createdAt', null, array('required' => false, 'label' => 'admin.tcg.created_at'))
            ->add('submittedAt', null, array('required' => false, 'label' => 'admin.tcg.submitted_at'))
            ->add('error', null, array('required' => false, 'label' => 'admin.tcg.error'))
            ->add('sent', null, array('required' => false, 'label' => 'admin.tcg.sent'))
            ->add('phone', null, array('required' => false, 'label' => 'admin.tcg.phone'));


    }

    /**
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     * @inheritdoc
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'edit', 'show', 'create'));
    }

}
