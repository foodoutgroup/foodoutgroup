<?php
namespace Food\DishesBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class FoodCategoryAdmin extends Admin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text', array('label' => 'Dish name. Translation?'))
            ->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place'))
            ->add('active', 'checkbox', array('label' => 'Dish active. Where is translation?'))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add('createdAt')
            ->add('editedAt')
            ->add('deletedAt')
            ->add('place')
            ->add('active')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->add('place')
            ->add('date', 'datetime')
            ->add('active', 'checkbox')
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
            ->add('deletedAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
        ;
    }

    /**
     * Set create date before inserting to database
     *
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\FoodCategory $object
     * @return mixed|void
     */
    public function prePersist($object)
    {
        // The magic container is here
        $container = $this->getConfigurationPool()->getContainer();
        $securityContext = $container->get('security.context');
        $user = $securityContext->getToken()->getUser();

        $object->setCreatedAt(new \DateTime());
        $object->setCreatedBy($user->getId());
    }

    /**
     * Set editing time before inserting to database
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\FoodCategory $object
     * @return mixed|void
     */
    public function preUpdate($object)
    {
        $container = $this->getConfigurationPool()->getContainer();
        $securityContext = $container->get('security.context');
        $user = $securityContext->getToken()->getUser();

        // Log this troll, so we could burn him later
        $object->setEditedAt(new \DateTime());
        $object->setEditedBy($user->getId());
    }
}
