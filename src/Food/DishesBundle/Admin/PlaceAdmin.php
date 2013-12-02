<?php
namespace Food\DishesBundle\Admin;

use Food\UserBundle\Entity\User;
use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PlaceAdmin extends FoodAdmin
{
    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        /**
         * @var EntityManager $em
         */
        $em = $this->modelManager->getEntityManager('Food\DishesBundle\Entity\Kitchen');
        /**
         * @var QueryBuilder
         */
        $kitchenQuery = $em->createQueryBuilder('k')
            ->select('k')
            ->from('Food\DishesBundle\Entity\Kitchen', 'k')
            ->where('k.visible = 1')
        ;

        $options = array('required' => false, 'label' => 'admin.place.logo');
        if (($pl = $this->getSubject()) && $pl->getLogo()) {
            $options['help'] = '<img src="/' . $pl->getWebPath() . '" />';
        }

        $formMapper
            ->add('name', 'text', array('label' => 'admin.place.name'))
            ->add('kitchens', null, array(
                'query_builder' => $kitchenQuery,
                'multiple' => true,
                'label' => 'admin.place.kitchens')
            )
            ->add('active', 'checkbox', array('label' => 'admin.active', 'required' => false,))
            ->add('file', 'file', $options)
            ->add('points', 'sonata_type_collection',
                array(
                    //'by_reference' => false,
                    'max_length' => 2,
                    'label' => 'admin.place_points',
                ),
                array(
                    'edit' => 'inline',
                    'inline' => 'table'
                )
            );
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'admin.place.name'))
            ->add('active', null, array('label' => 'admin.active'))
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', 'string', array('label' => 'admin.place.name'))
            ->add('image', 'string', array(
                'template' => 'FoodDishesBundle:Default:list_image.html.twig',
                'label' => 'admin.place.logo'
            ))
            ->add('active', null, array('label' => 'admin.active', 'editable' => true))
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
     * Set create date before inserting to database
     *
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\Place
     * @return mixed|void
     */
    public function prePersist($object)
    {
        // The magic container is here
        $securityContext = $this->getContainer()->get('security.context');
        $user = $securityContext->getToken()->getUser();
        $this->_fixPoints($object, $user);
        $this->saveFile($object);
        parent::prePersist($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     */
    public function preUpdate($object)
    {
        $container = $this->getConfigurationPool()->getContainer();
        $securityContext = $container->get('security.context');
        $this->_fixPoints($object, $securityContext->getToken()->getUser());
        $this->saveFile($object);
        parent::preUpdate($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     * @param \Food\UserBundle\Entity\User $user
     */
    private function _fixPoints($object, $user)
    {
        foreach ($object->getPoints() as $point) {
            $point->setPlace($object);
            $cAt = $point->getCreatedAt();
            if (empty($cAt)) {
                $point->setCreatedAt(new \DateTime('now'));
            }
            $createdBy = $point->getCreatedBy();
            if (empty($createdBy)) {
                $point->setCreatedBy($user);
            }
        }
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     */
    public function postPersist($object)
    {
        $this->fixSlugs($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     */
    public function postUpdate($object)
    {
        $this->fixSlugs($object);
    }

    /**
     * Lets fix da stufffff.... Slugs for Place :)
     *
     * @param \Food\DishesBundle\Entity\Place $object
     */
    private function fixSlugs($object)
    {
        $origName = $object->getName();
        $locales = $this->getContainer()->getParameter('available_locales');
        $textsForSlugs = array();
        foreach ($locales as $loc) {
            if (!isset($textsForSlugs[$loc])) {
                $textsForSlugs[$loc] = $origName;
            }
        }

        $languages = $this->getContainer()->get('food.app.utils.language')->getAll();
        $slugUtelyte = $this->getContainer()->get('food.dishes.utils.slug');
        foreach ($languages as $loc) {
            $slugUtelyte->generateEntityForPlace($loc, $object->getId(), $textsForSlugs[$loc]);
        }
    }
}