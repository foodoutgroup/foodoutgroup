<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class DishUnitCategoryAdmin extends FoodAdmin
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

        $formMapper->add('translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\DishesBundle\Entity\DishUnitCategory',
                'fields' => array(
                    'name' => array('label' => 'label.name')
                )
            )
        );
        $formMapper->add('place', null, array('label' => 'admin.dish.unit.category.place'));

    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'admin.dish.unit.category.name'))
            ->add('place', null, array('label' => 'admin.dish.unit.category.place'))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', 'string', array('label' => 'admin.dish.unit.category.name'))
            ->add('place', 'entity', array('label' => 'admin.dish.unit.category.place'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    public function preRemove($object)
    {
        // Trinant kategorija - turi istrinti ir pacius matavimo vienetus
        $doctrine = $this->getContainer()->get('doctrine');

        $em = $doctrine->getManager();
        $units = $doctrine->getRepository('FoodDishesBundle:DishUnit')
            ->findBy(array('unitCategory' => $object));

        $sizes = array();
        if (count($units) > 0) {
            $sizes = $doctrine->getRepository('FoodDishesBundle:DishSize')
                ->findBy(array('unit' => $units));
        }

        if (count($sizes) == 0) {
            foreach($units as $unit) {
                $em->remove($unit);
                $em->flush();
            }

            parent::preRemove($object);
        } else {
            throw new \Exception('Some units, that belong to this category are assigned to dishes. Can not delete');
        }
    }

    /**
     * @param mixed $object
     * @return mixed|void
     *
     * @codeCoverageIgnore
     */
    public function postRemove($object)
    {
        // Do nothing after delete
    }
}