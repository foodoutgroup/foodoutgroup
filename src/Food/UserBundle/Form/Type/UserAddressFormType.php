<?php

namespace Food\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'city_id',
                'entity',
                [
                    'class' => 'FoodAppBundle:City',
                    'label' => 'form.city',
                    'translation_domain' => 'FOSUserBundle',
                    'required' => true,
                    'empty_value' => '-',
                    'attr' => ['class' => 'custom-select'],
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('u')
                            ->where('u.active = :active')
                            ->setParameter('active',1)
                            ->orderBy('u.title', 'ASC');
                    },
                ]
            )
            ->add('address', null, ['label' => 'form.address', 'translation_domain' => 'FOSUserBundle', 'required' => false]);
    }

}
