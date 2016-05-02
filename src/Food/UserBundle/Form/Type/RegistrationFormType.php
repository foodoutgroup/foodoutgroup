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
            ->add('isBussinesClient',
                'checkbox',
                array(
                    'error_bubbling' => false
                )
            )
            ->add('companyName',
                'text',
                array(
                    'error_bubbling' => false,
                    'label' => 'form.company_name'
                )
            )
            ->add('companyCode',
                'text',
                array(
                    'error_bubbling' => false,
                    'label' => 'form.company_code'
                )
            )
            ->add('vatCode',
                'text',
                array(
                    'error_bubbling' => false,
                    'label' => 'form.vat_code'
                )
            )
            ->add('companyAddress',
                'text',
                array(
                    'error_bubbling' => false,
                    'label' => 'form.company_address'
                )
            )
            ->add('companyAccount',
                'text',
                array(
                    'error_bubbling' => false,
                    'label' => 'form.company_account'
                )
            )
            ->add('companyWorkers',
                'text',
                array(
                    'error_bubbling' => false,
                    'label' => 'form.company_workers'
                )
            )
            ->add('plainPassword',
                  'repeated',
                  array('error_bubbling' => false,
                        'type' => 'password',
                        'options' => array('translation_domain' => 'FOSUserBundle'),
                        'first_options' => array('label' => 'form.password', 'attr' => $attributes),
                        'second_options' => array('label' => 'form.password_confirmation', 'attr' => $attributes),
                        'invalid_message' => 'fos_user.password.mismatch'))
            ->remove('username')
            ->remove('phone')
        ;
    }
}
