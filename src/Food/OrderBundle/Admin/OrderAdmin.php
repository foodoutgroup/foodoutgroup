<?php
namespace Food\OrderBundle\Admin;

use Food\OrderBundle\Entity\Order;
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

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('company', 'checkbox', array('label' => 'admin.order.company', 'required' => false))
            ->add('companyName', 'text', array('label' => 'admin.order.companyName', 'required' => false))
            ->add('companyCode', 'text', array('label' => 'admin.order.companyCode', 'required' => false))
            ->add('vatCode', 'text', array('label' => 'admin.order.vatCode', 'required' => false))
            ->add('companyAddress', 'text', array('label' => 'admin.order.companyAddress', 'required' => false))
        ;

        /**
         * @var Order $order
         */
        $order = $this->getSubject();
        // TODO nutarem su buhalterija, kad visiems rasome - maistas ir nedetalizuojame patiekalu
//        if ($order->getOrderFromNav()) {
        $formMapper->add('total', null, array('label' => 'admin.order.total'));
        $formMapper->add('delivery_price', 'number', array('label' => 'admin.order.delivery_price'));
        /*} else {
            // Non Nav order should not let edit total
            $formMapper->add('total', null, array('label' => 'admin.order.total', 'disabled' => true));
        }*/
    }

    /**
     * @param DatagridMapper $datagridMapper
     *
     * @codeCoverageIgnore
     */
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
            //->add('address_id', null, array('label' => 'admin.order.delivery_address'))
            ->add('place_name', null, array('label' => 'admin.order.place_name_short',))
            //->add('order_date', 'doctrine_orm_date_range', array('label' => 'admin.order.order_date'))
            ->add('order_date', 'doctrine_orm_date_range', array(), null, array('widget' => 'single_text', 'required' => false,  'attr' => array('class' => 'datepicker2')))
            //->add('order_date', 'doctrine_orm_date_range', array(), null, array( 'required' => false,  'attr' => array('class' => 'datepicker')))
            ->add('city', 'doctrine_orm_callback', array('callback'   => array($this, 'userCityFilter'), 'field_type' => 'text'))
            ->add('address', 'doctrine_orm_callback', array('callback'   => array($this, 'userAddressFilter'), 'field_type' => 'text'))
            ->add('phone', 'doctrine_orm_callback', array('callback'   => array($this, 'userPhoneFilter'), 'field_type' => 'text'))
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
//            ->add('companyName', null, array('label' => 'admin.order.companyName'))
//            ->add('companyCode', null, array('label' => 'admin.order.companyCode'))
            ->add('total', null, array('label' => 'admin.order.total'))
            ->add('couponCode', null, array('label' => 'admin.order.coupon_code'))
            ->add('mobile', null, array('label' => 'admin.order.ismobile_full'))
            ->add('navDeliveryOrder', null, array('label' => 'admin.order.nav_delivery_order'))
            ->add('sfNumber', null, array('label' => 'admin.order.sf_line'))
            ->add('orderFromNav', null, array('label' => 'admin.order.order_from_nav'))
        ;
    }


    public function userCityFilter($queryBuilder, $alias, $field, $value)
    {
        if (!$value || empty($value['value'])) {
            return;
        }

        $queryBuilder->join(sprintf('%s.address_id', $alias), 'a');
        $queryBuilder->andWhere("a.city LIKE :thecity");
        $queryBuilder->setParameter('thecity', '%'.$value['value'].'%');

        return true;
    }


    public function userAddressFilter($queryBuilder, $alias, $field, $value)
    {
        if (!$value || empty($value['value'])) {
            return;
        }

        $queryBuilder->join(sprintf('%s.address_id', $alias), 'ad');
        $queryBuilder->andWhere("ad.address LIKE :theaddress");
        $queryBuilder->setParameter('theaddress', '%'.$value['value'].'%');

        return true;
    }


    public function userPhoneFilter($queryBuilder, $alias, $field, $value)
    {
        if (!$value || empty($value['value'])) {
            return;
        }

        $queryBuilder->join(sprintf('%s.user', $alias), 'fu');
        $queryBuilder->andWhere("fu.phone LIKE :thephone");
        $queryBuilder->setParameter('thephone', '%'.str_replace("+", "", $value['value']).'%');

        return true;
    }

    /**
     * @param ListMapper $listMapper
     *
     * @codeCoverageIgnore
     */
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
            ->add('paymentMethod', 'string', array('label' => 'admin.order.payment_method_short'))
            ->add('paymentStatus', 'string', array('label' => 'admin.order.payment_status'))
            ->add('mobile', null, array('label' => 'admin.order.ismobile'))
            ->add('orderFromNav', null, array('label' => 'admin.order.order_from_nav'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'sendInvoice' => array(
                        'template' => 'FoodOrderBundle:CRUD:list__action_sendInvoice.html.twig'
                    ),
                    'downloadInvoice' => array(
                        'template' => 'FoodOrderBundle:CRUD:list__action_downloadInvoice.html.twig'
                    ),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
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
            ->add('company', 'sonata_type_collection',
                array(
                    'label' => 'admin.order.company',
                    'template' => 'FoodOrderBundle:Admin:order_company_data.html.twig'
                )
            )
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
            ->add('deliveryPrice', 'string', array('label' => 'admin.order.delivery_price'))
            ->add('couponCode', 'string', array('label' => 'admin.order.coupon_code'))
            ->add('discountSize', 'string', array('label' => 'admin.order.discount_size'))
            ->add('discountSum', 'string', array('label' => 'admin.order.discount_sum'))
            ->add('sfLine', 'string', array('label' => 'admin.order.sf_line'))
            ->add('comment', 'string', array('label' => 'admin.order.comment'))
            ->add('place_comment', 'string', array('label' => 'admin.order.place_comment'))
            ->add('order_status', 'sonata_type_collection',
                array(
                    'label' => 'admin.order.order_status',
                    'template' => 'FoodOrderBundle:Admin:order_status_list.html.twig'
                )
            )
            ->add('paymentMethod', 'string', array('label' => 'admin.order.payment_method'))
            ->add('paymentStatus', 'sonata_type_collection',
                array(
                    'label' => 'admin.order.payment_status',
                    'template' => 'FoodOrderBundle:Admin:order_payment_status_list.html.twig'
                )
            )
            ->add('orderLog', 'sonata_type_collection',
                array(
                    'label' => 'admin.order.order_log',
                    'template' => 'FoodOrderBundle:Admin:order_log.html.twig'
                )
            )
            ->add('submittedForPayment', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.order.submitted_for_payment'))
            ->add('driverContact', null, array('label' => 'admin.order.driver'))
            ->add('lastUpdate', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.order.last_update'))
            ->add('lastPaymentError', 'string', array('label' => 'admin.order.last_payment_error'))
            ->add('orderHash', 'string', array('label' => 'admin.order.hash'))
            ->add('mobile', null, array('label' => 'admin.order.ismobile'))
            ->add('orderFromNav', null, array('label' => 'admin.order.order_from_nav'))
            ->add('navDeliveryOrder', null, array('label' => 'admin.order.nav_delivery_order'))
            ->add('clientContacted', null, array('label' => 'admin.order.client_contacted'))
        ;
    }

    /**
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('edit', 'list', 'show', 'export'))
            ->add('sendInvoice', $this->getRouterIdParameter().'/sendInvoice')
            ->add('downloadInvoice', $this->getRouterIdParameter().'/downloadInvoice');
    }

}
