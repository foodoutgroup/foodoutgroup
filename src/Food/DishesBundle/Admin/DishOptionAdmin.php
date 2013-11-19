<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
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
                    'name' => array(),
                    'description' => array(),
                ),
                'label' => 'Unit name (transl)'
            )
        );

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
            ->add('name')
            ->add('price')
            ->add('createdBy')
            ->add('createdAt')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->add('createdBy')
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
        ;
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