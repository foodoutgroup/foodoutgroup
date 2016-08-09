<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * @package Food\DishesBundle\Admin
 */
class PlacePointFullAdmin extends FoodAdmin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('address', 'text', array('label' => 'admin.point.address'))
            ->add('company_code', 'text', array('label' => 'admin.point.company_code'))
            ->add('city', 'text', array('label' => 'admin.point.city'))
            ->add('internal_code', null, array('label' => 'admin.place.internal_code', 'required' => false,))
            ->add('phone', 'text', array('label' => 'admin.point.phone', 'attr' => array('placeholder'=>'3706xxxxxxx')))
            ->add('alt_phone1', 'text', array('label' => 'admin.point.alt_phone', 'required' => false, 'attr' => array('placeholder'=>'370xxxxxxx')))
            ->add('alt_phone2', 'text', array('label' => 'admin.point.alt_phone', 'required' => false, 'attr' => array('placeholder'=>'370xxxxxxx')))
            ->add('email', 'text', array('label' => 'admin.point.email', 'required' => false))
            ->add('alt_email1', 'text', array('label' => 'admin.point.alt_email', 'required' => false))
            ->add('alt_email2', 'text', array('label' => 'admin.point.alt_email', 'required' => false))
            ->add('invoice_email', 'text', array('label' => 'admin.point.invoice_email', 'required' => false))
            //->add('coords', 'text', array('label' => 'admin.point.coords'))
            ->add('delivery_time', 'text', array('label' => 'admin.point.devtime'))
            ->add('lat', 'text', array('label' => 'admin.point.lat','attr'=>array('placeholder'=>'xx.xxxxx')))
            ->add('lon', 'text', array('label' => 'admin.point.lon','attr'=>array('placeholder'=>'xx.xxxxx')))
            ->add('public',null, array('label' => 'admin.point.public', 'required' => false))
            ->add('pickUp',null, array('label' => 'admin.point.pickup', 'required' => false))
            ->add('delivery', null, array('label' => 'admin.point.delivery', 'required' => false))
            ->add('active', null, array('label' => 'admin.point.active', 'required' => false))
            ->add('fast', null, array('label' => 'admin.point.fast', 'required' => false))
            ->add('allowCash', null, array('label' => 'admin.point.allow_cash', 'required' => false))
            ->add('allowCard', null, array('label' => 'admin.point.allow_card', 'required' => false))
            ->add('useExternalLogistics', null, array('label' => 'admin.point.use_external_logistics', 'required' => false))
            ->with('admin.point.work_time')
            ->add('wd1', 'text', array('label' => 'admin.point.wd1'))
            ->add('wd2', 'text', array('label' => 'admin.point.wd2'))
            ->add('wd3', 'text', array('label' => 'admin.point.wd3'))
            ->add('wd4', 'text', array('label' => 'admin.point.wd4'))
            ->add('wd5', 'text', array('label' => 'admin.point.wd5'))
            ->add('wd6', 'text', array('label' => 'admin.point.wd6'))
            ->add('wd7', 'text', array('label' => 'admin.point.wd7'))

            ->end()
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('city', null, array('label' => 'admin.point.city'));
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('place', null, array('label' => 'admin.point.place'))
            ->addIdentifier('address', 'string', array('label' => 'admin.point.address'))
            ->addIdentifier('city', 'string', array('label' => 'admin.point.city'))
            ->addIdentifier('active', 'boolean', array('label' => 'admin.point.active', 'editable' => true,))
            ->addIdentifier('fast', 'boolean', array('label' => 'admin.point.fast', 'editable' => true))
            ->add('allowCash', 'boolean', array('label' => 'admin.point.allow_cash', 'editable' => true))
            ->add('allowCard', 'boolean', array('label' => 'admin.point.allow_card', 'editable' => true))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }
}
