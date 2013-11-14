<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Food\DishesBundle\Entity\Place;

class DishAdmin extends FoodAdmin
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
        $em = $this->modelManager->getEntityManager('Food\DishesBundle\Entity\FoodCategory');

        /**
         * @var QueryBuilder
         */
        $categoryQuery = $em->createQueryBuilder('c')
            ->select('c')
            ->from('Food\DishesBundle\Entity\FoodCategory', 'c')
            ->where('c.active = 1')
        ;

        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\DishesBundle\Entity\Dish',
                'fields' => array(
                    'name' => array(
                    ),
                )
            ));

        // If user is admin - he can screw Your place. But if user is a moderator - we will set the place ir prePersist!
        if ($this->getContainer()->get('security.context')->isGranted('ROLE_ADMIN')) {
            $formMapper->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place'));
        }

        $formMapper
            ->add('categories', null, array('query_builder' => $categoryQuery, 'required' => true, 'multiple' => true,))
            ->add('unit', 'entity', array('class' => 'Food\DishesBundle\Entity\DishUnit', 'multiple' => false))
            ->add('options', 'entity', array('class' => 'Food\DishesBundle\Entity\DishOption','expanded' => true, 'multiple' => true, 'required' => false))
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

    /*
     * TODO sarasui filtruoti place
     */
    public function prePersist($object)
    {
        $securityContext = $this->getContainer()->get('security.context');
        if (!$securityContext->isGranted('ROLE_ADMIN') && $securityContext->isGranted('ROLE_MODERATOR')) {
//            $place = new Place();
            $place = $this->modelManager->find('Place', $this->getUser()->getPlaces()->getId());

            $object->setPlace($place);
        }
        parent::prePersist($object);
    }

}