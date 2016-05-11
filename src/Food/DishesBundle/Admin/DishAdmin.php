<?php
namespace Food\DishesBundle\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    protected $formOptions = array(
        'cascade_validation' => true
     );

    /**
     * @return string
     */
    public function getEditTemplate()
    {
        return 'FoodDishesBundle:Dish:base_edit.html.twig';
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        // Override edit template by our magic one with ajax
        $this->setTemplate('edit', 'FoodDishesBundle:Dish:admin_dish_edit.html.twig');

        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\DishesBundle\Entity\Dish',
                'fields' => array(
                    'name' => array(
                        'label' => 'label.name'
                    ),
                    'description' => array(
                        'label' => 'label.description'
                    ),
                )
            ));

        // If user is admin - he can screw Your place. But if user is a moderator - we will set the place ir prePersist!
        if ($this->isAdmin()) {
            $formMapper->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place'));
        }

        $options = array('required' => false, 'label' => 'admin.dish.photo');
        if (($pl = $this->getSubject()) && $pl->getPhoto()) {
            $options['help'] = '<img src="/' . $pl->getWebPathThumb('type1') . '" />';
        }

        $formMapper
            ->add(
                'categories',
                'sonata_type_model',
                array(
                    //'query_builder' => $optionsQuery,
                    'btn_add' => false,
                    'multiple' => true,
                    'required' => false
                )
            )
            ->add('timeFrom', null, array('label' => 'admin.dish.time_from', 'required' => false,))
            ->add('timeTo', null, array('label' => 'admin.dish.time_to', 'required' => false,))
            ->add('file', 'file', $options)
            ->add('sizes', 'sonata_type_collection', array(
                    'required' => false,
                    'by_reference' => false,
                    'label' => 'admin.dishes.sizes'
                    'btn_add' => $this->getContainer()->get('translator')->trans('link_action_create_override', array(), 'SonataAdminBundle')
                ), array(
                    'edit' => 'inline',
                    'inline' => 'table'
                )
            )
            ->add('discountPricesEnabled', 'checkbox', array('label' => 'admin.dish.discount_prices_enabled', 'required' => false,))
            ->add('noDiscounts', 'checkbox', array('label' => 'No discounts', 'required' => false,))
            ->add('showPublicPrice', 'checkbox', array('label' => 'Public price', 'required' => false,))
            ->add(
                'options',
                'sonata_type_model',
                array(
                    //'query_builder' => $optionsQuery,
                    'btn_add' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false
                )
            )
            ->add('recomended', 'checkbox', array('label' => 'admin.dish.recomended', 'required' => false,))
            ->add('active', 'checkbox', array('label' => 'admin.dish.active', 'required' => false,))
            ->add('group', null, array('label' => 'admin.dish.group'))
            ->add('checkEvenOddWeek', 'checkbox', array('label' => 'admin.dish.check_even_odd_week', 'required' => false,))
            ->add('evenWeek', 'checkbox', array('label' => 'admin.dish.even_week', 'required' => false,))
            ->add('useDateInterval', 'checkbox', array('label' => 'admin.dish.use_date_interval', 'required' => false,))
            ->add('dates', 'sonata_type_collection',
                array(
                    //'by_reference' => false,
                    'max_length' => 2,
                    'label' => 'admin.dish_date',
                    'required' => false,
                ),
                array(
                    'edit' => 'inline',
                    'inline' => 'table',
                )
            )
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'admin.dish.name'));

        //if ($this->isAdmin()) {
            $datagridMapper->add('place');
        //}

        $datagridMapper
            ->add('categories')
            //->add('options')
            //->add('recomended', 'checkbox', array('label' => 'admin.dish.recomended'))
            //->add('discountPricesEnabled', 'checkbox', array('label' => 'admin.dish.discount_prices_enabled'))
            //->add('createdBy', null, array('label' => 'admin.created_by'))
        // TODO isjungiau, nes nenaudojam, o po sonatos update - griuna widget optionsas
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
            ->addIdentifier('name', 'string', array('label' => 'admin.dish.name'))
            ->add('placeName')
            ->add('categories')
            ->add('image', 'string', array(
                'template' => 'FoodDishesBundle:Default:list_image.html.twig',
                'label' => 'admin.dish.photo'
            ))
            ->add('options')
            ->add('sizes', 'string', array('template' => 'FoodDishesBundle:Default:list_admin_list_sizes.html.twig'))
            ->add('discountPricesEnabled', null, array('label' => 'admin.dish.discount_prices_enabled', 'editable' => true))
            ->add('recomended', null, array('label' => 'admin.dish.recomended_list', 'editable' => true))
            ->add('active', null, array('label' => 'admin.dish.active_list', 'editable' => true))
            //->add('createdBy', 'entity', array('label' => 'admin.created_by'))
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
        $this->saveFile($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Dish $object
     * @return void
     */
    public function preUpdate($object)
    {
        $object->setEditedAt(new \DateTime());
        $this->fixRelations($object);
        $this->saveFile($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Dish $object
     * @return void
     */
    public function postPersist($object)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        if ($object->getDates()) {
            foreach ($object->getDates() as $date) {
                $date->setDish($object);
                $em->persist($date);
            }
            $em->flush();
        }

        parent::postPersist($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Dish $object
     * @return void
     */
    public function postUpdate($object)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        foreach ($object->getDates() as $date) {
            $date->setDish($object);
            $em->persist($date);
        }

        if ($object->getDeletedAt() != null) {
            // find and soft-delete other stuff
            $sizes = $object->getSizes();
            if (count($sizes) > 0) {
                foreach ($sizes as $size) {
                    $size->setDeletedAt(new \DateTime('NOW'));
                    $em->persist($size);
                }
                //~ $em->flush();
            }
        }
        $em->flush();

        parent::postUpdate($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Dish $object
     */
    private function fixRelations($object)
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
