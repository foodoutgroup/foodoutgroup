<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PlacePointFullAdmin extends FoodAdmin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('address', 'text', array('label' => 'Adresas'))
            ->add('city', 'text', array('label' => 'Miestas'))
            ->add('coords', 'text', array('label' => 'Koordinates'))
            ->add('wd1_start', 'text', array('label' => 'wd1_start'))
            ->add('wd1_end', 'text', array('label' => 'wd1_end'))
            ->add('wd2_start', 'text', array('label' => 'wd2_start'))
            ->add('wd2_end', 'text', array('label' => 'wd2_end'))
            ->add('wd3_start', 'text', array('label' => 'wd3_start'))
            ->add('wd3_end', 'text', array('label' => 'wd3_end'))
            ->add('wd4_start', 'text', array('label' => 'wd4_start'))
            ->add('wd4_end', 'text', array('label' => 'wd4_end'))
            ->add('wd5_start', 'text', array('label' => 'wd5_start'))
            ->add('wd5_end', 'text', array('label' => 'wd5_end'))
            ->add('wd6_start', 'text', array('label' => 'wd6_start'))
            ->add('wd6_end', 'text', array('label' => 'wd6_end'))
            ->add('wd7_start', 'text', array('label' => 'wd7_start'))
            ->add('wd7_end', 'text', array('label' => 'wd7_end'))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('city');
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('address')
            ->addIdentifier('city')
            ->addIdentifier('coords')
        ;
    }
}