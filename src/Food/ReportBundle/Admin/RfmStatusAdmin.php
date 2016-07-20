<?php
namespace Food\ReportBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;

class RfmStatusAdmin extends FoodAdmin {

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(\Sonata\AdminBundle\Datagrid\ListMapper $listMapper)
    {
        $listMapper
            ->add('from', 'text', array('label' => 'admin.rfm_status.from'))
            ->add('to', 'text', array('label' => 'admin.rfm_status.to'))
            ->add('title', 'text', array('label' => 'admin.rfm_status.title'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(\Sonata\AdminBundle\Datagrid\DatagridMapper $filterMapper)
    {
        $filterMapper
            ->add('id')
            ->add('from', null, array('label' => 'admin.rfm_status.from'))
            ->add('to', null, array('label' => 'admin.rfm_status.to'))
            ->add('title', null, array('label' => 'admin.rfm_status.title'))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(\Sonata\AdminBundle\Form\FormMapper $formMapper)
    {
        $formMapper
            ->add('from', 'text', array('label' => 'admin.rfm_status.from', 'required' => false))
            ->add('to', 'text', array('label' => 'admin.rfm_status.to', 'required' => false))
            ->add('title', 'text', array('label' => 'admin.rfm_status.title'))
        ;
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'edit', 'create', 'delete'));
    }
}
