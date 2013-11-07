<?php
namespace Food\DishesBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class DishAdmin extends Admin
{
    /**
     * Default Datagrid values
     *
     * @var array
     */
    protected $datagridValues = array (
//        'deleted' => array ('value' => false), // type 2 : > TODO neveikia solutionas, kad nerodytu istrintu
        '_page' => 1, // Display the first page (default = 1)
        '_sort_order' => 'ASC', // Descendant ordering (default = 'ASC')
        '_sort_by' => 'id' // name of the ordered field (default = the model id field, if any)
    );

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text', array('label' => 'Dish name'))
            ->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place'))
            ->add('categories', 'entity', array('class' => 'Food\DishesBundle\Entity\FoodCategory', 'multiple' => true))
            ->add('units', 'entity', array('class' => 'Food\DishesBundle\Entity\DishUnit', 'multiple' => true))
            ->add('options', 'entity', array('class' => 'Food\DishesBundle\Entity\DishOption', 'multiple' => true))
            ->add('price') // TODO type to be decimal
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add('place')
            ->add('categories')
            ->add('units')
            ->add('options')
            ->add('createdBy')
            ->add('createdAt')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->add('place')
            ->add('categories')
            ->add('units')
            ->add('options')
            ->add('price')
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
     * @param \Food\DishesBundle\Entity\Dish
     * @return mixed|void
     */
    public function prePersist($object)
    {
        // The magic container is here
        $container = $this->getConfigurationPool()->getContainer();
        $securityContext = $container->get('security.context');
        $user = $securityContext->getToken()->getUser();

        $object->setCreatedAt(new \DateTime());
        $object->setDeleted(0);
        $object->setCreatedBy($user->getId());
    }

    /**
     * Set editing time before inserting to database
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\Dish $object
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


    /**
     * @inheritdoc
     * @param string $context
     * @return \Sonata\AdminBundle\Datagrid\ProxyQueryInterface
     */
    public function createQuery($context = 'list')
    {
        // Filter out deleted entries
        // TODO OMG HACK - this is not the proper way. Need a good working solution, but for now..
        $query = parent::createQuery($context);
        if ($context == 'list') {
            $query->where('o.deleted != 1');
        }

        return $query;
    }
}