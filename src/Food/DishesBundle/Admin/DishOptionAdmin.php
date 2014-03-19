<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\AppBundle\Filter\PlaceFilter;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class DishOptionAdmin extends FoodAdmin
{

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\DishesBundle\Entity\DishOption',
                'fields' => array(
                    'name' => array('label' => 'label.name'),
                    'description' => array('label' => 'label.description', 'required' => false),
                ),
//                'label' => 'Unit name (transl)'
            )
        )->add('code', null, array('label' => 'admin.dish_option.code', 'required' => false))
         ->add('singleSelect', 'checkbox', array('label' => 'admin.dish_option.single_select', 'required' => false));

        if ($this->isAdmin()) {
            $formMapper->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place'));
        }

        $formMapper->add('price')
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'admin.dish_option.name'))
            ->add('code', null, array('label' => 'admin.dish_option.code'))
            ->add('price', null, array('label' => 'admin.dish_option.price'))
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
            ->addIdentifier('name', null, array('label' => 'admin.dish_option.name'))
            ->add('price', null, array('label' => 'admin.dish_option.price'))
            ->add('createdBy', null, array('label' => 'admin.created_by'))
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

    /*
     * If user is a moderator - set place, as he can not choose it. Chuck Norris protection is active
     */
    public function prePersist($object)
    {
        if ($this->isModerator()) {
            $place = $this->modelManager->find('Food\DishesBundle\Entity\Place', $this->getUser()->getPlace()->getId());

            $object->setPlace($place);
        }
        parent::prePersist($object);
    }
}