<?php

namespace Food\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Food\UserBundle\Form\Type\ProfileFormType;
use Food\UserBundle\Form\Type\UserAddressFormType;
use Food\UserBundle\Form\Type\ChangePasswordFormType;

class ProfileMegaFormType extends AbstractType
{
    private $profile;
    private $address;
    private $change_password;

    public function __construct(
        ProfileFormType $profile,
        UserAddressFormType $address,
        ChangePasswordFormType $change_password
    )
    {
        $this->profile = $profile;
        $this->address = $address;
        $this->change_password = $change_password;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'validation_groups' => array(
                'Default',
                'FoodProfile'
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
            ->add('profile', $this->profile, array('required' => true))
            ->add('address', $this->address, array('required' => false))
            ->add('change_password', $this->change_password, array('required' => false))
        ;
    }
}
