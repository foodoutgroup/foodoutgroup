<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\DishesBundle\Entity\PlacePointWorkTime;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PlaceAdmin extends FoodAdmin
{
    /**
     * @param FormMapper $formMapper
     *
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

        $options = ['required' => false, 'label' => 'admin.place.logo'];
        if (($pl = $this->getSubject()) && $pl->getLogo()) {
            $options['help'] = '<img src="/' . $pl->getWebPathThumb() . '" />';
        }

        $trans = $this->getContainer()->get('translator');

        $deliveryOptionChoices = [
            Place::OPT_DELIVERY_AND_PICKUP => $trans->trans('admin.place.delivery_option.delivery_and_pickup'),
            Place::OPT_ONLY_DELIVERY       => $trans->trans('admin.place.delivery_option.delivery'),
            Place::OPT_ONLY_PICKUP         => $trans->trans('admin.place.delivery_option.pickup'),
        ];

        $alcoholRules = ['label' => 'admin.place.alcohol_rules', 'required' => false];
        if (!$this->getContainer()->getParameter('alcohol_allowed')) {
            $alcoholRules['display'] = false;
        }

        $formMapper
            ->add('name', 'text', ['label' => 'admin.place.name'])
            ->add(
                'translations',
                'a2lix_translations_gedmo',
                [
                    'translatable_class' => 'Food\DishesBundle\Entity\Place',
                    'fields'             => [
                        'slogan'       => ['label' => 'admin.place.slogan', 'required' => false,],
                        'description'  => ['label' => 'admin.place.description', 'required' => true,],
                        'alcoholRules' => $alcoholRules,
                    ]
                ])
        ;

        if ($this->getContainer()->getParameter('place_slug_manual')) {
            $formMapper->add('slug', null, ['required' => true]);
        }

        $formMapper->add('chain', null, ['label' => 'admin.place.chain', 'required' => false,])
            ->add('navision', 'checkbox', ['label' => 'admin.place.navision', 'required' => false,])
            ->add('kitchens', null, [
                    'query_builder' => $kitchenQuery,
                    'multiple'      => true,
                    'label'         => 'admin.place.kitchens']
            )
            ->add('active', 'checkbox', ['label' => 'admin.active', 'required' => false,])
            ->add('showNotification', 'checkbox', ['label' => 'admin.place.show_notification', 'required' => false,])
            ->add('notificationContent', null, ['label' => 'admin.place.notification_content', 'attr' => ['class' => 'ckeditor_custom']])
            ->add('new', 'checkbox', ['label' => 'admin.is_new', 'required' => false,])
            ->add('recommended', 'checkbox', ['label' => 'admin.place.recommended', 'required' => false,])
            ->add('top', 'checkbox', ['label' => 'TOP', 'required' => false,])
            ->add('discountPricesEnabled', 'checkbox', ['label' => 'admin.place.discount_prices_enabled', 'required' => false,])
            ->add('onlyAlcohol', 'checkbox', ['label' => 'admin.place.only_alcohol', 'required' => false,])
            ->add('sendInvoice', 'checkbox', ['label' => 'admin.place.send_invoice', 'required' => false])
            ->add('deliveryOptions', 'choice', ['label' => 'admin.place.delivery_options', 'required' => true, 'choices' => $deliveryOptionChoices])
            ->add('deliveryTime', null, ['label' => 'admin.place.delivery_time'])
            ->add('pickupTime', null, ['label' => 'admin.place.pickup_time'])
            ->add('deliveryTimeInfo', null, ['label' => 'admin.place.delivery_time_info', 'required' => false])
            ->add('deliveryPrice', null, ['label' => 'admin.place.delivery_price'])
            ->add('cartMinimum', null, ['label' => 'admin.place.cart_minimum'])
            ->add('basketLimitFood', null, ['label' => 'admin.place.cart_food_limit'])
            ->add('basketLimitDrinks', null, ['label' => 'admin.place.cart_drink_limit'])
            ->add('dishesNumeration', 'checkbox', ['label' => 'admin.place.dishes_numeration', 'required' => false])
            ->add('autoInform', 'checkbox', ['label' => 'admin.place.auto_inform', 'required' => false])
            ->add('selfDelivery', 'checkbox', ['label' => 'admin.place.self_delivery', 'required' => false])
            ->add('minimalOnSelfDel', 'checkbox', ['label' => 'admin.place.minimal_on_self_delivery', 'required' => false])
            ->add('cardOnDelivery', 'checkbox', ['label' => 'admin.place.card_on_delivery', 'required' => false])
            ->add('disabledOnlinePayment', 'checkbox', ['label' => 'admin.place.disabled_online_payment', 'required' => false])
            ->add('disabledPaymentOnDelivery', 'checkbox', ['label' => 'admin.place.disabled_payment_on_delivery', 'required' => false])
            ->add('priority', null, ['label' => 'admin.place.priority', 'required' => true])
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
                [
                    //'by_reference' => false,
                    'max_length' => 2,
                    'label'      => 'admin.place_points',
                    'btn_add'    => $this->getContainer()->get('translator')->trans('link_action_create_override', [], 'SonataAdminBundle')
                ],
                [
                    'edit'     => 'inline',
                    'inline'   => 'table',
                    'template' => 'FoodDishesBundle:Default:point_inline_edit.html.twig'
                ]
            )
        ;
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
            ['FoodDishesBundle:Default:place_form_theme.html.twig']
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
            ->add('name', null, ['label' => 'admin.place.name'])
            ->add('discountPricesEnabled', null, ['label' => 'admin.place.discount_prices_enabled'])
            ->add('active', null, ['label' => 'admin.active'])
            ->add('autoInform', null, ['label' => 'admin.place.auto_inform'])
            ->add('selfDelivery', null, ['label' => 'admin.place.self_delivery'])
            ->add('recommended', null, ['label' => 'admin.place.recommended'])
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
            ->addIdentifier('name', 'string', ['label' => 'admin.place.name'])
            ->add('image', 'string', [
                'template' => 'FoodDishesBundle:Default:list_image.html.twig',
                'label'    => 'admin.place.logo'
            ])
            ->add('selfDelivery', null, ['label' => 'admin.place.self_delivery'])
            ->add('active', null, ['label' => 'admin.active', 'editable' => true])
            ->add('new', null, ['label' => 'admin.is_new', 'editable' => true])
            ->add('recommended', null, ['label' => 'admin.place.recommended', 'editable' => true])
            ->add('top', null, ['label' => 'TOP', 'editable' => true])
            ->add('discountPricesEnabled', null, ['label' => 'admin.place.discount_prices_enabled', 'editable' => true,])
            ->add('_action', 'actions', [
                'actions' => [
                    'edit'   => [],
                    'delete' => [],
                ],
                'label'   => 'admin.actions'
            ])
        ;
    }


    /**
     * Set create date before inserting to database
     *
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\Place
     *
     * @return mixed|void
     */
    public function prePersist($object)
    {
        $object->setCreatedAt(new \DateTime());
        $this->_fixPhotos($object);
        $this->saveFile($object);
        parent::prePersist($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     *
     * @return void
     */
    public function preUpdate($object)
    {
        $object->setEditedAt(new \DateTime());
        $this->_fixPhotos($object);
        $this->saveFile($object);
        parent::preUpdate($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     * @param \Food\UserBundle\Entity\User    $user
     */
    private function _fixPoints($object, $user)
    {
        foreach ($object->getPoints() as $point) {
            $point->setPlace($object);
            $this->_fixWorkTime($point);
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
    private function _fixWorkTime(PlacePoint $object)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        foreach ($object->getWorkTimes() as $workTime) {
            $em->remove($workTime);
        }
        $em->flush();

        for ($i = 1; $i <= 7; $i++) {
            $workTime = $object->{'getWd' . $i}();
            $workTime = preg_replace('~\s*-\s*~', '-', $workTime);
            $intervals = explode(' ', $workTime);
            foreach ($intervals as $interval) {
                if (strpos($interval, '-') === false) {
                    continue;
                }
                list($start, $end) = explode('-', $interval);
                if (strlen($start) < 1 || strlen($end) < 1) {
                    continue;
                }

                if (strpos($start, ':') !== false) {
                    list($startHour, $startMin) = explode(':', $start);
                } else {
                    $startHour = $start;
                    $startMin = 0;
                }

                if (strpos($end, ':') !== false) {
                    list($endHour, $endMin) = explode(':', $end);
                } else {
                    $endHour = $end;
                    $endMin = 0;
                }

                // if start time is later thant end time, then we should split it
                if ($endHour < $startHour || $endHour == $startHour && $endMin < $startMin) {
                    $ppwt = new PlacePointWorkTime();
                    $ppwt->setPlacePoint($object)
                        ->setWeekDay($i)
                        ->setStartHour($startHour)
                        ->setStartMin($startMin)
                        ->setEndHour(0)
                        ->setEndMin(0)
                    ;

                    $em->persist($ppwt);

                    // 00:00 - 00:00 must be excluded
                    if ($endHour != 0 || $endMin != 0) {
                        $ppwt = new PlacePointWorkTime();
                        $ppwt->setPlacePoint($object)
                            ->setWeekDay($i < 7 ? $i + 1 : 1)
                            ->setStartHour(0)
                            ->setStartMin(0)
                            ->setEndHour($endHour)
                            ->setEndMin($endMin)
                        ;

                        $em->persist($ppwt);
                    }
                } else {
                    $ppwt = new PlacePointWorkTime();
                    $ppwt->setPlacePoint($object)
                        ->setWeekDay($i)
                        ->setStartHour($startHour)
                        ->setStartMin($startMin)
                        ->setEndHour($endHour)
                        ->setEndMin($endMin)
                    ;

                    $em->persist($ppwt);
                }
            }
        }

        $em->flush();
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
     * Synch da epic place points :)
     *
     * @param Place $object
     */
    public function synchDaPlacePoints($object)
    {
        // sinchronizavimo nereikia - copyright Ramune Ablinge
        if (0 && $object->getId() == 63) {
            $dc = $this->getContainer()->get('doctrine');
            $clone = $dc->getRepository('FoodDishesBundle:Place')->find(142);

            $query = "SELECT * FROM place_point WHERE place = " . $object->getId();
            $stmt = $dc->getConnection()->prepare($query);
            $stmt->execute();
            $pointsOrig = $stmt->fetchAll();

            $query = "SELECT * FROM place_point WHERE place = " . $clone->getId();
            $stmt = $dc->getConnection()->prepare($query);
            $stmt->execute();
            $pointsClone = $stmt->fetchAll();

            $pointsCloneRelation = [];
            foreach ($pointsClone as $point) {
                $pointsCloneRelation[$point['parent_id']] = $point['id'];
            }
            foreach ($pointsOrig as $point) {
                if (!empty($pointsCloneRelation[$point['id']])) {
                    $fieldsForUpdate = [];
                    foreach ($point as $field => $val) {
                        $fieldsForUpdate[$field] = $val;
                    }
                    unset($fieldsForUpdate['id']);
                    unset($fieldsForUpdate['place']);
                    unset($fieldsForUpdate['parent_id']);
                    unset($fieldsForUpdate['no_replication']);
                    unset($fieldsForUpdate['edited_by']);
                    unset($fieldsForUpdate['deleted_by']);
                    $queryParts = [];
                    foreach ($fieldsForUpdate as $field => $val) {
                        if ($field == "deleted_at") {
                            if ($val == "") {
                                $queryParts[] = "`" . $field . "` = NULL";
                            } else {
                                $queryParts[] = "`" . $field . "` = '" . $val . "'";
                            }
                        } else {
                            $queryParts[] = "`" . $field . "` = '" . $val . "'";
                        }
                    }
                    $query = "UPDATE place_point SET ";
                    $query .= implode(",", $queryParts);
                    $query .= " WHERE id=" . $pointsCloneRelation[$point['id']];
                    $stmt = $dc->getConnection()->prepare($query);
                    $stmt->execute();
                } else {
                    $fieldsForInsert = [];
                    foreach ($point as $field => $val) {
                        $fieldsForInsert[$field] = $val;
                    }
                    unset($fieldsForInsert['id']);
                    $fieldsForInsert['place'] = $clone->getId();
                    $fieldsForInsert['parent_id'] = $point['id'];
                    $fieldsForInsert['no_replication'] = 1;
                    $fieldsForInsert['edited_by'] = 1;
                    $fieldsForInsert['deleted_by'] = 1;
                    if ($fieldsForInsert['deleted_at'] == "") {
                        $fieldsForInsert['deleted_at'] = "NULL";
                    }

                    $query = "INSERT INTO place_point (`" . implode("`,`", array_keys($fieldsForInsert)) . "`)";
                    $query .= " VALUES('" . implode("','", $fieldsForInsert) . "')";
                    $query = str_replace("'NULL'", "NULL", $query);

                    $stmt = $dc->getConnection()->prepare($query);
                    $stmt->execute();
                }
            }
        }
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     *
     * @return void
     */
    public function postPersist($object)
    {
        $securityContext = $this->getContainer()->get('security.context');
        $user = $securityContext->getToken()->getUser();
        $this->_fixPoints($object, $user);
        $this->fixSlugs($object);
        parent::postPersist($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     *
     * @return void
     */
    public function postUpdate($object)
    {
        if ($object->getDeletedAt() == null) {
            $this->fixSlugs($object);
            $this->synchDaPlacePoints($object);
        } else {
            // find and soft-delete other stuff
            $em = $this->getContainer()->get('doctrine')->getManager();
            $dishes = $object->getDishes();
            if (count($dishes) > 0) {
                foreach ($dishes as $dish) {
                    $dish->setDeletedAt(new \DateTime('NOW'));
                    $em->persist($dish);

                    $dish_options = $dish->getOptions();
                    if (count($dishes) > 0) {
                        foreach ($dish_options as $option) {
                            $option->setDeletedAt(new \DateTime('NOW'));
                            $em->persist($option);
                        }
                    }
                }
                $em->flush();
            }

            $categories = $object->getCategories();
            if (count($categories) > 0) {
                foreach ($categories as $category) {
                    $category->setDeletedAt(new \DateTime('NOW'));
                    $em->persist($category);
                }
                $em->flush();
            }

            $reviews = $object->getReviews();
            if (count($reviews) > 0) {
                foreach ($reviews as $review) {
                    $review->setDeletedAt(new \DateTime('NOW'));
                    $em->persist($review);
                }
                $em->flush();
            }

            $place_points = $object->getPoints();
            if (count($place_points) > 0) {
                foreach ($place_points as $point) {
                    $point->setDeletedAt(new \DateTime('NOW'));
                    $em->persist($point);

                    $point_zones = $point->getZones();
                    if (count($point_zones) > 0) {
                        foreach ($point_zones as $zone) {
                            $zone->setDeletedAt(new \DateTime('NOW'));
                            $em->persist($zone);
                        }
                    }
                }
                $em->flush();
            }
        }

        $securityContext = $this->getContainer()->get('security.context');
        $user = $securityContext->getToken()->getUser();
        $this->_fixPoints($object, $user);
        parent::postUpdate($object);
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
        $textsForSlugs = [];
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
