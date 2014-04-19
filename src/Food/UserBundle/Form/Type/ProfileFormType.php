<?php

namespace Food\UserBundle\Form\Type;

use Food\UserBundle\Form\Type\UserAddressFormType;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Validator\Constraint\UserPassword as OldUserPassword;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ProfileFormType extends BaseType
{
    private $class;

    /**
     * @param string $class The User class name
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Food\UserBundle\Entity\User',
            'intention'  => 'profile',
            'csrf_protection' => false,
            'cascade_validation' => true
        ));
    }

    public function getName()
    {
        return 'food_user_profile';
    }

    protected function buildUserForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', null, array('required' => true, 'label' => 'form.firstname', 'translation_domain' => 'FOSUserBundle'))
            ->add('lastname', null, array('required' => true, 'label' => 'form.lastname', 'translation_domain' => 'FOSUserBundle'))
            ->add('email', 'email', array('label' => 'form.email', 'translation_domain' => 'FOSUserBundle'))
        ;
    }
}
