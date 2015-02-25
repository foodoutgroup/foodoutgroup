<?php

namespace Food\PlacesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class BestOfferAdmin extends FoodAdmin
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

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $options = ['data_class' => null, 'label' => 'admin.best_offers.image'];

        if (($pl = $this->getSubject()) && $pl->getImage()) {
            $options['help'] = '<img src="/' . $pl->getWebPathThumb() . '" />';
        }

        $formMapper->add('title', 'text', ['label' => 'admin.best_offers.title'])
                   ->add('link', 'text', ['label' => 'admin.best_offers.link'])
                   ->add('text', 'textarea', ['label' => 'admin.best_offers.text'])
                   ->add('active', 'checkbox', ['label' => 'admin.best_offers.active', 'required' => false])
                   ->add('file', 'file', $options);
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title', null, ['label' => 'admin.best_offers.title'])
            ->add('text', null, ['label' => 'admin.best_offers.text'])
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('title', 'string', array('label' => 'admin.best_offers.title'))
            ->addIdentifier('link', 'string', array('label' => 'admin.best_offers.link'))
            ->addIdentifier('image', 'string', array('label' => 'admin.best_offers.image', 'template' => 'FoodPlacesBundle:Default:list_image.html.twig'))
            ->addIdentifier('active', 'boolean', array('label' => 'admin.best_offers.active'))
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
