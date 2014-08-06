<?php

namespace Food\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RegistrationFormType extends BaseType
{
    private $class;

    /**
     * @param string $class The User class name
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    public function getName()
    {
        return 'food_user_registration';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class,
            'csrf_protection' => false,
            'validation_groups' => array(
                'Registration'
            )
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $attributes = array('rel' => 'tooltip',
                            'data-toggle' => 'tooltip',
                            'data-placement' => 'right',
                            'data-trigger' => 'focus');

        $builder
            ->add('firstname',
                  null,
                  array('error_bubbling' => false,
                        'required' => true,
                        'label' => 'form.firstname',
                        'translation_domain' => 'FOSUserBundle',
                        'attr' => $attributes))
            ->add('lastname',
                  null,
                  array('error_bubbling' => false,
                        'required' => false,
                        'label' => 'form.lastname',
                        'translation_domain' => 'FOSUserBundle',
                        'attr' => $attributes))
            ->add('email',
                  'email',
                  array('error_bubbling' => false,
                        'label' => 'form.email',
                        'translation_domain' => 'FOSUserBundle',
                        'attr' => $attributes))
            ->add('username',
                  null,
                  array('error_bubbling' => false,
                        'label' => 'form.username',
                        'translation_domain' => 'FOSUserBundle',
                        'attr' => $attributes))
            ->add('plainPassword',
                  'repeated',
                  array('error_bubbling' => false,
                        'type' => 'password',
                        'options' => array('translation_domain' => 'FOSUserBundle'),
                        'first_options' => array('label' => 'form.password'),
                        'second_options' => array('label' => 'form.password_confirmation'),
                        'invalid_message' => 'fos_user.password.mismatch',
                        'first_options' => ['attr' => $attributes],
                        'second_options' => ['attr' => $attributes]))
            ->remove('username')
            ->remove('phone')
        ;
    }
}
