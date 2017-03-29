<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\AppBundle\Filter\PlaceFilter;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Food\AppBundle\Validator\Constraints\Slug;
use \Food\AppBundle\Entity\Slug as SlugEntity;

class FoodCategoryAdmin extends FoodAdmin
{

    /**
     * Fields to be shown on create/edit forms
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\DishesBundle\Entity\FoodCategory',
                'fields' => array(
                    'name' => array('label' => 'label.name'),
                    'slug' => [
                        'constraints' => new Slug(SlugEntity::TYPE_FOOD_CATEGORY, $formMapper),
                        'attr'=>['data-slugify'=>'name']
                    ]
                )


            ));
        if ($this->isAdmin()) {
            $formMapper
                ->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place',));
        }
        $formMapper
            ->add('lineup', null, array('required' => false, 'label' => 'admin.food_category.lineup'))
            ->add('textsOnly', null, array('required' => false, 'label' => 'admin.food_category.texts_only'))
            ->add('drinks', 'checkbox', array('required' => false, 'label' => 'admin.food_category.drinks'))
            ->add('alcohol', 'checkbox', array('required' => false, 'label' => 'admin.food_category.alcohol'))
            ->add('active', 'checkbox', array('required' => false, 'label' => 'admin.active'));
    }

    /**
     * Fields to be shown on filter forms
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'admin.food_category.name'))
            ->add('drinks', null, array('label' => 'admin.food_category.drinks'))
            ->add('alcohol', null, array('label' => 'admin.food_category.alcohol'))
            ->add('createdAt', null, array('label' => 'admin.created_at'))
            ->add('editedAt', null, array('label' => 'admin.edited_at'))
            ->add('deletedAt', null, array('label' => 'admin.deleted_at'));

        if ($this->isAdmin()) {
            $datagridMapper->add('place');
        }

        $datagridMapper->add('active', null, array('label' => 'admin.places.list.active'));
    }

    /**
     * Fields to be shown on lists
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', 'string', array('label' => 'admin.food_category.name'))
            ->add('place')
            ->add('textsOnly', null, array('label' => 'admin.food_category.texts_only'))
            ->add('drinks', null, array('label' => 'admin.food_category.drinks'))
            ->add('alcohol', null, array('label' => 'admin.food_category.alcohol'))
            ->add('active', null, array('label' => 'admin.places.list.active', 'editable' => true))
            ->add('lineup', '', array('required' => false, 'label' => 'admin.food_category.lineup', 'editable' => true))
//            ->add('createdBy', 'entity', array('label' => 'admin.created_by'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.edited_at'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ));

        $this->setPlaceFilter(new PlaceFilter($this->getSecurityContext()))
            ->setPlaceFilterEnabled(true);
    }

    /**
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
        $this->slug($object);
        parent::prePersist($object);
    }

    public function preUpdate($object)
    {
        $this->slug($object);
        parent::preUpdate($object);

    }

    private function slug($object)
    {
        $slugService = $this->getContainer()->get('slug');
        $slugService->generate($object, 'slug', SlugEntity::TYPE_FOOD_CATEGORY);
    }
}
