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
        $formMapper
            ->add('place', null, array('label' => 'Place'))
            ->add('placePoint', null, array('label' => 'Point'))
            ->add('distance', 'text', array('label' => 'Distance'))
            ->add('price', 'text', array('label' => 'Price'))
            ->add('timeFrom', 'text', array('label' => 'Time from', 'required' => false))
            ->add('timeTo', 'text', array('label' => 'Time to', 'required' => false))
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
            ->add('place', null, array('label' => 'Place'))
            ->addIdentifier('placePoint', 'string', array('label' => 'Point'))
            ->add('distance', null, array('label' => 'Distance', 'editable' => true))
            ->add('price', null, array('label' => 'Price', 'editable' => true))
            ->add('timeFrom', null, array('label' => 'Time From', 'editable' => true))
            ->add('timeTo', null, array('label' => 'Time To', 'editable' => true))
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

    }

    /**
     * @param \Food\DishesBundle\Entity\PlacePointDeliveryZones $object
     */
    public function preUpdate($object)
    {

    }

}