<?php

namespace Food\PushBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;


class PushesAdmin extends SonataAdmin
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
            ->add('token', 'text', array('label' => 'admin.push.token', 'required' => true))
            ->add('message', null, array('label' => 'admin.push.message', 'required' => true))
            ->add('sent', 'checkbox', array('required' => false, 'label' => 'admin.push.sent'))
            ->add('createdAt', null, array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('error', null, array('required' => false, 'label' => 'admin.push.error'))
//            ->add('order', null, array('required' => false, 'label' => 'admin.push.orderid'))
            ->add('submittedAt', null, array('label' => 'admin.push.submitted', 'required' => true));
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('sent', null, array('label' => 'admin.sms.sent'))
            ->add('createdAt', null, array('label' => 'admin.created_at'));
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('token', 'string', array('label' => 'admin.push.token'))
            ->add('message', 'string', array('label' => 'admin.push.message'))
            ->add('sent', null, array('label' => 'admin.push.sent', 'editable' => true))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('error', null, array('required' => false, 'label' => 'admin.push.error'))
//            ->add('order', null, array('required' => false, 'label' => 'admin.push.orderid'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'show' => array(),
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
            ->add('token', 'string', array('label' => 'admin.push.sender'))
            ->add('message', 'string', array('label' => 'admin.push.message'))
            ->add('sent', null, array('label' => 'admin.push.sent', 'editable' => false))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('error', null, array('required' => false, 'label' => 'admin.push.error'));
//            ->add('order', null, array('required' => false, 'label' => 'admin.push.orderid'));
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
