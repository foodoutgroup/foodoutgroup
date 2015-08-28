<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class MarketingUserAdmin extends FoodAdmin
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
            ->add('firstname', 'text', array('label' => 'First name'))
            ->add('lastname', 'text', array('label' => 'Last name'))
            ->add('city', 'text', array('label' => 'City'))
            ->add('phone', 'text', array('label' => 'Phone'))
            ->add('email', 'text', array('label' => 'Email'))
            ->add('birthDate', null, array('label' => 'B-day'))
            ->add('entryKey', 'text', array('label' => 'Entry Key', 'required' => false))
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
            ->add('city', null, array('label' => 'City'))
            ->add('entryKey', null, array('label' => 'Entry Key'));
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
            ->addIdentifier('id', 'integer', array('label' => 'ID'))
            ->add('firstname', 'text', array('label' => 'First name'))
            ->add('lastname', 'text', array('label' => 'Last name'))
            ->add('city', 'text', array('label' => 'City'))
            ->add('phone', 'text', array('label' => 'Phone'))
            ->add('email', 'text', array('label' => 'Email'))
            ->add('birthDate', 'datetime', array('format' => 'Y-m-d', 'label' => 'B-day'))
            ->add('entryKey', 'string', array('label' => 'Entry Key'))
        ;
    }
}
