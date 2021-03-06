<?php
namespace Food\UserBundle\Admin;

use FOS\UserBundle\Model\UserManagerInterface;
use Sonata\UserBundle\Admin\Model\UserAdmin as SonataUserAdmin;


class UserAdmin extends SonataUserAdmin {
    /**
     * {@inheritdoc}
     */
    protected function configureListFields(\Sonata\AdminBundle\Datagrid\ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('username', 'text', array('label' => 'admin.users.username'))
            ->add('email', 'text', array('label' => 'admin.users.email'))
            ->add('place', 'text', array('label' => 'admin.users.place'))
            ->add('enabled', null, array('editable' => true, 'label' => 'admin.users.enabled'))
            ->add('locked', null, array('editable' => true, 'label' => 'admin.users.locked'))
            ->add('noInvoice', null, array('editable' => true, 'label' => 'admin.users.no_invoice'))
            ->add('noMinimumCart', null, array('editable' => true, 'label' => 'admin.users.no_minimum_cart'))
            ->add('isBussinesClient', null, array('editable' => true, 'label' => 'admin.users.bussines_client'))
            ->add('companyName', null, array('label' => 'admin.users.company_name'))
            ->add('lastLogin', null, array('format' => 'Y-m-d H:i:s', 'label' => 'admin.users.last_login'))
            ->add('special', null, array('editable' => true, 'label' => 'admin.users.special'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(\Sonata\AdminBundle\Datagrid\DatagridMapper $filterMapper)
    {


        $filterMapper
            ->add('id')
            ->add('username', null, array('label' => 'admin.users.username'))
            ->add('locked', null, array('label' => 'admin.users.locked'))
            ->add('email', null, array('label' => 'admin.users.email'))
            ->add('phone', null, array('label' => 'admin.users.phone'))
            ->add('createdAt', 'doctrine_orm_date_range', array('label'=>'admin.users.created_at'), null, array('widget' => 'single_text', 'required' => false,  'attr' => array('class' => 'datepicker2')))
            ->add('place')
            ->add('noInvoice', null, array('label' => 'admin.users.no_invoice'))
            ->add('noMinimumCart', null, array('label' => 'admin.users.no_minimum_cart'))
            ->add('isBussinesClient', null, array('label' => 'admin.users.bussines_client'))
            ->add('companyName', null, array('label' => 'admin.users.company_name'))
            ->add('group', null, array('label' => 'admin.users.group'))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(\Sonata\AdminBundle\Show\ShowMapper $showMapper)
    {
        parent::configureShowFields($showMapper);

        $showMapper
            ->with('General')
            ->add('createdAt', null, array('label' => 'admin.users.created_at'))
            ->add('group', null, array('label' => 'admin.users.group'))
            ->add('special', null, array('label' => 'admin.users.special'))
            ->remove('groups')
            ->remove('dateOfBirth')
            ->remove('website')
            ->remove('biography')
            ->remove('gender')
            ->remove('locale')
            ->remove('timezone')
            ->remove('facebookUid')
            ->remove('facebookName')
            ->remove('twitterUid')
            ->remove('twitterName')
            ->remove('gplusUid')
            ->remove('gplusName')
            ->with('Business')
            ->add('locale', null, array('label' => 'admin.users.locale'))
            ->add('isBussinesClient', null, array('label' => 'admin.users.bussines_client'))
            ->add('companyName', null, array('label' => 'admin.users.company_name'))
            ->add('companyCode', null, array('label' => 'admin.users.company_code'))
            ->add('vatCode', null, array('label' => 'admin.users.vat_code'))
            ->add('company_address', null, array('label' => 'admin.users.company_address'))
            ->add('checkingAccount', null, array('label' => 'admin.users.checking_account'))
            ->add('workersCount', null, array('label' => 'admin.users.workers_count'))
            ->add('directorFirstName', null, array('label' => 'admin.users.director_first_name'))
            ->add('directorLastName', null, array('label' => 'admin.users.director_last_name'))
            ->add('discount', null, array('label' => 'admin.users.discount'))
            ->add('allowDelayPayment', null, array('label' => 'admin.users.allow_delay_payment'))
            ->add('currentDiscount', 'sonata_type_collection',
                array(
                    'label' => 'admin.users.current_discount',
                    'template' => 'FoodUserBundle:Admin:current_discount.html.twig'
                )
            )
        ;

        $groups = $showMapper->getAdmin()->getShowGroups();
        unset($groups['Social']);
        unset($groups['Security']);
        unset($groups['Groups']);
        $showMapper->getAdmin()->setShowGroups($groups);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(\Sonata\AdminBundle\Form\FormMapper $formMapper)
    {
        $formMapper
            ->add('firstname', 'text', array('label' => 'admin.users.firstname'))
            ->add('lastname', 'text', array('label' => 'admin.users.lastname', 'required' => false))
            ->add('username', 'text', array('label' => 'admin.users.username'))
            ->add('email', 'text', array('label' => 'admin.users.email'))
            ->add('phone',
                  'text',
                  array('label' => 'admin.users.phone',
                        'attr' => array('placeholder' => '+370xxxxxxx'),
                        'required' => false))
            ->add('group', null, array('label' => 'admin.users.group'))
            ->add('place', 'entity', array(
                'class' => 'Food\DishesBundle\Entity\Place',
                'multiple' => false,
                'required' => false,
                'label' => 'admin.users.place'
            ))
            ->add('plainPassword', 'text', array('required' => false, 'label' => 'admin.users.plainPassword'))
            ->add('special', 'checkbox', array('label' => 'admin.users.special','required'=>false))
        ;

        if ($this->getSubject() && !$this->getSubject()->hasRole('ROLE_SUPER_ADMIN')) {
            $formMapper
                ->with('admin.users.management')
                ->add(
                    'roles',
                    'choice',
                    array(
                        'expanded' => true,
                        'multiple' => true,
                        'required' => false,
                        'label' => 'admin.users.roles',
                        'choices' => array(
                            'ROLE_ADMIN' => 'ROLE_ADMIN: ROLE_MODERATOR',
                            'ROLE_MODERATOR' => 'ROLE_MODERATOR',
                            'ROLE_SUPPORT' => 'ROLE_SUPPORT',
                            'ROLE_DISPATCHER' => 'ROLE_DISPATCHER',
                            'ROLE_USER' => 'ROLE_USER',
                            'ROLE_MARKETING' => 'ROLE_MARKETING',
                            'ROLE_EDITOR' => 'ROLE_EDITOR',
                        )
                    )
                )
                ->add('locked', null, array('required' => false, 'label' => 'admin.users.locked'))
                ->add('enabled', null, array('required' => false, 'label' => 'admin.users.enabled'))
                ->end()
                ->with('admin.users.bussines_management')
                ->add('isBussinesClient', null, array('required' => false, 'label' => 'admin.users.bussines_client'))
                ->add('companyName', null, array('required' => false, 'label' => 'admin.users.company_name'))
                ->add('companyCode', null, array('required' => false, 'label' => 'admin.users.company_code'))
                ->add('vatCode', null, array('required' => false, 'label' => 'admin.users.vat_code'))
                ->add('company_address', null, array('required' => false, 'label' => 'admin.users.company_address'))
                ->add('checkingAccount', null, array('required' => false, 'label' => 'admin.users.checking_account'))
                ->add('workersCount', null, array('required' => false, 'label' => 'admin.users.workers_count'))
                ->add('directorFirstName', null, array('required' => false, 'label' => 'admin.users.director_first_name'))
                ->add('directorLastName', null, array('required' => false, 'label' => 'admin.users.director_last_name'))
                ->add('discount', null, array('required' => false, 'label' => 'admin.users.discount'))
                ->add('allowDelayPayment', null, array('required' => false, 'label' => 'admin.users.allow_delay_payment'))
                ->add('requiredDivision', null, array('required' => false, 'label' => 'admin.users.required_division'))
                ->add('regeneratePassword', null, array('required' => false, 'label' => 'admin.users.regenerate_password'))
                ->add('divisionCodes', 'sonata_type_collection',
                    array('required' => false, 'label' => 'admin.users.division_codes'),
                    array(
                        'edit' => 'inline',
                        'inline' => 'table',
//                        'template' => 'FoodDishesBundle:Default:point_inline_edit.html.twig'
                    )
                )
                ->add('contractNumber', null)
                ->end()
                ->with('admin.users.other')
                ->add('noInvoice', null, array('required' => false, 'label' => 'admin.users.no_invoice'))
                ->add('noMinimumCart', null, array('required' => false, 'label' => 'admin.users.no_minimum_cart'))
                ->end()
            ;
        }
    }

    /**
     * @inheritdoc
     *
     * @return UserManagerInterface
     */
    public function getUserManager()
    {
        return $this->getConfigurationPool()->getContainer()->get('fos_user.user_manager');
    }

    /**
     * If user is a moderator - set place, as he can not choose it. Chuck Norris protection is active
     *
     * @param \Food\UserBundle\Entity\User $object
     * @return void
     */
    public function prePersist($object)
    {

        parent::prePersist($object);
        $this->fixRelations($object);
    }

    /**
     * @param \Food\UserBundle\Entity\User $object
     * @return void
     */
    public function preUpdate($object)
    {
        $this->fixRelations($object);
        $this->getUserManager()->updateCanonicalFields($object);
        $this->getUserManager()->updatePassword($object);
    }

    /**
     * @param \Food\UserBundle\Entity\User $object
     */
    private function fixRelations($object)
    {
        $divisions = $object->getDivisionCodes();
        if (!empty($divisions)) {
            foreach ($divisions as $division) {
                $division->setUser($object);
            }
        }
    }
}
