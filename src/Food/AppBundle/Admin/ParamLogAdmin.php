<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class ParamLogAdmin extends FoodAdmin
{

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
            ->add('param', null, array('label' => 'admin.param.name'))
            ->add('user.email', null, array('label' => 'admin.users.email'))
            ->add('event_date', 'doctrine_orm_date', array(), null, array('widget' => 'single_text', 'required' => false,  'attr' => array('class' => 'datepicker2')))
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
            ->addIdentifier('id', 'integer', array('label' => 'admin.param.id'))
            ->add('param', 'string', array('label' => 'admin.param.name', 'editable' => false))
            ->add('oldValue', 'string', array('label' => 'admin.paramlog.old_value', 'editable' => false))
            ->add('newValue', 'string', array('label' => 'admin.paramlog.new_value', 'editable' => false))
            ->add('event_date', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.paramlog.event_date'))
            ->add('user', 'string', array('label' => 'admin.paramlog.user', 'editable' => false))
        ;
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list'));
    }
}
