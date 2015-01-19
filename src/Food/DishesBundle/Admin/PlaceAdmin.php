<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\DishesBundle\Entity\Place;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PlaceAdmin extends FoodAdmin
{
    /**
     * @param FormMapper $formMapper
     * @codeCoverageIgnore
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        /**
         * @var EntityManager $em
         */
        $em = $this->modelManager->getEntityManager('Food\DishesBundle\Entity\Kitchen');
        /**
         * @var QueryBuilder
         */
        $kitchenQuery = $em->createQueryBuilder('k')
            ->select('k')
            ->from('Food\DishesBundle\Entity\Kitchen', 'k')
            ->where('k.visible = 1')
        ;

        $options = array('required' => false, 'label' => 'admin.place.logo');
        if (($pl = $this->getSubject()) && $pl->getLogo()) {
            $options['help'] = '<img src="/' . $pl->getWebPathThumb() . '" />';
        }

        $deliveryOptionChoices = array(
            Place::OPT_DELIVERY_AND_PICKUP => 'admin.place.delivery_option.delivery_and_pickup',
            Place::OPT_ONLY_DELIVERY => 'admin.place.delivery_option.delivery',
            Place::OPT_ONLY_PICKUP => 'admin.place.delivery_option.pickup',
        );

        $formMapper
            ->add('name', 'text', array('label' => 'admin.place.name'))
            ->add(
                'translations',
                'a2lix_translations_gedmo',
                array(
                    'translatable_class' => 'Food\DishesBundle\Entity\Place',
                    'fields' => array(
                        'slogan' => array('label' => 'admin.place.slogan', 'required' => false,),
                        'description' => array('label' => 'admin.place.description', 'required' => true,),
                        'alcoholRules' => array('label' => 'admin.place.alcohol_rules', 'required' => false,),
                )
            ))
            ->add('chain', null, array('label' => 'admin.place.chain', 'required' => false,))
            ->add('navision', 'checkbox', array('label' => 'admin.place.navision', 'required' => false,))
            ->add('kitchens', null, array(
                'query_builder' => $kitchenQuery,
                'multiple' => true,
                'label' => 'admin.place.kitchens')
            )
            ->add('active', 'checkbox', array('label' => 'admin.active', 'required' => false,))
            ->add('new', 'checkbox', array('label' => 'admin.is_new', 'required' => false,))
            ->add('recommended', 'checkbox', array('label' => 'admin.place.recommended', 'required' => false,))
            ->add('top', 'checkbox', array('label' => 'TOP', 'required' => false,))
            ->add('discountPricesEnabled', 'checkbox', array('label' => 'admin.place.discount_prices_enabled', 'required' => false,))
            ->add('onlyAlcohol', 'checkbox', array('label' => 'admin.place.only_alcohol', 'required' => false,))
            ->add('sendInvoice', 'checkbox', array('label' => 'admin.place.send_invoice', 'required' => false))
            ->add('deliveryOptions', 'choice', array('label' => 'admin.place.delivery_options', 'required' => true, 'choices' => $deliveryOptionChoices))
            ->add('deliveryTime', null, array('label' => 'admin.place.delivery_time'))
            ->add('deliveryTimeInfo', null, array('label' => 'admin.place.delivery_time_info', 'required' => false))
            ->add('deliveryPrice', null, array('label' => 'admin.place.delivery_price'))
            ->add('cartMinimum', null, array('label' => 'admin.place.cart_minimum'))
            ->add('selfDelivery', 'checkbox', array('label' => 'admin.place.self_delivery', 'required' => false))
            ->add('minimalOnSelfDel', 'checkbox', array('label' => 'admin.place.minimal_on_self_delivery', 'required' => false))
            ->add('cardOnDelivery', 'checkbox', array('label' => 'admin.place.card_on_delivery', 'required' => false))
            ->add('disabledOnlinePayment', 'checkbox', array('label' => 'admin.place.disabled_online_payment', 'required' => false))
            ->add('priority', null, array('label' => 'admin.place.priority', 'required' => false))

            ->add('file', 'file', $options)
        /*
            ->add('photos', 'sonata_type_collection',
                array(
                    //'by_reference' => true,
                    'max_length' => 2,
                    'label' => 'admin.place_cover_photos',
                ),
                array(
                    'edit' => 'inline',
                    'inline' => 'table',
                )
            )
        */
            ->add('points', 'sonata_type_collection',
                array(
                    //'by_reference' => false,
                    'max_length' => 2,
                    'label' => 'admin.place_points'
                ),
                array(
                    'edit' => 'inline',
                    'inline' => 'table',
                    'template' => 'FoodDishesBundle:Default:point_inline_edit.html.twig'
                )
            );
    }


    /**
     * @return array
     *
     * @codeCoverageIgnore
     */
    public function getFormTheme()
    {
        return array_merge(
            parent::getFormTheme(),
            array('FoodDishesBundle:Default:place_form_theme.html.twig')
        );
    }

    /**
     * @param DatagridMapper $datagridMapper
     *
     * @codeCoverageIgnore
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'admin.place.name'))
            ->add('discountPricesEnabled', null, array('label' => 'admin.place.discount_prices_enabled'))
            ->add('active', null, array('label' => 'admin.active'))
            ->add('recommended', null, array('label' => 'admin.place.recommended'))
        ;
    }

    /**
     * @param ListMapper $listMapper
     *
     * @codeCoverageIgnore
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', 'string', array('label' => 'admin.place.name'))
            ->add('image', 'string', array(
                'template' => 'FoodDishesBundle:Default:list_image.html.twig',
                'label' => 'admin.place.logo'
            ))
            ->add('active', null, array('label' => 'admin.active', 'editable' => true))
            ->add('new', null, array('label' => 'admin.is_new', 'editable' => true))
            ->add('recommended', null, array('label' => 'admin.place.recommended', 'editable' => true))
            ->add('top', null, array('label' => 'TOP', 'editable' => true))
            ->add('discountPricesEnabled', null, array('label' => 'admin.place.discount_prices_enabled', 'editable' => true,))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }


    /**
     * Set create date before inserting to database
     *
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\Place
     * @return mixed|void
     */
    public function prePersist($object)
    {
        // The magic container is here
        $securityContext = $this->getContainer()->get('security.context');
        $user = $securityContext->getToken()->getUser();
        $this->_fixPoints($object, $user);
        $this->_fixPhotos($object);
        $this->saveFile($object);
        parent::prePersist($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     */
    public function preUpdate($object)
    {
        $container = $this->getConfigurationPool()->getContainer();
        $securityContext = $container->get('security.context');
        $this->_fixPoints($object, $securityContext->getToken()->getUser());
        $this->_fixPhotos($object);
        $this->saveFile($object);
        parent::preUpdate($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     * @param \Food\UserBundle\Entity\User $user
     */
    private function _fixPoints($object, $user)
    {
        foreach ($object->getPoints() as $point) {
            $point->setPlace($object);
            $this->_fixExtendedWorkTime($point);
            $cAt = $point->getCreatedAt();
            if (empty($cAt)) {
                $point->setCreatedAt(new \DateTime('now'));
            }
            $createdBy = $point->getCreatedBy();
            if (empty($createdBy)) {
                $point->setCreatedBy($user);
            }
        }
    }

    /**
     * @param \Food\DishesBundle\Entity\PlacePoint $object
     */
    private function _fixExtendedWorkTime($object)
    {
        for($i = 1; $i<=7; $i++) {
            $wt = explode(":", $object->{'getWd'.$i.'End'}());
            $val = $object->{'getWd'.$i.'End'}();
            if (sizeof($wt) == 2) {
                if ($wt[0] <= 6) {
                    $wt[0] = $wt[0] + 24;
                    $object->{'setWd'.$i.'EndLong'}(implode("", $wt));
                } else {
                    $object->{'setWd'.$i.'EndLong'}(implode("", $wt));
                }
            } else {
                $object->{'setWd'.$i.'EndLong'}($val);
            }
        }
    }
    /**
     * @param \Food\DishesBundle\Entity\Place $object
     */
    private function _fixPhotos($object)
    {
       // foreach($object->getPhotos() as $photo) {
       //     $photo->setPlace($object);
       // }
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     */
    public function postPersist($object)
    {
        $this->fixSlugs($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     */
    public function postUpdate($object)
    {
        $this->fixSlugs($object);
    }

    /**
     * Lets fix da stufffff.... Slugs for Place :)
     *
     * @param \Food\DishesBundle\Entity\Place $object
     */
    private function fixSlugs($object)
    {
        $origName = $object->getOrigName($this->modelManager->getEntityManager('FoodDishesBundle:Place'));
        $locales = $this->getContainer()->getParameter('available_locales');
        $textsForSlugs = array();
        foreach ($locales as $loc) {
            if (!isset($textsForSlugs[$loc])) {
                $textsForSlugs[$loc] = $origName;
            }
        }

        $languages = $this->getContainer()->get('food.app.utils.language')->getAll();
        $slugUtelyte = $this->getContainer()->get('food.dishes.utils.slug');
        foreach ($languages as $loc) {
            $slugUtelyte->generateEntityForPlace($loc, $object->getId(), $textsForSlugs[$loc]);
        }
    }
}