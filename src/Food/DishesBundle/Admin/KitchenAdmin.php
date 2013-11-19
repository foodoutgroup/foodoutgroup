<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class KitchenAdmin extends FoodAdmin
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
        $options = array('required' => false, 'label' => 'admin.kitchen.logo');

        if (($pl = $this->getSubject()) && $pl->getLogo()) {
            $this->getUploadService()->setObject($pl);
            $options['help'] = '<img src="/' . $pl->getWebPath() . '" />';
        }

        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\DishesBundle\Entity\Kitchen',
                'fields' => array(
                    'name' => array('label' => 'label.name'),
                )
            ))
            ->add('file', 'file', $options)
            ->add('visible', 'checkbox', array(
                'required' => false,
                'label' => 'admin.visible'
            ))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add('visible')
            ->add('createdBy')
            ->add('createdAt')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', 'string', array('label' => 'admin.kitchen.name'))
            ->add('logo', 'string', array(
                'template' => 'FoodDishesBundle:Default:list_image.html.twig',
                'label' => 'admin.kitchen.logo')
            )
            ->add('visible', null, array('label' => 'admin.visible'))
            ->add('createdBy', 'entity', array('label' => 'admin.created_by'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.edited_at'))
        ;
    }

    /**
     * Save file before saving to db
     *
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\Kitchen
     * @return mixed|void
     */
    public function prePersist($object)
    {
        $this->saveFile($object);
        parent::prePersist($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Kitchen $object
     * @return mixed|void
     */
    public function preUpdate($object)
    {
        $this->saveFile($object);
        parent::preUpdate($object);
    }
}