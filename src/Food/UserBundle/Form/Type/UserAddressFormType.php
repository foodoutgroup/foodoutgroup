<?php

namespace Food\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserAddressFormType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Food\UserBundle\Entity\UserAddress',
            'csrf_protection' => false,
        ));
    }

    public function getName()
    {
        return 'food_user_address';
    }

    protected function buildUserForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ->add('city', 'choice', ['choices' => ['Vilnius', 'Kaunas'], 'required' => true])
            // ->add('address', null, ['required' => true])
            ->add('city')
            ->add('address')
        ;
    }
}
