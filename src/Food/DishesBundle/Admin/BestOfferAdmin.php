<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * @package Food\DishesBundle\Admin
 */
class BestOfferAdmin extends FoodAdmin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {

        if ($this->isAdmin()) {
            $formMapper->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place'));
        } else {
            // If user is a moderator - he is assigned to a place (unless he is Chuck or Cekuolis)
            $userPlaceId = $this->getUser()->getPlace()->getId();
        }

        $options = array('required' => false, 'label' => 'admin.place_cover_photo.photo');
        if (($pl = $this->getSubject()) && $pl->getPhoto()) {
            $options['help'] = '<img src="/' . $pl->getWebPathThumb() . '" width=200 />';
        }

        $formMapper
            ->add('file', 'file', $options)
            ->add('title')
            ->add('description')
            ->add('goto')
            ->add('active', 'checkbox', array('label' => 'admin.dish.active', 'required' => false,))
        ;

    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('place', null, array('label' => 'admin.place_cover_photo.place'));
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('place')
            ->add('image', 'string', array(
                'template' => 'FoodDishesBundle:Default:list_image_100px.html.twig',
                'label' => 'admin.dish.photo'
            ))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    public function prePersist($object)
    {
        $this->saveFile($object);
        parent::prePersist($object);
    }

    public function preUpdate($object)
    {
        $this->saveFile($object);
        parent::preUpdate($object);
    }
}