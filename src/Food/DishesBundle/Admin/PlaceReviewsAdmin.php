<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\AppBundle\Filter\PlaceFilter;
use Food\DishesBundle\Entity\PlaceReviews;
use Food\UserBundle\Entity\UserAddress;
use FOS\UserBundle\Model\User;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PlaceReviewsAdmin extends FoodAdmin
{

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        // Is it edit?
        if ($this->id($this->getSubject())) {
            $fieldDisabled = true;
        }
        // Or is it create?
        else {
            $fieldDisabled = false;
        }

        if ($this->isAdmin()) {
            $formMapper
                ->add('place', 'entity', array('class' => 'Food\DishesBundle\Entity\Place'))
                ->add('rate', null, array('label' => 'admin.place.review.rate'));
        }
        $formMapper
            ->add('createdBy', 'entity', array(
                'class' => 'Food\UserBundle\Entity\User',
                'label' => 'admin.created_by',
                'disabled' => $fieldDisabled,
                'required' => false,
            ));

        if ($this->isAdmin() && !$fieldDisabled) {
            $formMapper
                ->add(
                    'newuser',
                    'text',
                    array(
                        'mapped' => false,
                        'label' => 'admin.place.review.created_by_new',
                        'attr' => array(
                            'placeholder' => $this->trans('admin.place.review.created_by_new_placeholder')
                        )
                    )
                )
                ->add(
                    'newcity',
                    'text',
                    array(
                        'mapped' => false,
                        'label' => 'admin.place.review.city_new',
                    )
                )
                ->add('createdAt', null, array('format' => 'Y-m-d H:i:s', 'label' => 'admin.place.review.created_at'));
        }

        $formMapper
            ->add('review', 'textarea', array('label' => 'admin.place.review'))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        if ($this->isAdmin()) {
            $datagridMapper
                ->add('place');
        }
//        $datagridMapper
//            ->add('createdBy', null, array('label' => 'admin.created_by'))
//            ->add(
//                'createdAt',
//                'doctrine_orm_datetime_range',
//                array('label' => 'admin.created_at', 'format' => 'Y-m-d',),
//                null,
//                array(
//                    'widget' => 'single_text',
//                    'required' => false,
//                    'format' => 'Y-m-d',
//                    'attr' => array('class' => 'datepicker')
//                )
//            )
//        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('review', 'string', array('label' => 'admin.place.review'))
            ->add('createdBy', 'entity', array('label' => 'admin.created_by'));
        if ($this->isAdmin()) {
            $listMapper
                ->add('place', 'entity');
        }
        $listMapper
            ->add('rate', 'string', array('label' => 'admin.place.review.rate'))
            ->add('createdAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.created_at'))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.edited_at'))
            ->add('editedBy', 'entity', array('label' => 'admin.edited_by'))
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
     * Set create date before inserting to database
     *
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\PlaceReviews $object
     * @return mixed|void
     */
    public function prePersist($object)
    {
        // Create new user in whose name we'll be posting this nonsense
        $uniqid = $_GET['uniqid'];
        $formData = $this->getRequest()->request->get($uniqid);
        $newUserName = $formData['newuser'];
        $newCity = $formData['newcity'];

        $fos = $this->getContainer()->get('fos_user.user_manager');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $domain = $this->getContainer()->getParameter('domain');

        $possibleUserName = $newUserName.'_comment_dummy@'.$domain;
        $exists = $this->checkDummyUserExists($possibleUserName, $newCity, $fos);

        if ($exists) {
            $counter = 1;
            while ($exists) {
                $possibleUserName = $newUserName.$counter.'_comment_dummy@'.$domain;

                $exists = $this->checkDummyUserExists($possibleUserName, $newCity, $fos);

                $counter++;
            }
        }

        // Maby we returned that it does not exist, but we want to reuse it :)
        $reuseableUser = $userCheck = $fos->findUserByEmail($possibleUserName);
        if (!empty($userCheck) && $userCheck instanceof User && $userCheck->getId())
        {
            $newUser = $reuseableUser;
        } else {
            /**
             * @var $newUser \Food\UserBundle\Entity\User
             */
            $newUser = $fos->createUser();
            $newUser->setEmail($possibleUserName);
            $newUser->setFirstname($newUserName);
            $newUser->setPhone('370600000001');
            $newUser->setFullyRegistered(0)
                ->setRoles(array('ROLE_USER'));

            $newUser->setPassword('super_slaptas_dummy_passw');
            $fos->updateUser($newUser);

            $address = new UserAddress();
            $address->setCity($newCity)
                ->setAddress('Laisves pr.')
                ->setDefault(1)
                ->setLat('25.00000')
                ->setLon('25.00000')
                ->setUser($newUser);

            $em->persist($address);
            $em->flush();
        }

        $object->setCreatedBy($newUser);
        $object->setDummy(true);
    }

    public function postPersist($object)
    {
        parent::postPersist($object);

        $placeService = $this->getContainer()->get('food.places');

        $place = $object->getPlace();

        $rating = $placeService->calculateAverageRating($place);
        $place->setAverageRating($rating);
        $place->setReviewCount(count($place->getReviews()));
        $placeService->savePlace($place);
    }

    /**
     * @param string $possibleUserName
     * @param string $newCity
     * @param $fos
     * @return bool
     */
    private function checkDummyUserExists($possibleUserName, $newCity, $fos)
    {
        $userCheck = $fos->findUserByEmail($possibleUserName);
        if (!empty($userCheck) && $userCheck instanceof User && $userCheck->getId())
        {
            $addressCheck = $userCheck->getDefaultAddress();

            if ($addressCheck && $addressCheck->getCity() != $newCity) {
                $exists = true;
            } else {
                $exists = false;
            }
        } else {
            $exists = false;
        }

        return $exists;
    }
}