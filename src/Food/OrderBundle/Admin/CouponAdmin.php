<?php
namespace Food\OrderBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class CouponAdmin extends FoodAdmin
{
    /**
     * Fields to be shown on create/edit forms
     *
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text', array('label' => 'admin.coupon.name',))
            ->add('discountSum')
            ->add('fullOrderCovers', null, array('required' => false))
            ->add('discount', null, array('label' => 'admin.coupon.discount', 'required' => false))
            ->add('freeDelivery', null, array('label' => 'admin.coupon.free_delivery', 'required' => false))
            ->add('code', null, array('label' => 'admin.coupon.code', 'required' => true))
            ->add('places', null, array('label' => 'admin.coupon.place',))
            ->add('onlyNav', 'checkbox', array('label' => 'admin.coupon.only_nav', 'required' => false))
            ->add('noSelfDelivery','checkbox', array('required' => false))
            ->add('singleUse', 'checkbox', array('label' => 'admin.coupon.single_use', 'required' => false))
            ->add('enableValidateDate', 'checkbox', array('required' => false))
            ->add('validFrom', 'datetime', array('required' => false))
            ->add('validTo', 'datetime', array('required' => false))
            ->add('active', 'checkbox', array('label' => 'admin.coupon.active', 'required' => false));
        ;
    }

    /**
     * Fields to be shown on filter forms
     *
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'admin.coupon.name'))
            ->add('code', null, array('label' => 'admin.coupon.code'))
            ->add('active', null, array('label' => 'admin.coupon.active'))
            ->add('singleUse', null, array('label' => 'admin.coupon.single_use'))
            ->add('places', null, array('label' => 'admin.coupon.place'))
            ->add('onlyNav', null, array('label' => 'admin.coupon.only_nav'))
            ->add('freeDelivery', null, array('label' => 'admin.coupon.free_delivery'))
        ;
    }

    /**
     * Fields to be shown on lists
     *
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id', 'integer', array('label' => 'admin.coupon.id'))
            ->addIdentifier('name', 'string', array('label' => 'admin.coupon.name', 'editable' => true))
            ->add('discountSum', null, array('editable' => true))
            ->add('discount', null, array('label' => 'admin.coupon.discount', 'editable' => true))
            ->add('freeDelivery', null, array('label' => 'admin.coupon.free_delivery', 'editable' => true))
            ->add('code', null, array('label' => 'admin.coupon.code', 'editable' => false))
            ->add('places', null, array('label' => 'admin.coupon.place', 'editable' => true))
            ->add('onlyNav', null, array('label' => 'admin.coupon.only_nav', 'editable' => true))
            ->add('active', null, array('label' => 'admin.coupon.active', 'editable' => true))
            ->add('singleUse', null, array('label' => 'admin.coupon.single_use', 'editable' => true))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'edit', 'create', 'delete', 'export'));
    }
}
