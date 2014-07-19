<?php

namespace Food\UserBundle\Form\Type;

use Food\UserBundle\Form\Type\UserAddressFormType;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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

    public function getName()
    {
        return 'food_user_profile';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class,
            'csrf_protection' => false,
            'validation_groups' => array(
                'Profile'
            )
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $attributes = array('rel' => 'tooltip',
                            'data-toggle' => 'tooltip',
                            'data-placement' => 'top');

        $builder
            ->add('firstname',
                  null,
                  array('required' => true,
                        'error_bubbling' => false,
                        'label' => 'form.firstname',
                        'translation_domain' => 'FOSUserBundle',
                        'attr' => $attributes))
            ->add('lastname',
                  null,
                  array('required' => false,
                        'error_bubbling' => false,
                        'label' => 'form.lastname',
                        'translation_domain' => 'FOSUserBundle',
                        'attr' => $attributes))
            ->add('email',
                  'email',
                  array('required' => true,
                        'error_bubbling' => false,
                        'label' => 'form.email',
                        'translation_domain' => 'FOSUserBundle',
                        'disabled' => true,
                        'attr' => $attributes))
            ->add('phone',
                  null,
                  array('required' => true,
                        'error_bubbling' => false,
                        'label' => 'form.phone',
                        'translation_domain' => 'FOSUserBundle',
                        'attr' => array_merge(array('placeholder' => '3706xxxxxxx'), $attributes)))
        ;
    }
}
