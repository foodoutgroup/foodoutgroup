<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\AppBundle\Filter\PlaceFilter;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Security\Acl\Exception\Exception;

class DishSizeAdmin extends FoodAdmin
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
            $formMapper->add(
                'unit',
                'entity',
                array(
                    'group_by' => 'group',
                    'class' => 'Food\DishesBundle\Entity\DishUnit',
                    'multiple' => false,
                    'query_builder' => function ($repository)
                        {
                            $uid = $this->getRequest()->get('uniqid');
                            $req = $this->getRequest()->get($uid);

                            $place = null;
                            if (!empty($req['place'])) {
                                $place = $req['place'];
                            } elseif (!$this->isAdmin()) {
                                $place = $this->getUser()->getPlace()->getId();
                            }
                            if (empty($place)) {
                                // @todo - sugalvoti teisinga
                                // The EPIC FAIL OF FAILS :(
                                $dishId = $this->getRequest()->get('id');
                                $dish = $this->modelManager->getEntityManager('Food\DishesBundle\Entity\Dish')->getRepository('FoodDishesBundle:Dish')->findOneById($dishId);
                                $place = $dish->getPlace()->getId();
                            }

                            return $repository->createQueryBuilder('s')
                                ->where('s.place = ?1')
                                ->setParameter(1, $place);
                        }
                )
            );

        $formMapper->add('code')
            ->add('price')
            ->add('discountPrice')
            ->add('publicPrice')
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('unit', null, array('label' => 'admin.dish.name'))
            ->add('code')
            ->add('price')

        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', 'string', array('label' => 'admin.dish.name'))
            ->add('place')
            ->add('categories')
            ->add('unit')
            ->add('options')
            ->add('price')
            ->add('publicPrice')
            ->add('recomended', null, array('label' => 'admin.dish.recomended', 'editable' => true))
            ->add('createdBy', 'entity', array('label' => 'admin.created_by'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.edited_at'))
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
}