<?php
namespace Food\UserBundle\Admin;

use FOS\UserBundle\Model\UserManagerInterface;
use Sonata\UserBundle\Admin\Model\UserAdmin as SonataUserAdmin;


class UserDivisionCodeAdmin extends SonataUserAdmin {
    /**
     * {@inheritdoc}
     */
    protected function configureListFields(\Sonata\AdminBundle\Datagrid\ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id', null, array('label' => 'admin.users_dision_code.id'))
            ->add('code', 'text', array('label' => 'admin.users_dision_code.code'))
            ->add('division', 'text', array('label' => 'admin.users_dision_code.division'))
            ->add('_action', 'actions', array(
                'actions' => array(
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
            ->add('code', null, array('label' => 'admin.users_dision_code.code'))
            ->add('division', null, array('label' => 'admin.users_dision_code.division'))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(\Sonata\AdminBundle\Form\FormMapper $formMapper)
    {
        $formMapper
            ->add('code', 'text', array('label' => 'admin.users_dision_code.code', 'required' => true))
            ->add('division', 'text', array('label' => 'admin.users_dision_code.division', 'required' => true))
        ;
    }
}
