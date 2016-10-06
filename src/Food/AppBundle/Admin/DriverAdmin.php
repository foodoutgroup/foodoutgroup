<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\AppBundle\Entity\Driver;
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
            ->add(
                'name',
                'text',
                array(
                    'label' => 'admin.driver.name',
                    'attr' => array(
                        'placeholder' => $this->trans('admin.driver.name_placeholder')
                    )
                )
            )
            ->add(
                'phone',
                'text',
                array(
                    'label' => 'admin.driver.phone',
                    'attr' => array(
                        'placeholder' => $this->trans('admin.driver.phone_placeholder')
                    )
                )
            )
            ->add('city', null, array('label' => 'admin.driver.city'))
            ->add(
                'type',
                'choice',
                array(
                    'multiple' => false,
                    'required' => true,
                    'label' => 'admin.driver.type',
                    'choices' => array(
                        'local' => $this->trans('admin.driver.type.local'),
                        'outsource' => $this->trans('admin.driver.type.outsource'),
                        'individual' => $this->trans('admin.driver.type.individual'),
                    )
                )
            )
            ->add('provider', null, array('label' => 'admin.driver.provider'))
            ->add('extId', 'text', array('label' => 'admin.driver.ext_id_long', 'required' => false))
            ->add('token', 'text', array('label' => 'admin.driver.token', 'required' => false))
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
            ->add('city', null, array('label' => 'admin.driver.city'))
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
            ->add('city', 'string', array('label' => 'admin.driver.city'))
            ->add(
                'type',
                'choice',
                array(
                    'label' => 'admin.driver.type',
                    'choices' => array(
                        'local' => $this->trans('admin.driver.type.local'),
                        'outsource' => $this->trans('admin.driver.type.outsource'),
                        'individual' => $this->trans('admin.driver.type.individual'),
                    ),
                )
            )
            ->add('provider', 'string', array('label' => 'admin.driver.provider'))
            ->add('extId', 'string', array('label' => 'admin.driver.ext_id'))
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
        $collection->clearExcept(array('list', 'edit', /*'show', */'create', 'delete', 'export'));
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

    public function prePersist($object)
    {
        $phone = $this->cleanDaPhone($object->getPhone());
        $object->setPhone($phone);

        if (!$object->getToken()) {
            $object->setToken($this->generateToken());
        }

        parent::prePersist($object);
    }

    public function preUpdate($object)
    {
        $phone = $this->cleanDaPhone($object->getPhone());
        $object->setPhone($phone);

        if (!$object->getToken()) {
            $object->setToken($this->generateToken());
        }

        parent::preUpdate($object);
    }

    /**
     * @param $phone
     * @return mixed|string
     */
    private function cleanDaPhone($phone)
    {
        $phone = trim($phone);
        $phone = str_replace('+', '', $phone);
        $phone = str_replace(' ', '', $phone);

        return $phone;
    }

    private function generateToken()
    {
        $chars = 'ABCDEFGHYJKLMNPRSTUVZ';
        return $chars[rand(0, strlen($chars) - 1)].rand(10000, 99999).$chars[rand(0, strlen($chars) - 1)];
    }

}
