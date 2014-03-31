<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class DriverAdmin extends FoodAdmin
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
            ->add('name', 'text', array('label' => 'admin.driver.name', 'attr' => array('placeholder' => 'admin.driver.name_placeholder')))
            ->add('phone', 'text', array('label' => 'admin.driver.phone', 'attr' => array('placeholder' => 'admin.driver.phone_placeholder')))
            ->add('provider', null, array('label' => 'admin.driver.provider'))
            ->add('extId', 'text', array('label' => 'admin.driver.ext_id_long'))
            ->add('active', 'checkbox', array('label' => 'admin.driver.active', 'required' => false));
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
            ->add('name', null, array('label' => 'admin.driver.name'))
            ->add('provider', null, array('label' => 'admin.driver.provider'))
            ->add('phone', null, array('label' => 'admin.driver.phone'))
            ->add('extId', null, array('label' => 'admin.driver.ext_id'))
            ->add('active', null, array('label' => 'admin.driver.active'))
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
            ->addIdentifier('id', 'integer', array('label' => 'admin.driver.id'))
            ->addIdentifier('name', 'string', array('label' => 'admin.driver.name', 'editable' => true))
            ->add('provider', 'string', array('label' => 'admin.driver.provider'))
            ->add('phone', 'string', array('label' => 'admin.driver.phone', 'editable' => true))
            ->add('active', null, array('label' => 'admin.driver.active', 'editable' => true))
            ->add('_action', 'actions', array(
                'actions' => array(
//                    'show' => array(),
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
        $collection->clearExcept(array('list', 'edit', /*'show', */'create', 'delete'));
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     * TODO: implement me, please, su vairuotojo darbo ataskaita :)
     */
//    protected function configureShowFields(\Sonata\AdminBundle\Show\ShowMapper $showMapper)
//    {
//        $showMapper
//            ->add('title', null, array('label' => 'admin.static.title'))
//            ->add('content', null, array('label' => 'admin.static.content'))
//        ;
//    }
}
