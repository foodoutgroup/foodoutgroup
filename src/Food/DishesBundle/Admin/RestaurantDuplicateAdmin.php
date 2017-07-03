<?php

namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class RestaurantDuplicateAdmin extends FoodAdmin
{

    /**
     * Fields to be shown on create/edit forms
     *
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('place', null, array('required' => true));
    }

    /**
     * Fields to be shown on filter forms
     *
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */

    /**
     * Fields to be shown on lists
     *
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('place')
            ->addIdentifier('newPlace')
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('created_by', 'int', array('label' => 'admin.duplicate.user'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    protected function configureShowFields(\Sonata\AdminBundle\Show\ShowMapper $showMapper)
    {
        $showMapper
            ->add('place', null, array('label' => 'admin.duplicate.place'))
            ->add('newPlace', null, array('label' => 'admin.duplicate.new_place'))
            ->add('created_by', 'int', array('label' => 'admin.duplicate.user'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
        ;



    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'show','create'));
    }

    public function prePersist($object)
    {

        $doctrine = $this->getContainer()->get('doctrine');
        $placeRepo = $doctrine->getRepository('FoodDishesBundle:Place');
        $place = $placeRepo->find($object->getPlace());

        $object->setPlace($place);
        $object->setCreatedAt(date('Y-m-d H:i:s'));
        $object->setCreatedBy($this->getUser());

        $duplicatedService = $this->getContainer()->get('food.restaurant.duplicate.service')->DuplicateRestaurant($object->getPlace()->getId());

        $newPlace = $placeRepo->find($duplicatedService);

        $object->setNewPlace($newPlace);

        parent::prePersist($object);
    }


}
