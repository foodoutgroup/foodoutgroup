<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class CityAdmin extends FoodAdmin
{

    function configureListFields(ListMapper $list)
    {
        $list
            ->add('title', null, array('label' => 'admin.cities.title', 'editable' => true))
            ->add('zavalas_on', 'boolean', array('label' => 'admin.cities.zavalas_on', 'editable' => true))
            ->add('zavalas_time', null, array('label' => 'admin.cities.zavalas_time', 'editable' => true))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    function configureFormFields(FormMapper $form)
    {
        $form
            ->add('title', 'text', array('label' => 'admin.cities.title', 'required' => true))
            ->add('zavalas_on', 'checkbox', array('label' => 'admin.cities.zavalas_on', 'required' => false))
            ->add('zavalas_time', 'text', array('label' => 'admin.cities.zavalas_time', 'required' => false))
        ;
    }
}
