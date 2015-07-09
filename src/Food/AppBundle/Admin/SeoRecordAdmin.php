<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class SeoRecordAdmin extends FoodAdmin
{
    /**
     * Fields to be shown on create/edit forms
     *
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', null, array('label' => 'admin.seorecord.name', 'required' => true))
            ->add('title', null, array('label' => 'admin.seorecord.title', 'required' => false))
            ->add('description', null, array('label' => 'admin.seorecord.description', 'required' => false))
            ->add('places', 'entity', array('label' => 'admin.seorecord.places', 'class' => 'Food\DishesBundle\Entity\Place', 'required' => true, 'multiple' => true))
        ;
    }

    /**
     * Fields to be shown on filter forms
     *
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'admin.seorecord.name'))
            ->add('title', null, array('label' => 'admin.seorecord.title'))
        ;
    }

    /**
     * Fields to be shown on lists
     *
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', 'string', array('label' => 'admin.seorecord.name'))
            ->add('title', 'string', array('label' => 'admin.seorecord.title', 'editable' => true))
            ->add('description', 'string', array('label' => 'admin.seorecord.description', 'editable' => true))
            ->add('places', 'string', array('template' => 'FoodAppBundle:SeoRecord:admin_list_places.html.twig', 'label' => 'admin.seorecord.places'))
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
