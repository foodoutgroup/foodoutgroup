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
            ->add('place')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(\Sonata\AdminBundle\Show\ShowMapper $showMapper)
    {
        parent::configureShowFields($showMapper);

        $showMapper
            ->remove('groups')
            ->remove('dateOfBirth')
            ->remove('firstname')
            ->remove('lastname')
            ->remove('website')
            ->remove('biography')
            ->remove('gender')
            ->remove('locale')
            ->remove('timezone')
            ->remove('phone')
            ->remove('facebookUid')
            ->remove('facebookName')
            ->remove('twitterUid')
            ->remove('twitterName')
            ->remove('gplusUid')
            ->remove('gplusName')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(\Sonata\AdminBundle\Form\FormMapper $formMapper)
    {
        $formMapper
            ->add('username', 'text', array('label' => 'admin.users.username'))
            ->add('email', 'text', array('label' => 'admin.users.email'))
            ->add('place', 'entity', array(
                'class' => 'Food\DishesBundle\Entity\Place',
                'multiple' => false,
                'required' => false,
                'label' => 'admin.users.place'
            ))
            ->add('plainPassword', 'text', array('required' => false, 'label' => 'admin.users.plainPassword'))
        ;

        // TODO ???? Darom - nedarom, kaip darom?
//        if ($this->getSubject() && !$this->getSubject()->hasRole('ROLE_SUPER_ADMIN')) {
//            $formMapper
//                ->with('Management')
//                ->add('realRoles', 'sonata_security_roles', array(
//                    'expanded' => true,
//                    'multiple' => true,
//                    'required' => false
//                ))
//                ->add('locked', null, array('required' => false))
//                ->add('expired', null, array('required' => false))
//                ->add('enabled', null, array('required' => false))
//                ->add('credentialsExpired', null, array('required' => false))
//                ->end()
//            ;
//        }
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

}