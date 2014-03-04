<?php
namespace Food\OrderBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;


class OrderAdmin extends SonataAdmin
{
    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id', null, array('label' => 'admin.order.id'))
            ->add('order_date', null, array('label' => 'admin.order.order_date'))
//            ->add('place', null, array('label' => 'admin.order.place'))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id', 'integer', array('label' => 'admin.order.id', 'editable' => false))
            ->addIdentifier('order_date', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.order.order_date'))
            ->add('place_name', 'string', array('label' => 'admin.order.place_name_short', 'editable' => false,))
            ->add('place_point_address', 'string', array('label' => 'admin.order.place_point'))
            ->add('user', null, array('label' => 'admin.order.user'))
            ->add('address_id', null, array('label' => 'admin.order.address'))
            ->add('deliveryType', 'string', array('label' => 'admin.order.delivery_type'))
            ->add('order_status', 'string', array('label' => 'admin.order.order_status'))
            ->add('paymentMethod', 'string', array('label' => 'admin.order.payment_method'))
            ->add('paymentStatus', 'string', array('label' => 'admin.order.payment_status'))
            ->add('submittedForPayment', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.order.submitted_for_payment'))
            ->add('lastUpdate', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.order.last_update'))
            ->add('lastPaymentError', 'string', array('label' => 'admin.order.last_payment_error'))
            ->add('_action', 'actions', array(
                'actions' => array(
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
            ->add('id', null, array('label' => 'admin.order.id', 'editable' => false))
            ->add('order_date', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.order.order_date'))
            ->add('place_name', 'string', array('label' => 'admin.order.place_name_short', 'editable' => false,))
            ->add('place_point_address', 'string', array('label' => 'admin.order.place_point'))
            ->add('user', null, array('label' => 'admin.order.user'))
            ->add('address_id', null, array('label' => 'admin.order.address'))
            ->add('deliveryType', 'string', array('label' => 'admin.order.delivery_type'))
            ->add('details', null, array('label' => 'admin.order.details'))
            ->add('vat', 'string', array('label' => 'admin.order.vat'))
            ->add('comment', 'string', array('label' => 'admin.order.comment'))
            ->add('place_comment', 'string', array('label' => 'admin.order.place_comment'))
            ->add('order_status', 'string', array('label' => 'admin.order.order_status'))
            ->add('paymentMethod', 'string', array('label' => 'admin.order.payment_method'))
            ->add('paymentStatus', 'string', array('label' => 'admin.order.payment_status'))
            ->add('submittedForPayment', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.order.submitted_for_payment'))
            ->add('lastUpdate', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.order.last_update'))
            ->add('lastPaymentError', 'string', array('label' => 'admin.order.last_payment_error'))
        ;
    }

    /**
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     * @inheritdoc
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'show'));
    }

}
