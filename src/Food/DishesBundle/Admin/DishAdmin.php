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
use Sonata\AdminBundle\Validator\ErrorElement;
use Food\DishesBundle\Entity\Place;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Food\AppBundle\Validator\Constraints\Slug;
use \Food\AppBundle\Entity\Slug as SlugEntity;

class DishAdmin extends FoodAdmin
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

    protected function configureFormFields(FormMapper $formMapper)
    {
        // Override edit template by our magic one with ajax
        $this->setTemplate('edit', 'FoodDishesBundle:Dish:admin_dish_edit.html.twig');
        $subject = $this->getSubject();

        $formMapper->getFormBuilder()->addEventListener(FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formMapper, $subject) {
                $form = $event->getForm();

                if ($form->has('categories')) {
                    $form->remove('categories');
                }
                if ($form->has('options')) {
                    $form->remove('options');
                }
                $place = $form->get('place')->getData();

                /**
                 * @var EntityManager $em
                 */
                $em = $this->modelManager->getEntityManager('Food\DishesBundle\Entity\FoodCategory');

                /**
                 * @var QueryBuilder
                 */
                $categoryQuery = $em->createQueryBuilder('c')
                    ->select('c')
                    ->from('Food\DishesBundle\Entity\FoodCategory', 'c');

                /**
                 * @var QueryBuilder
                 */
                $optionsQuery = $em->createQueryBuilder('o')
                    ->select('o')
                    ->from('Food\DishesBundle\Entity\DishOption', 'o');

                $categoryQuery->where('c.active = 1');
                $optionsQuery->where('o.hidden = 0');

                if (!empty($place) && $place instanceof Place) {
                    $categoryQuery->andWhere('c.place = :place')
                        ->setParameter('place', $place);
                    $optionsQuery->andWhere('o.place = :place')
                        ->setParameter('place', $place);
                }

                $form->add('categories', null, array('query_builder' => $categoryQuery, 'required' => true, 'multiple' => true,))
                    ->add('options', null, array('query_builder' => $optionsQuery, 'expanded' => true, 'multiple' => true, 'required' => false));
            });

        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\DishesBundle\Entity\Dish',
                'fields' => array(
                    'name' => array(
                        'label' => 'label.name',
                    ),
                    'nameToNav' => array(
                        'label' => 'Navision name'
                    ),
                    'description' => array(
                        'label' => 'label.description'
                    ),
                    'slug' => [
                        'constraints' => new Slug('dish', $formMapper),
                        'attr'=>['data-slugify'=>'name']
                    ]
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
            //->add('categories', null, array('query_builder' => $categoryQuery, 'required' => true, 'multiple' => true,))
            ->add(
                'categories',
                'sonata_type_model',
                array(
                    //'query_builder' => $optionsQuery,
                    'choices' => array(),
                    'btn_add' => false,
                    'multiple' => true,
                    'required' => false
                )
            )
            ->add('timeFrom', null, array('label' => 'admin.dish.time_from', 'required' => false,))
            ->add('timeTo', null, array('label' => 'admin.dish.time_to', 'required' => false,))
            ->add('file', 'file', $options)
            ->add('sizes', 'sonata_type_collection', array(
                'required' => true,
                'by_reference' => false,
                'label' => 'admin.dishes.sizes',
//                    'btn_add' => $this->getContainer()->get('translator')->trans('link_action_create_override', array(), 'SonataAdminBundle')
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
                    'choices' => array(),
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
            );
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('name', null, array('label' => 'admin.dish.name'));
        $datagridMapper->add('place');
        $datagridMapper->add('categories');
    }

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
                ),
                'label' => 'admin.actions'
            ));

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
        $this->slug($object);

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
        $this->slug($object);
        parent::preUpdate($object);

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

    private function slug($object)
    {
        $slugService = $this->getContainer()->get('slug');
        $slugService->generate($object, 'slug', SlugEntity::TYPE_DISH);
    }


}
