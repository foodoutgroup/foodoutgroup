<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class StreetAdmin extends FoodAdmin
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
                'city',
                'text',
                array(
                    'label' => 'admin.street.city',
                    'attr' => array(
                        'placeholder' => $this->trans('admin.street.city_placeholder')
                    )
                )
            )
            ->add(
                'street',
                'text',
                array(
                    'label' => 'admin.street.street',
                    'attr' => array(
                        'placeholder' => $this->trans('admin.street.street_placeholder')
                    )
                )
            );
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
            ->add('city', null, array('label' => 'admin.street.city'))
            ->add('street', null, array('label' => 'admin.street.street'))
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
            ->addIdentifier('street', 'string', array('label' => 'admin.street.street', 'editable' => true))
            ->add('city', 'string', array('label' => 'admin.street.city', 'editable' => true))
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
