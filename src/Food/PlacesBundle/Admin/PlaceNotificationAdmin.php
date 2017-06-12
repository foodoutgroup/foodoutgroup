<?php

namespace Food\PlacesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PlaceNotificationAdmin extends FoodAdmin
{

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {

        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\PlacesBundle\Entity\PlaceNotification',
                'fields' => [
                    'description' => [],
                ]
            ));

        $formMapper
            ->add('type', 'choice', [
                'choices' => [
                    'alert' => 'red',
                    'warning' => 'yellow',
                    'success' => 'green',
                    'info' => 'blue',
                ]
            ])
            ->add('cityCollection', 'city', ['label' => 'admin.point.city', 'multiple' => true, 'required' => false])
            ->add('placeCollection', null, ['label' => 'admin.place', 'required' => false])
            ->add('active', 'checkbox', ['label' => 'admin.active', 'required' => false])
            ;
    }


    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title', null, ['label' => 'admin.title']);
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('title', 'string', array('label' => 'admin.title'))
            ->add('active', 'boolean', array('label' => 'admin.active', 'editable' => true))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ));
    }
}
