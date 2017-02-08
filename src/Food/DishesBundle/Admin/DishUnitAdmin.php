<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class DishUnitAdmin extends FoodAdmin
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
        // Override edit template by our magic one with ajax
        $this->setTemplate('edit', 'FoodDishesBundle:Dish:admin_dish_unit_edit.html.twig');

        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\DishesBundle\Entity\DishUnit',
                'fields' => array(
                    'name' => array('label' => 'label.name'),
                    'shortName' => array('label' => 'label.short_name'),
                    'nameToNav' => array(
                        'label' => 'Navision name'
                    ),
                ),
            ));

        // If user is admin - he can screw Your place. But if user is a moderator - we will set the place ir prePersist!
        if ($this->isAdmin()) {
            $formMapper->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place'))
                ->add('unitCategory', null, array('label' => 'admin.unit.unit_category'));
        } else {
            /**
             * @var EntityManager $em
             */
            $em = $this->modelManager->getEntityManager('Food\DishesBundle\Entity\FoodCategory');

            /**
             * @var QueryBuilder
             */
            $categoryQuery = $em->createQueryBuilder('c')
                ->select('c')
                ->from('Food\DishesBundle\Entity\DishUnitCategory', 'c')
                ->where('c.place = :place')
                ->setParameter('place', $this->getUser()->getPlace()->getId());
            ;

            $formMapper->add('unitCategory', null, array(
                'query_builder' => $categoryQuery,
                'label' => 'admin.unit.unit_category'
            ));
        }
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'admin.unit.name'));

        if ($this->isAdmin()) {
            $datagridMapper->add('place');
        }

//        $datagridMapper->add('createdBy', null, array('label' => 'admin.created_by'))
//            ->add(
//                'createdAt',
//                'doctrine_orm_datetime_range',
//                array('label' => 'admin.created_at', 'format' => 'Y-m-d',),
//                null,
//                array(
//                    'widget' => 'single_text',
//                    'required' => false,
//                    'format' => 'Y-m-d',
//                    'attr' => array('class' => 'datepicker')
//                )
//            )
//            ->add(
//                'deletedAt',
//                'doctrine_orm_datetime_range',
//                array('label' => 'admin.deleted_at', 'format' => 'Y-m-d',),
//                null,
//                array(
//                    'widget' => 'single_text',
//                    'required' => false,
//                    'format' => 'Y-m-d',
//                    'attr' => array('class' => 'datepicker')
//                )
//            )
//        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', 'string', array('label' => 'admin.unit.name'))
            ->add('place')
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
    }
}
