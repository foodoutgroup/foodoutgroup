<?php
namespace Food\DishesBundle\Admin;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\AppBundle\Filter\PlaceFilter;
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
        /**
         * @var EntityManager $em
         */
        $em = $this->modelManager->getEntityManager('Food\DishesBundle\Entity\FoodCategory');
        /**
         * @var QueryBuilder
         */
        $categoryQuery = $em->createQueryBuilder('c')
            ->select('c')
            ->from('Food\DishesBundle\Entity\FoodCategory', 'c')
        ;

        /**
         * @var QueryBuilder
         */
        $optionsQuery = $em->createQueryBuilder('o')
            ->select('o')
            ->from('Food\DishesBundle\Entity\DishOption', 'o')
        ;

        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\DishesBundle\Entity\Dish',
                'fields' => array(
                    'name' => array(
                        'label' => 'label.name'
                    ),
                )
            ));

        // If user is admin - he can screw Your place. But if user is a moderator - we will set the place ir prePersist!
        if ($this->isAdmin()) {
            $formMapper->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place'));

            // Filter out inactive, hidden field options
            $categoryQuery->where('c.active = 1');
            $optionsQuery->where('o.hidden = 0');
        } else {
            // If user is a moderator - he is assigned to a place (unless he is Chuck or Cekuolis)
            $userPlaceId = $this->getUser()->getPlace()->getId();

            // Filter out inactive, hidden field options
            $categoryQuery->where('c.active = 1 AND c.place = :place')
                ->setParameter('place', $userPlaceId);

            $optionsQuery->where('o.hidden = 0 AND o.place = :place')
                ->setParameter('place', $userPlaceId);
        }

        $formMapper
            ->add('categories', null, array('query_builder' => $categoryQuery, 'required' => true, 'multiple' => true,))
            ->add('sizes', 'sonata_type_collection', array(
                    'required' => false,
                    'by_reference' => false,
                    'label' => 'admin.dishes.sizes'
                ), array(
                    'edit' => 'inline',
                    'inline' => 'table'
                )
            )
            ->add('options', null, array('query_builder' => $optionsQuery,'expanded' => true, 'multiple' => true, 'required' => false))
            ->add('recomended', 'checkbox', array('label' => 'admin.dish.recomended', 'required' => false,))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'admin.dish.name'));

        if ($this->isAdmin()) {
            $datagridMapper->add('place');
        }

        $datagridMapper
            ->add('categories')
            ->add('options')
            ->add('recomended', null, array('label' => 'admin.dish.recomended'))
            ->add('createdBy', null, array('label' => 'admin.created_by'))
            ->add(
                'createdAt',
                'doctrine_orm_datetime_range',
                array('label' => 'admin.created_at', 'format' => 'Y-m-d',),
                null,
                array(
                    'widget' => 'single_text',
                    'required' => false,
                    'format' => 'Y-m-d',
                    'attr' => array('class' => 'datepicker')
                )
            )
            ->add(
                'deletedAt',
                'doctrine_orm_datetime_range',
                array('label' => 'admin.deleted_at', 'format' => 'Y-m-d',),
                null,
                array(
                    'widget' => 'single_text',
                    'required' => false,
                    'format' => 'Y-m-d',
                    'attr' => array('class' => 'datepicker')
                )
            )
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', 'string', array('label' => 'admin.dish.name'))
            ->add('place')
            ->add('categories')
            ->add('options')
            ->add('sizes', 'string', array('template' => 'FoodDishesBundle:Default:list_admin_list_sizes.html.twig'))
            ->add('recomended', null, array('label' => 'admin.dish.recomended', 'editable' => true))
            ->add('createdBy', 'entity', array('label' => 'admin.created_by'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.edited_at'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;

        $this->setPlaceFilter(new PlaceFilter($this->getSecurityContext()))
            ->setPlaceFilterEnabled(true);
    }

    /**
     * If user is a moderator - set place, as he can not choose it. Chuck Norris protection is active
     *
     * @param \Food\DishesBundle\Entity\Dish $object
     * @return void
     */
    public function prePersist($object)
    {
        if ($this->isModerator()) {
            /**
             * @var Place $place
             */
            $place = $this->modelManager->find('Food\DishesBundle\Entity\Place', $this->getUser()->getPlace()->getId());

            $object->setPlace($place);
        }
        parent::prePersist($object);
        $this->fixRelations($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Dish $object
     * @return void
     */
    public function preUpdate($object)
    {
        $this->fixRelations($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Dish $object
     * @param null $setCreatedAt
     */
    private function fixRelations($object, $setCreatedAt = null)
    {
        $dishSizes = $object->getSizes();
        if (!empty($dishSizes)) {
            foreach ($dishSizes as $size) {
                $cAt = $size->getCreatedAt();
                if (!$cAt || empty($cAt)) {
                    $size->setCreatedAt(new \DateTime('now'));
                }
                $size->setDish($object);
            }
        }
    }

}