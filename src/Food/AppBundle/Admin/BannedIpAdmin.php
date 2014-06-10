<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class BannedIpAdmin extends FoodAdmin
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
                'ip',
                'text',
                array(
                    'label' => 'admin.banned_ip.ip',
                    'attr' => array(
                        'placeholder' => $this->trans('admin.banned_ip.ip_placeholder')
                    )
                )
            )
            ->add(
                'reason',
                'text',
                array(
                    'label' => 'admin.banned_ip.reason',
                )
            )
            ->add('active', 'checkbox', array('label' => 'admin.banned_ip.active', 'required' => false));
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
            ->add('ip', null, array('label' => 'admin.banned_ip.ip'))
            ->add('active', null, array('label' => 'admin.banned_ip.active'))
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
            ->addIdentifier('id', 'integer', array('label' => 'admin.banned_ip.id'))
            ->addIdentifier('ip', 'string', array('label' => 'admin.banned_ip.ip', 'editable' => true))
            ->add('reason', 'string', array('label' => 'admin.banned_ip.reason'))
            ->add('active', null, array('label' => 'admin.banned_ip.active', 'editable' => true))
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
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'edit', 'create', 'delete'));
    }
}
