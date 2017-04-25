<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;


class ErrorLogAdmin extends FoodAdmin
{

    protected $datagridValues = array(
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'id',
    );

    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter
            ->add('source', null, array('label' => 'admin.error.source'))
            ->add('description', null, array('label' => 'admin.error.source'))
            ->add('createdAt', 'doctrine_orm_date_range', array(), null, array('widget' => 'single_text', 'required' => false,  'attr' => array('class' => 'datepicker2')));
    }

    protected function configureListFields(ListMapper $list)
    {
        $list
            ->addIdentifier('id', 'integer', array('label' => 'admin.order.id'))
            ->add('source', 'string', array('label' => 'admin.error.source'))
            ->add('description', 'string', array('label' => 'admin.error.description'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('created_by', 'int', array('label' => 'admin.error.user'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    protected function configureShowFields(\Sonata\AdminBundle\Show\ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', null, array('label' => 'admin.order.id'))
            ->add('source', 'string', array('label' => 'admin.error.source'))
            ->add('description', 'string', array('label' => 'admin.error.description'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('created_by', 'string', array('label' => 'admin.error.user'))
            ->add('cart', 'string', array('label' => 'admin.error.cart_session'))
            ->add('place.name', 'string', array('label' => 'admin.error.place'))
            ->add('ip', 'string', array('label' => 'admin.error.ip'))
            ->add('url', 'string', array('label' => 'admin.error.url'))
            ->add('debug', 'string', array('label' => 'admin.error.debug'));


    }

    public function getExportFields()
    {
        $exportFields =[];
        $exportFields[] = 'id';
        $exportFields[] = 'created_by';
        $exportFields[] = 'ip';
        $exportFields[] = 'url';
        $exportFields[] = 'source';
        $exportFields[] = 'description';
        $exportFields[] = 'cart';
        $exportFields[] = 'place';

        return $exportFields;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'show', 'export'));
    }
}