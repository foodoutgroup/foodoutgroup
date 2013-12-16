<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * TODO ar neturetu sita klase extendinti PlacePointAdmin????!!!!
 *
 * @package Food\DishesBundle\Admin
 */
class PlacePointFullAdmin extends FoodAdmin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('address', 'text', array('label' => 'admin.point.address'))
            ->add('city', 'text', array('label' => 'admin.point.city'))
            ->add('coords', 'text', array('label' => 'admin.point.coords'))
            ->add('pickUp',null, array('label' => 'admin.point.pickup'))
            ->add('delivery', null, array('label' => 'admin.point.delivery'))
            ->add('delivery_time', 'text', array('label' => 'admin.point.devtime'))
            ->add('active', null, array('label' => 'admin.point.active'))
            ->with('admin.point.work_time')
            ->add('wd1_start', 'text', array('label' => 'admin.point.wd1_start'))
            ->add('wd1_end', 'text', array('label' => 'admin.point.wd_end'))
            ->add('wd2_start', 'text', array('label' => 'admin.point.wd2_start'))
            ->add('wd2_end', 'text', array('label' => 'admin.point.wd_end'))
            ->add('wd3_start', 'text', array('label' => 'admin.point.wd3_start'))
            ->add('wd3_end', 'text', array('label' => 'admin.point.wd_end'))
            ->add('wd4_start', 'text', array('label' => 'admin.point.wd4_start'))
            ->add('wd4_end', 'text', array('label' => 'admin.point.wd_end'))
            ->add('wd5_start', 'text', array('label' => 'admin.point.wd5_start'))
            ->add('wd5_end', 'text', array('label' => 'admin.point.wd_end'))
            ->add('wd6_start', 'text', array('label' => 'admin.point.wd6_start'))
            ->add('wd6_end', 'text', array('label' => 'admin.point.wd_end'))
            ->add('wd7_start', 'text', array('label' => 'admin.point.wd7_start'))
            ->add('wd7_end', 'text', array('label' => 'admin.point.wd_end'))
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
            ->addIdentifier('address', 'string', array('label' => 'admin.point.address'))
            ->addIdentifier('city', 'string', array('label' => 'admin.point.city'))
            ->addIdentifier('coords', 'string', array('label' => 'admin.point.coords'))
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