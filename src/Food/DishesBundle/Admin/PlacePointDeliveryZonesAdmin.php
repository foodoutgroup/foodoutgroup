<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * @package Food\DishesBundle\Admin
 */
class PlacePointDeliveryZonesAdmin extends FoodAdmin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $this->setTemplate('edit', 'FoodDishesBundle:PlacePointDeliveryZones:admin_place_point_delivery_zone_edit.html.twig');
        /**
         * @var EntityManager $em
         */
        $em = $this->modelManager->getEntityManager('Food\DishesBundle\Entity\PlacePoint');

        /**
         * @var QueryBuilder
         */
        $categoryQuery = $em->createQueryBuilder('p')
            ->select('p')
            ->from('Food\DishesBundle\Entity\PlacePoint', 'p')
        ;


        $formMapper
            ->add('place',  'entity', array('class' => 'Food\DishesBundle\Entity\Place'))
            ->add('placePoint',  'entity', array('class' => 'Food\DishesBundle\Entity\PlacePoint'))
            ->add('placePoint', null, array('query_builder' => $categoryQuery, 'required' => false))
            ->add('distance', 'text', array('label' => 'Distance'))
            ->add('price', 'text', array('label' => 'Price'))
            ->add('cartSize', 'text', array('label' => 'Cart size'))
            ->add('timeFrom', 'text', array('label' => 'Time from', 'required' => false))
            ->add('timeTo', 'text', array('label' => 'Time to', 'required' => false))
            ->add('active', null, array('required' => false))
            ->end()
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('place', null, array('label' => 'Place'));
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('place')
            ->add('placePoint.toString')
            ->add('distance','string', array('label' => 'Distance', 'editable' => true))
            ->add('price', 'string', array('label' => 'Price', 'editable' => true))
            ->add('cartSize', 'string', array('label' => 'Cart size', 'editable' => true))
            ->add('timeFrom', 'string', array('label' => 'Time From', 'editable' => true))
            ->add('timeTo', 'string', array('label' => 'Time To', 'editable' => true))
            ->add('active', 'boolean', array('label' => 'Active', 'editable' => true))
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
     * @param \Food\DishesBundle\Entity\PlacePointDeliveryZones $object
     * @return mixed|void
     */
    public function prePersist($object)
    {
        $securityContext = $this->getContainer()->get('security.context');
        $user = $securityContext->getToken()->getUser();
        $object->setCreatedAt(new \DateTime('NOW'));
        $object->setCreatedBy($user);
    }

    /**
     * @param \Food\DishesBundle\Entity\PlacePointDeliveryZones $object
     */
    public function preUpdate($object)
    {

    }

}