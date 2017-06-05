<?php

namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\AppBundle\Entity\Driver;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PhoneCodesAdmin extends FoodAdmin
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
            ->add(
                'country', 'text', array(
                    'label' => 'admin.phone.country',

                )
            )
            ->add(
                'code', 'text', array(
                    'label' => 'admin.phone.code',
                )
            )->add(
                'active', 'checkbox', array(
                    'label' => 'admin.phone.active')
            );

    }

    protected function configureShowFields(\Sonata\AdminBundle\Show\ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', null, array('label' => 'admin.order.id'))
            ->add('country', 'string', array('label' => 'admin.phone.country'))
            ->add('code', 'string', array('label' => 'admin.phone.code'))
            ->add('active', 'string', array('label' => 'admin.phone.active'))
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
            ->add('country', null, array('label' => 'admin.phone.country'))
            ->add('code', null, array('label' => 'admin.phone.code'));
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
            ->addIdentifier('id', 'integer', array('label' => 'admin.driver.id'))
            ->add('country', 'string', array('label' => 'admin.phone.country'))
            ->add('code', 'string', array('label' => 'admin.phone.code'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' =>array()
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'edit', 'show','create','delete'));
    }


}
