<?php
namespace Food\DishesBundle\Admin;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class DishSizeAdmin extends FoodAdmin
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
        if ($this->isAdmin()) {
            $formMapper->add('unit', 'entity', array('group_by' => 'group', 'class' => 'Food\DishesBundle\Entity\DishUnit', 'multiple' => false));
        } else {
            $formMapper->add(
                'unit',
                'entity',
                array(
                    'group_by' => 'group',
                    'class' => 'Food\DishesBundle\Entity\DishUnit',
                    'multiple' => false,
                    'query_builder' => function ($repository)
                        {
                            return $repository->createQueryBuilder('s')
                                ->where('s.place = ?1')
                                ->setParameter(1, $this->getUser()->getPlace()->getId());
                        }
                )
            );
        }

        $formMapper->add('code')
            ->add('price')
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'admin.dish.name'))
            ->add('place')
            ->add('categories')
            ->add('unit')
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
            ->add('unit')
            ->add('options')
            ->add('price')
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
    }
}