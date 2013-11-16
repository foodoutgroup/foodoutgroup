<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PlaceReviewsAdmin extends FoodAdmin
{

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        if ($this->isAdmin()) {
            $formMapper
                ->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place', 'disabled' => true));
        }
        $formMapper
            ->add('createdBy', 'entity', array('class' => 'Food\UserBundle\Entity\User'))
            ->add('review')
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        if ($this->isAdmin()) {
            $datagridMapper
                ->add('place');
        }
        $datagridMapper
            ->add('createdBy')
            ->add('createdAt')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('review')
            ->add('createdBy');
        if ($this->isAdmin()) {
            $listMapper
                ->add('place');
        }
        $listMapper
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
            ->add('editedBy')
        ;
    }
}