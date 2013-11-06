<?php
namespace Food\DishesBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PlaceAdmin extends Admin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text', array('label' => 'Dish name'))
            ->add('kitchens', 'entity', array('multiple'=>true, 'class' => 'Food\DishesBundle\Entity\Kitchen'))
            ->add('active', 'checkbox', array('label' => 'Dish name2'))
            ->add('logo', 'file', array('required' => false))
          //  ->add('categories', 'entity', array('class' => 'Food\DishesBundle\Entity\FoodCategory'))
          //  ->add('price') //if no type is specified, SonataAdminBundle tries to guess it
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')

//            ->add('place')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
//            ->add('place')
            ->add('logo');
        ;
    }
}