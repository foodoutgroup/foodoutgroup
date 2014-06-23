<?php

namespace Food\UserBundle\Form\Type;

use Food\UserBundle\Form\Type\UserAddressFormType;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
//use Symfony\Component\Security\Core\Validator\Constraint\UserPassword as OldUserPassword;
//use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
//use Food\UserBundle\Form\UserPassword;

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
            'validation_groups' => array(
                'Food\UserBundle\Entity\User',
                'determineValidationGroups',
            ),
        ));
    }

    public function getName()
    {
        return 'food_user_profile';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        if (class_exists('Symfony\Component\Security\Core\Validator\Constraints\UserPassword')) {
//            $constraint = new UserPassword();
//            $constraint->service = 'food_user.user_password_validator';
//            $constraint->validatedBy();
//        } else {
//            // Symfony 2.1 support with the old constraint class
//            $constraint = new OldUserPassword();
//        }

        $builder
            ->add('firstname', null, array('required' => true, 'label' => 'form.firstname', 'translation_domain' => 'FOSUserBundle'))
            ->add('lastname', null, array('required' => true, 'label' => 'form.lastname', 'translation_domain' => 'FOSUserBundle'))
            ->add('email', 'email', array('required' => true, 'label' => 'form.email', 'translation_domain' => 'FOSUserBundle', 'disabled' => true))
            ->add('phone', null, array('required' => true, 'label' => 'form.phone', 'translation_domain' => 'FOSUserBundle', 'attr' => array('placeholder' => '3706xxxxxxx')))
            ->add('current_password', 'password', array(
                'label' => 'form.current_password',
                'translation_domain' => 'FOSUserBundle',
                'mapped' => false,
                // 'constraints' => $constraint,
                'cascade_validation' => true,
                // 'required' => false,
            ))
            ->add('plainPassword', 'repeated', array(
                'type' => 'password',
                'options' => array('translation_domain' => 'FOSUserBundle'),
                'first_options' => array('label' => 'form.new_password'),
                'second_options' => array('label' => 'form.new_password_confirmation'),
                'invalid_message' => 'fos_user.password.mismatch',
                // 'required' => false,
            ))
        ;
    }
}
