<?php
namespace Food\DishesBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class FoodCategoryAdmin extends Admin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text', array('label' => 'Dish name'))
            ->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place'))
            ->add('active', 'checkbox', array('label' => 'Dish name2'))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add('createdAt')
            ->add('editedAt')
            ->add('deletedAt')
            ->add('place')
            ->add('active')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->add('place')
            ->add('date', 'datetime')
            ->add('active', 'checkbox')
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
            ->add('deletedAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
        ;
    }
}
