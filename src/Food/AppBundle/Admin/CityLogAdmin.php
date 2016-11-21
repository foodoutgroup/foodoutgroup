<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class CityLogAdmin extends FoodAdmin
{

    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter
            ->add('city', null, array('label' => 'admin.cities'))
            ->add('user.email', null, array('label' => 'admin.users.email'))
            ->add('event_date', 'doctrine_orm_date', array(), null, array('widget' => 'single_text', 'required' => false,  'attr' => array('class' => 'datepicker2')))
        ;
    }

    protected function configureListFields(ListMapper $list)
    {
        $list
            ->addIdentifier('id', 'integer', array('label' => 'admin.param.id'))
            ->add('city', 'string', array('label' => 'admin.cities.title', 'editable' => false))
            ->add('oldValue', 'string', array('label' => 'admin.paramlog.old_value', 'editable' => false))
            ->add('newValue', 'string', array('label' => 'admin.paramlog.new_value', 'editable' => false))
            ->add('event_date', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.paramlog.event_date'))
            ->add('user', 'string', array('label' => 'admin.paramlog.user', 'editable' => false))
        ;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(array('list'));
    }
}
