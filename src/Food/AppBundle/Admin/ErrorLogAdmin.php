<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class ErrorLogAdmin extends FoodAdmin
{

    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter
            ->add('source', null, array('label' => 'admin.error.source'))
            ->add('createdAt', 'doctrine_orm_date', array(), null, array('widget' => 'single_text', 'required' => false,  'attr' => array('class' => 'datepicker2')))
        ;
    }

    protected function configureListFields(ListMapper $list)
    {
        $list
            ->addIdentifier('id', 'integer', array('label' => 'admin.id'))
            ->add('source', 'string', array('label' => 'admin.error.source'))
            ->add('description', 'string', array('label' => 'admin.error.description'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('createdBy', 'string', array('label' => 'admin.user'))
        ;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'show'));
    }
}
