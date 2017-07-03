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
                    'description' => ['attr' => ['class' => 'ckeditor_custom'], 'required' => true],
                ]
            ));

        $formMapper
            ->add('type', 'choice', [
                'choices' => [
                    'danger' => 'alert',
                    'warning' => 'warning',
                    'success' => 'success',
                    'info' => 'info',
                ]
            ])
            ->add('cityCollection', 'city', ['label' => 'admin.point.city', 'multiple' => true, 'required' => false])
            ->add('placeCollection', null, ['label' => 'admin.place', 'required' => false])
            ->add('showTill', null, ['label' => 'admin.show_till', 'required' => false])
            ->add('active', 'checkbox', ['label' => 'admin.active', 'required' => false])
            ;
    }


    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('description', null, ['label' => 'admin.title'])
            ->add('active', null, ['label' => 'admin.active']);
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id', null, array('label' => 'admin.id'))
            ->add('type', null, array('label' => 'admin.type'))
            ->add('placeCollection', null, array('label' => 'admin.place'))
            ->add('description', null, array('label' => 'admin.title'))
            ->add('showTill', null, ['label' => 'admin.show_till'])
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
