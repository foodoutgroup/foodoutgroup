<?php
namespace Food\DishesBundle\Admin;

use Food\UserBundle\Entity\User;
use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PlaceAdmin extends FoodAdmin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text', array('label' => 'Place name'))
            ->add('kitchens', 'entity', array('multiple'=>true, 'class' => 'Food\DishesBundle\Entity\Kitchen'))
            ->add('active', 'checkbox', array('label' => 'I are active?'))
            //->add('logo', 'file', array('required' => false))
            ->add('points', 'sonata_type_collection',
                array(
                    //'by_reference' => false,
                ),
                array(
                    'edit' => 'inline',
                    'inline' => 'table'
                )
            );
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')

//            ->add('place')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
//            ->add('place')
            ->add('logo');
        ;
    }


    /**
     * Set create date before inserting to database
     *
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\Place
     * @return mixed|void
     */
    public function prePersist($object)
    {
        // The magic container is here
        $securityContext = $this->getContainer()->get('security.context');
        $user = $securityContext->getToken()->getUser();
        $this->_fixPoints($object, $user);

        parent::prePersist($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     */
    public function preUpdate($object)
    {
        $container = $this->getConfigurationPool()->getContainer();
        $securityContext = $container->get('security.context');
        $this->_fixPoints($object, $securityContext->getToken()->getUser());

        parent::preUpdate($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     * @param \Food\UserBundle\Entity\User $user
     */
    private function _fixPoints($object, $user)
    {
        foreach ($object->getPoints() as $point) {
            $point->setPlace($object);
            if (empty($point->getCreatedBy())) {
                $point->setCreatedBy($user);
            }
        }
    }
}