<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\AppBundle\Filter\PlaceFilter;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class DishOptionAdmin extends FoodAdmin
{

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\DishesBundle\Entity\DishOption',
                'fields' => array(
                    'name' => array('label' => 'label.name'),
                    'nameToNav' => array(
                        'label' => 'Navision name'
                    ),
                    'description' => array('label' => 'label.description', 'required' => false),
                ),
            )
        )
        ->add('infocode', 'checkbox', array('label' => 'admin.dish_option.infocode', 'required' => false,))
        ->add('firstLevel', 'checkbox', array('label' => 'First level', 'required' => false,))
        ->add('code', null, array('label' => 'admin.dish_option.code', 'required' => false))
        ->add('subCode', null, array('label' => 'admin.dish_option.sub_code', 'required' => false))
        ->add('groupName', null, array('label' => 'admin.dish_option.group_name'))
        ->add('singleSelect', 'checkbox', array('label' => 'admin.dish_option.single_select', 'required' => false));

        if ($this->isAdmin()) {
            $formMapper->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place'));
        }

        $formMapper->add('price');
        $formMapper->add(
            'sizesPrices',
            'sonata_type_collection',
            array('required' => false, 'by_reference' => false, 'label' => 'admin.dish_option_size_price'),
            array('edit' => 'inline','inline' => 'table')
        );
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('place')
            ->add('name', null, array('label' => 'admin.dish_option.name'))
            ->add('infocode', null, array('label' => 'admin.dish_option.infocode'))
            ->add('code', null, array('label' => 'admin.dish_option.code'))
            ->add('subCode', null, array('label' => 'admin.dish_option.sub_code'))
            ->add('price', null, array('label' => 'admin.dish_option.price'));
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', null, array('label' => 'admin.dish_option.name'))
            ->add('place')
            ->add('price', null, array('label' => 'admin.dish_option.price'))
            ->add('firstLevel', null)
            ->add('singleSelect', null, array('label' => 'Single', 'editable' => true))
            ->add('infocode', null, array('label' => 'admin.dish_option.infocode', 'editable' => true))
            ->add('code', null, array('label' => 'admin.dish_option.code', 'editable' => true))
            ->add('subCode', null, array('label' => 'admin.dish_option.sub_code','editable' => true))
            ->add('groupName', null, array('label' => 'admin.dish_option.group_name', 'editable' => true))
            ->add('createdBy', null, array('label' => 'admin.created_by'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.edited_at'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ));

        $this->setPlaceFilter(new PlaceFilter($this->getSecurityContext()))
            ->setPlaceFilterEnabled(true);
    }

    /*
     * If user is a moderator - set place, as he can not choose it. Chuck Norris protection is active
     */
    public function prePersist($object)
    {
        if ($this->isModerator()) {
            $place = $this->modelManager->find('Food\DishesBundle\Entity\Place', $this->getUser()->getPlace()->getId());

            $object->setPlace($place);
        }
        parent::prePersist($object);
        $this->fixRelations($object);
    }

    public function preUpdate($object)
    {
        parent::preUpdate($object);
        $this->fixRelations($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\DishOption $object
     */
    private function fixRelations($object)
    {
        $dishSizesPrices = $object->getSizesPrices();
        if (!empty($dishSizesPrices)) {
            foreach ($dishSizesPrices as $dishSizesPrice) {
                $dishSizesPrice->setDishOption($object);
            }
        }
    }
}
