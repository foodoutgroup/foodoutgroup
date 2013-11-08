<?php
namespace Food\DishesBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class KitchenAdmin extends Admin
{
    /**
     * Default Datagrid values
     *
     * @var array
     */
    protected $datagridValues = array (
        '_page' => 1, // Display the first page (default = 1)
        '_sort_order' => 'ASC', // Descendant ordering (default = 'ASC')
        '_sort_by' => 'id' // name of the ordered field (default = the model id field, if any)
    );

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text', array('label' => 'Kitchen name. Translate?'))
            ->add('logo', 'text')
            ->add('visible', 'checkbox', array('label' => 'Kitchen visible. Where is translation?'))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add('visible')
            ->add('createdBy')
            ->add('createdAt')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->add('logo')
            ->add('visible')
            ->add('createdBy')
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
        ;
    }

    /**
     * Set create date before inserting to database
     *
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\Kitchen $object
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
     * @param \Food\DishesBundle\Entity\Kitchen $object
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