<?php

namespace Food\UserBundle\Form\Type;

use Food\UserBundle\Form\Type\UserAddressFormType;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ChangePasswordFormType extends BaseType
{
    private $class;
    private $currentPassword;

    /**
     * @param string $class The User class name
     */
    public function __construct($class, $currentPassword)
    {
        $this->class = $class;
        $this->currentPassword = $currentPassword;
    }

    public function isCurrentPassword()
    {
        return !empty($this->currentPassword);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class,
            'csrf_protection' => false,
            'validation_groups' => array(
                'Default',
                'Profile'
            )
        ));
    }

    public function getName()
    {
        return 'food_user_change_password';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $attributes = array('rel' => 'tooltip',
                            'data-toggle' => 'tooltip',
                            'data-placement' => 'top',
                            'data-trigger' => 'focus');

        $builder
            ->add('current_password', 'password', array(
                  'required' => false,
                  'label' => 'form.current_password',
                  'error_bubbling' => false,
                  'translation_domain' => 'FOSUserBundle',
                  'mapped' => false,
                  'attr' => array_merge(['autocomplete' => 'off'], $attributes),
                  'constraints' => $this->isCurrentPassword() ?
                                   new UserPassword(['message' => 'fos_user.form.invalid_current_password']) :
                                   null))
            ->add('plainPassword', 'repeated', array(
                  'type' => 'password',
                  'error_bubbling' => false,
                  'options' => array('translation_domain' => 'FOSUserBundle'),
                  'first_options' => array('label' => 'form.new_password', 'attr' => array_merge(['autocomplete' => 'off'], $attributes)),
                  'second_options' => array('label' => 'form.new_password_confirmation', 'attr' => array_merge(['autocomplete' => 'off'], $attributes)),
                  'invalid_message' => 'fos_user.password.mismatch',
                  'required' => false))
        ;
    }
}
