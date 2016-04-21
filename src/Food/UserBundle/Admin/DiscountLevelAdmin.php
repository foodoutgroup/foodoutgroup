<?php
namespace Food\UserBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;

class DiscountLevelAdmin extends FoodAdmin {
//    protected $baseRouteName = 'food_slug_generator';
//    protected $baseRoutePattern = 'slug/generator';

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(\Sonata\AdminBundle\Datagrid\ListMapper $listMapper)
    {
        $listMapper
            ->add('rangeStart', 'text', array('label' => 'admin.discount_level.range_start'))
            ->add('rangeEnd', 'text', array('label' => 'admin.discount_level.range_end'))
            ->add('discount', 'text', array('label' => 'admin.discount_level.discount'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(\Sonata\AdminBundle\Datagrid\DatagridMapper $filterMapper)
    {
        $filterMapper
            ->add('id')
            ->add('rangeStart', null, array('label' => 'admin.discount_level.range_start'))
            ->add('rangeEnd', null, array('label' => 'admin.discount_level.range_end'))
            ->add('discount', null, array('label' => 'admin.discount_level.discount'))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(\Sonata\AdminBundle\Form\FormMapper $formMapper)
    {
        $formMapper
            ->add('rangeStart', 'text', array('label' => 'admin.discount_level.range_start', 'required' => false))
            ->add('rangeEnd', 'text', array('label' => 'admin.discount_level.range_end', 'required' => false))
            ->add('discount', 'text', array('label' => 'admin.discount_level.discount'))
        ;
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'edit', 'create', 'delete'));
    }
}
