<?php
namespace Food\SmsBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class MessagesAdmin extends FoodAdmin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('sender', 'text', array('label' => 'admin.sms.recipient', 'required' => true))
            ->add('message', null, array('label' => 'admin.sms.message', 'required' => true))
            ->add('sent', 'checkbox', array('required' => false, 'label' => 'admin.sms.sent'))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        // TODO
        $datagridMapper
            ->add('recipient', null, array('label' => 'admin.sms.recipient'))
            ->add('sent', null, array('label' => 'admin.sms.sent'))
            ->add('delivered', null, array('label' => 'admin.sms.delivered'))
            ->add('createdAt', null, array('label' => 'admin.created_at'))
            ->add('submittedAt', null, array('label' => 'admin.sms.submitted_at'))
            ->add('receivedAt', null, array('label' => 'admin.sms.received_at'))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('recipient', 'string', array('label' => 'admin.sms.recipient'))
            ->add('sender', 'string', array('label' => 'admin.sms.sender'))
            ->add('message', 'string', array('label' => 'admin.sms.message'))
            ->add('sent', null, array('label' => 'admin.sms.sent', 'editable' => true))
            ->add('delivered', null, array('label' => 'admin.sms.delivered', 'editable' => false))
            ->add('dlrId', 'string', array('label' => 'admin.sms.dlr_id'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('submittedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.sms.submitted_at'))
            ->add('receivedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.sms.received_at'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'show' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(\Sonata\AdminBundle\Show\ShowMapper $showMapper)
    {
        // TODO add all stats & translate all admin thing
        $showMapper
            ->add('recipient', 'string', array('label' => 'admin.sms.recipient'))
            ->add('sender', 'string', array('label' => 'admin.sms.sender'))
            ->add('message', 'string', array('label' => 'admin.sms.message'))
            ->add('sent', null, array('label' => 'admin.sms.sent', 'editable' => false))
            ->add('delivered', null, array('label' => 'admin.sms.delivered', 'editable' => false))
            ->add('dlrId', 'string', array('label' => 'admin.sms.dlr_id'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('submittedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.sms.submitted_at'))
            ->add('receivedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.sms.received_at'))
        ;
    }

    /**
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     * @inheritdoc
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'edit', 'show'));
    }

}
