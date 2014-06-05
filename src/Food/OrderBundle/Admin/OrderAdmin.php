<?php
namespace Food\OrderBundle\Admin;

use Food\OrderBundle\Service\OrderService;
use Sonata\AdminBundle\Admin\Admin as SonataAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;


class OrderAdmin extends SonataAdmin
{
    public function __construct($code, $class, $baseControllerName)
    {
        parent::__construct($code, $class, $baseControllerName);

        if (!$this->hasRequest()) {
            $this->datagridValues = array(
                '_page'       => 1,
                '_sort_order' => 'DESC',
                '_sort_by'    => 'order_date',
            );
        }
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $statusChoices = array();
        $allOrderStatuses = OrderService::getOrderStatuses();
        foreach ($allOrderStatuses as $status) {
            $statusChoices[$status] = $this->trans('admin.dispatcher.order_status.'.$status);
        }

        $paymentStatusChoices = array();
        $allPaymentStatuses = OrderService::getPaymentStatuses();
        foreach ($allPaymentStatuses as $status) {
            $paymentStatusChoices[$status] = $this->trans('admin.order.payment_status.'.$status);
        }

        $datagridMapper
            ->add('id', null, array('label' => 'admin.order.id'))
            ->add('address_id', null, array('label' => 'admin.order.delivery_address'))
            ->add('place_name', null, array('label' => 'admin.order.place_name_short',))
            ->add('order_date', null, array('label' => 'admin.order.order_date'))
            ->add('userIp', null, array('label' => 'admin.order.user_ip'))
            ->add('order_status',null, array('label' => 'admin.order.order_status'), 'choice', array(
                'choices' => $statusChoices
            ))
            ->add('paymentStatus',null, array('label' => 'admin.order.payment_status'), 'choice', array(
                'choices' => $paymentStatusChoices
            ))
            ->add('place_point_self_delivery',null, array('label' => 'admin.order.self_delivery'), 'choice', array(
                'choices' => array(
                    '1' => $this->trans('label_type_yes'),
                )
            ))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id', 'integer', array('label' => 'admin.order.id', 'editable' => false))
            ->addIdentifier('order_date', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.order.order_date'))
            ->add('address_id', null, array('label' => 'admin.order.delivery_address'))
            ->add('place_name', 'string', array('label' => 'admin.order.place_name_short', 'editable' => false,))
            ->add('place_point_address', 'string', array('label' => 'admin.order.place_point_short'))
            ->add('deliveryType', 'string', array('label' => 'admin.order.delivery_type_short'))
            ->add('order_status', 'string', array('label' => 'admin.order.order_status_short'))
            ->add('paymentMethod', 'string', array('label' => 'admin.order.payment_method'))
            ->add('paymentStatus', 'string', array('label' => 'admin.order.payment_status'))
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
            ->add('place_name', 'string', array('label' => 'admin.order.place_name', 'editable' => false,))
            ->add('place_point_address', 'string', array('label' => 'admin.order.place_point'))
            ->add('user.contact', null, array('label' => 'admin.order.user'))
            ->add('address_id', null, array('label' => 'admin.order.delivery_address'))
            ->add('userIp', null, array('label' => 'admin.order.user_ip'))
            ->add('deliveryType', 'string', array('label' => 'admin.order.delivery_type'))
            ->add('details', 'sonata_type_collection',
                array(
                    'label' => 'admin.order.details',
                    'template' => 'FoodOrderBundle:Admin:show_details_list.html.twig'
                )
            )
            ->add('vat', 'string', array('label' => 'admin.order.vat'))
            ->add('total', 'string', array('label' => 'admin.order.total'))
            ->add('comment', 'string', array('label' => 'admin.order.comment'))
            ->add('place_comment', 'string', array('label' => 'admin.order.place_comment'))
            ->add('order_status', 'sonata_type_collection',
                array(
                    'label' => 'admin.order.order_status',
                    'template' => 'FoodOrderBundle:Admin:order_status_list.html.twig'
                )
            )
            ->add('paymentMethod', 'string', array('label' => 'admin.order.payment_method'))
            ->add('paymentStatus', 'string', array('label' => 'admin.order.payment_status'))
            ->add('submittedForPayment', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.order.submitted_for_payment'))
            ->add('driver.contact', null, array('label' => 'admin.order.driver'))
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
