<?php
namespace Food\DishesBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PlacePointAdmin extends Admin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('address', 'text', array('label' => 'Adresas'))
            ->add('city', 'text', array('label' => 'Miestas'))
            ->add('coords', 'text', array('label' => 'Koordinates'))

        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
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
        $object->setCreatedAt(new \DateTime("now"));
        $container = $this->getConfigurationPool()->getContainer();
        $securityContext = $container->get('security.context');
        $user = $securityContext->getToken()->getUser();
        $object->setCreatedBy($user);
    }

    /**
     * Set editing time before inserting to database
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\Place $object
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