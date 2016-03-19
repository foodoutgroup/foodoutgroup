<?php
namespace Food\DishesBundle\Admin;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\AppBundle\Filter\PlaceFilter;
use Food\DishesBundle\Entity\ComboDiscount;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Food\DishesBundle\Entity\Place;

class ComboDiscountAdmin extends FoodAdmin
{


    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $this->setTemplate('edit','FoodDishesBundle:Dish:admin_combo_discount_edit.html.twig');

        $comboChoices = array(
            ComboDiscount::OPT_COMBO_TYPE_FREE,
            //ComboDiscount::OPT_COMBO_TYPE_DISCOUNT
        );

        $combyApplyBy = array(
            ComboDiscount::OPT_COMBO_APPLY_UNIT
        );

        $formMapper->add('place', null, array('required' => true))
            ->add('name', null, array('required' => true))
            ->add('active')
            ->add('amount')
            ->add('applyBy', 'choice', array('required' =>  true, 'choices' => $combyApplyBy))
            ->add('dishCategory')
            ->add('dishUnit', null, array('required' => false))
            ->add('discountType', 'choice', array('required' =>  true, 'choices' => $comboChoices))
            ->add('discountSize')
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {

    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', 'string')
            ->add('place')
            ->add('active', null, array( 'editable' => true))
            //->add('createdBy', 'entity', array('label' => 'admin.created_by'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;

        $this->setPlaceFilter(new PlaceFilter($this->getSecurityContext()))
            ->setPlaceFilterEnabled(true);
    }

    /**
     * @param \Food\DishesBundle\Entity\ComboDiscount $object
     * @return mixed|void
     */
    public function postPersist($object)
    {
        $user = $this->getContainer()->get('security.context')->getToken()->getUser();
        $object->setCreatedBy($user);
        $object->setCreatedAt(new \DateTime('NOW'));
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->persist($object);
        $em->flush();
    }

    /**
     * @param \Food\DishesBundle\Entity\ComboDiscount $object
     * @return void
     */
    public function postUpdate($object)
    {
        $user = $this->getContainer()->get('security.context')->getToken()->getUser();
        $object->setEditedBy($user);
        $object->setEditedAt(new \DateTime('NOW'));
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->persist($object);
        $em->flush();

        parent::postUpdate($object);
    }

}