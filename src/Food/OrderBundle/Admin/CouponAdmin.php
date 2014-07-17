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
            ->add('discount', null, array('label' => 'admin.coupon.discount',))
            ->add('code', null, array('label' => 'admin.coupon.code', 'required' => true))
            ->add('place', null, array('label' => 'admin.coupon.place',))
            ->add('singleUse', 'checkbox', array('label' => 'admin.coupon.single_use', 'required' => false))
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
            ->add('place', null, array('label' => 'admin.coupon.place'))
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
            ->add('discount', null, array('label' => 'admin.coupon.discount', 'editable' => true))
            ->add('code', null, array('label' => 'admin.coupon.code', 'editable' => false))
            ->add('place', null, array('label' => 'admin.coupon.place', 'editable' => true))
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
        $collection->clearExcept(array('list', 'edit', 'create', 'delete'));
    }
}
