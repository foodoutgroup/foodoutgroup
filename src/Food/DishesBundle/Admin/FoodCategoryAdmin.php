<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class FoodCategoryAdmin extends FoodAdmin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\DishesBundle\Entity\FoodCategory',
                'fields' => array(
                    'name' => array('label' => 'label.name'),
                )
            ));
        if ($this->isAdmin()) {
            $formMapper
                ->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place', 'disabled' => true));
        }
        $formMapper
            ->add('active', 'checkbox', array('required' => false, 'label' => 'admin.active'))
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
            ->addIdentifier('name', 'string', array('admin.food_category.name'))
            ->add('place')
            ->add('active', null, array('label' => 'admin.places.list.active'))
            ->add('createdBy', 'entity', array('label' => 'admin.created_by'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.edited_at'))
        ;
    }

    /*
     * If user is a moderator - set place, as he can not choose it. Chuck Norris protection is active
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
    }

}
