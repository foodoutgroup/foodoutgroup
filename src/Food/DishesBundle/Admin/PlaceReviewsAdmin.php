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
            ->add('createdBy', 'entity', array(
                'class' => 'Food\UserBundle\Entity\User',
                'label' => 'admin.created_by',
                'disabled' => true
            ))
            ->add('review', 'textarea', array('label' => 'admin.place.review'))
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
            ->add('createdBy', null, array('label' => 'admin.created_by'))
            ->add('createdAt', null, array('label' => 'admin.created_at'))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('review', 'string', array('label' => 'admin.place.review'))
            ->add('createdBy', 'entity', array('label' => 'admin.created_by'));
        if ($this->isAdmin()) {
            $listMapper
                ->add('place', 'entity');
        }
        $listMapper
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.edited_at'))
            ->add('editedBy', 'entity', array('label' => 'admin.edited_by'))
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