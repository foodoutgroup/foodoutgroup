<?php

namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * @package Food\DishesBundle\Admin
 */
class BannerLinkAdmin extends FoodAdmin
{

    protected function configureFormFields(FormMapper $formMapper)
    {
        $options = array('required' => false, 'label' => 'admin.place_cover_photo.photo');
        if (($pl = $this->getSubject()) && $pl->getPhoto()) {
            $options['help'] = '<img src="/' . $pl->getWebPathThumb() . '" width=200 />';
        }

        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\DishesBundle\Entity\BannerLinks',
                'fields' => array(
                    'text' => [],
                )
            ));

        $formMapper
            ->add('placeFrom', 'entity', ['class' => 'Food\DishesBundle\Entity\Place', 'required' => true, 'label' => 'admin.banner_link.place_from'])
            ->add('placeTo', 'entity', ['class' => 'Food\DishesBundle\Entity\Place', 'required' => true, 'label' => 'admin.banner_link.place_to'])
            ->add('file', 'file', $options)
            ->add('active', 'checkbox', ['label' => 'admin.active', 'required' => false])
            ->add('color', null, ['label' => 'admin.banner_link.color']);
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('placeFrom', null, array('label' => 'admin.place_cover_photo.place'));
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('placeFrom', null, ['label' => 'admin.banner_link.place_from'])
            ->add('placeTo', null, ['label' => 'admin.banner_link.place_to'])
            ->add('image', 'string', array(
                'template' => 'FoodDishesBundle:Default:list_image_100px.html.twig',
                'label' => 'admin.dish.photo'
            ))
            ->add('color', null, ['label' => 'admin.banner_link.color'])
            ->add('text', null, ['label' => 'admin.banner_link.text'])
            ->add('active', 'checkbox', ['label' => 'admin.active'])
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ));

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