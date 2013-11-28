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
                ->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place', 'disabled' => false));
        }
        $formMapper
            ->add('active', 'checkbox', array('required' => false, 'label' => 'admin.active'))
        ;
    }

    /**
     *  Fields to be shown on filter forms
     * @param DatagridMapper $datagridMapper
     *
     * @todo - Mantai ar cia tikrai Active prie createdAt turi but?
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'admin.food_category.name'))
            ->add('createdAt', 'doctrine_orm_datetime_range', array('label' => 'admin.places.list.active'), null, array('widget' => 'single_text', 'required' => false, 'attr' => array('class' => 'datepicker')))
            ->add('editedAt', null, array('label' => 'admin.edited_at'))
            ->add('deletedAt', null, array('label' => 'admin.deleted_at'))
            ->add('place')
            ->add('active', null, array('label' => 'admin.places.list.active'))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', 'string', array('label' => 'admin.food_category.name'))
            ->add('place')
            ->add('active', null, array('label' => 'admin.places.list.active', 'editable' => true))
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



    /**
     * @param \Food\DishesBundle\Entity\FoodCategory $object
     */
    public function postPersist($object)
    {
        $this->fixSlugs($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\FoodCategory $object
     */
    public function postUpdate($object)
    {
        $this->fixSlugs($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\FoodCategory $object
     */
    private function fixSlugs($object)
    {
        $origName = $object->getOrigName($this->modelManager->getEntityManager('FoodDishesBundle:FoodCategory'));
        $locales = $this->getContainer()->getParameter('available_locales');
        $textsForSlugs = array();
        foreach($object->getTranslations()->getValues() as $row) {
            $textsForSlugs[$row->getLocale()] = $row->getContent();
        }
        foreach ($locales as $loc) {
            if (!isset($textsForSlugs[$loc])) {
                $textsForSlugs[$loc] = $origName;
            }
        }

        $languages = $this->getContainer()->get('food.app.utils.language')->getAll();
        $slugUtelyte = $this->getContainer()->get('food.dishes.utils.slug');
        foreach ($languages as $loc) {
            $slugUtelyte->generateForFoodCategory($loc, $object->getId(), $textsForSlugs[$loc]);
        }
    }

}
