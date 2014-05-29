<?php
namespace Food\SmsBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;


class MessagesAdmin extends SonataAdmin
{
    public function __construct($code, $class, $baseControllerName)
    {
        parent::__construct($code, $class, $baseControllerName);

        if (!$this->hasRequest()) {
            $this->datagridValues = array(
                '_page'       => 1,
                '_sort_order' => 'DESC',
                '_sort_by'    => 'createdAt'
            );
        }
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('recipient', 'text', array('label' => 'admin.sms.recipient', 'required' => true))
            ->add('sender', 'text', array('label' => 'admin.sms.sender', 'required' => true))
            ->add('message', null, array('label' => 'admin.sms.message', 'required' => true))
            ->add('sent', 'checkbox', array('required' => false, 'label' => 'admin.sms.sent'))
            ->add('delivered', 'checkbox', array('required' => false, 'label' => 'admin.sms.delivered'))
            ->add('createdAt', null, array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('submittedAt', null, array('format' => 'Y-m-d H:i:s', 'label' => 'admin.sms.submitted_at'))
            ->add('timesSent', null, array('required' => false, 'label' => 'admin.sms.times_sent'))
            ->add('smsc', null, array('required' => false, 'label' => 'admin.sms.smsc'))
            ->add('extId', null, array('required' => false, 'label' => 'admin.sms.ext_id'))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
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
            ->add('smsc', 'string', array('label' => 'admin.sms.smsc'))
            ->add('timesSent', null, array('label' => 'admin.sms.times_sent', 'editable' => true,))
            ->add('sent', null, array('label' => 'admin.sms.sent', 'editable' => true))
            ->add('delivered', null, array('label' => 'admin.sms.delivered', 'editable' => true))
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
        $showMapper
            ->add('recipient', 'string', array('label' => 'admin.sms.recipient'))
            ->add('sender', 'string', array('label' => 'admin.sms.sender'))
            ->add('message', 'string', array('label' => 'admin.sms.message'))
            ->add('smsc', 'string', array('label' => 'admin.sms.smsc'))
            ->add('sent', null, array('label' => 'admin.sms.sent', 'editable' => false))
            ->add('timesSent', null, array('label' => 'admin.sms.times_sent'))
            ->add('delivered', null, array('label' => 'admin.sms.delivered', 'editable' => false))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('submittedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.sms.submitted_at'))
            ->add('receivedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.sms.received_at'))
            ->add('lastErrorDate', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.sms.last_error_date'))
            ->add('lastSendingError', 'string', array('label' => 'admin.sms.last_sending_error'))
            ->add('extId', 'string', array('label' => 'admin.sms.ext_id'))
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
