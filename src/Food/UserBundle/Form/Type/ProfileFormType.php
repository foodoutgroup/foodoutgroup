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

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class,
            'intention'  => 'profile',
            'csrf_protection' => false,
            'validation_groups' => array(
                'Default',
                'Profile'
            )
        ));
    }

    public function getName()
    {
        return 'food_user_profile';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', null, array('required' => true, 'error_bubbling' => false, 'label' => 'form.firstname', 'translation_domain' => 'FOSUserBundle'))
            ->add('lastname', null, array('required' => false, 'error_bubbling' => false, 'label' => 'form.lastname', 'translation_domain' => 'FOSUserBundle'))
            ->add('email', 'email', array('required' => true, 'error_bubbling' => false, 'label' => 'form.email', 'translation_domain' => 'FOSUserBundle', 'disabled' => true))
            ->add('phone', null, array('required' => true, 'error_bubbling' => false, 'label' => 'form.phone', 'translation_domain' => 'FOSUserBundle', 'attr' => array('placeholder' => '3706xxxxxxx')))
        ;
    }
}
