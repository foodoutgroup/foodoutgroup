<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PlacePointAdmin extends FoodAdmin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('address', 'text', array('label' => 'admin.point.address'))
            ->add('city', 'text', array('label' => 'admin.point.city'))
            ->add('coords', 'text', array('label' => 'admin.point.coords'))
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