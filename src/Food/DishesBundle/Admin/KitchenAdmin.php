<?php

namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Food\AppBundle\Validator\Constraints\Slug;
use \Food\AppBundle\Entity\Slug as SlugEntity;

class KitchenAdmin extends FoodAdmin
{
    /**
     * Default Datagrid values
     *
     * @var array
     */
    protected $datagridValues = array(
        '_page' => 1, // Display the first page (default = 1)
        '_sort_order' => 'ASC', // Descendant ordering (default = 'ASC')
        '_sort_by' => 'id' // name of the ordered field (default = the model id field, if any)
    );

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
//        $options = array('required' => false, 'label' => 'admin.kitchen.logo');
//
//        if (($pl = $this->getSubject()) && $pl->getLogo()) {
//            $this->getUploadService()->setObject($pl);
//            $options['help'] = '<img src="/' . $pl->getWebPath() . '" />';
//        }

        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\DishesBundle\Entity\Kitchen',
                'fields' => array(
                    'name' => array('label' => 'label.name'),
                    'alias' => array('label' => 'label.alias', 'required' => false),
                    'metaDescription' => ['label' => 'admin.meta_description', 'required' => false],
                    'metaTitle' => ['label' => 'admin.meta_title', 'required' => false],
                    'slug' => [
                        'constraints' => new Slug(SlugEntity::TYPE_KITCHEN, $formMapper),
                        'attr' => ['data-slugify' => 'name']
                    ]
                )
            ))
            //->add('file', 'file', $options)
            ->add('visible', 'checkbox', array(
                'required' => false,
                'label' => 'admin.visible'
            ));
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'admin.kitchen.name'))
            ->add('alias', null, array('label' => 'admin.kitchen.alias'))
            ->add('visible', null, array('label' => 'admin.visible'))
//            ->add('createdBy', null, array('label' => 'admin.created_by'))
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
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', 'string', array('label' => 'admin.kitchen.name'))
            //->add('logo', 'string', array(
            //    'template' => 'FoodDishesBundle:Default:list_image.html.twig',
            //    'label' => 'admin.kitchen.logo')
            //)
            ->add('visible', null, array('label' => 'admin.visible', 'editable' => true))
            ->add('createdByUsername', null, array('label' => 'admin.created_by'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.edited_at'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ));
    }

    /**
     * Save file before saving to db
     *
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\Kitchen
     * @return mixed|void
     */
    public function prePersist($object)
    {
        //$this->saveFile($object);
        $this->slug($object);

        parent::prePersist($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Kitchen $object
     * @return mixed|void
     */
    public function preUpdate($object)
    {
        //$this->saveFile($object);
        $this->slug($object);

        parent::preUpdate($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Kitchen $object
     */
    public function postPersist($object)
    {
        $this->slug($object);

        parent::postPersist($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Kitchen $object
     */
    public function postUpdate($object)
    {
        $this->slug($object);

        parent::postUpdate($object);
    }

    /**
     * Lets fix da stufffff.... Slugs for Kichen :)
     *
     * @param \Food\DishesBundle\Entity\Kitchen $object
     */


    //    private function fixSlugs($object)
//    {
//        $origName = $object->getOrigName($this->modelManager->getEntityManager('FoodDishesBundle:Kitchen'));
//        $origAlias = $object->getOrigAlias($this->modelManager->getEntityManager('FoodDishesBundle:Kitchen'));
//        $locales = $this->getContainer()->getParameter('available_locales');
//        $textsForSlugs = array();
//        $alias_textsForSlugs = array();
//        $all_alias_textsForSlugs = array();
//        foreach($object->getTranslations()->getValues() as $row) {
//            if ($row->getField() == "name") {
//                $textsForSlugs[$row->getLocale()] = $row->getContent();
//            }
//            if ($row->getField() == "alias") {
//                $alias_textsForSlugs[$row->getLocale()] = $row->getContent();
//            }
//        }
//        foreach ($locales as $loc) {
//            if (!isset($textsForSlugs[$loc])) {
//                $textsForSlugs[$loc] = $origName;
//            }
//            if (!isset($alias_textsForSlugs[$loc])) {
//                $alias_textsForSlugs[$loc] = $origAlias;
//            }
//        }
//
//        if (count($alias_textsForSlugs) > 0) {
//            foreach($alias_textsForSlugs as $key => $value) {
//                if (!empty($value)) {
//                    $values = explode(',', $value);
//                    foreach ($values as $val) {
//                        $all_alias_textsForSlugs[$key][] = trim($val);
//                    }
//                }
//            }
//        }
//
//        $languages = $this->getContainer()->get('food.app.utils.language')->getAll();
//        $slugUtelyte = $this->getContainer()->get('food.dishes.utils.slug');
//        foreach ($languages as $loc) {
//            if (count($all_alias_textsForSlugs) > 0) {
//                foreach($all_alias_textsForSlugs[$loc] as $aliasText) {
//                    $slugUtelyte->generateForKitchens($loc, $object->getId(), $aliasText);
//                }
//            }
//            $slugUtelyte->generateForKitchens($loc, $object->getId(), $textsForSlugs[$loc]);
//        }
//    }

    private function slug($object)
    {
        $slugService = $this->getContainer()->get('slug');
        $slugService->generate($object, 'slug', SlugEntity::TYPE_KITCHEN);
    }

}