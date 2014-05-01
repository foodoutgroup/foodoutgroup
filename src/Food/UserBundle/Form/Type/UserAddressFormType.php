<?php

namespace Food\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserAddressFormType extends AbstractType
{
    private $availableCities = array();

    function __construct($cities)
    {
        $this->setAvailableCities($cities);

        parent::_construct();
    }

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
                'city',
                'choice',
                [
                    'label' => 'form.city',
                    'translation_domain' => 'FOSUserBundle',
                    'choices' => $this->availableCities,
                    'required' => true,
                    'empty_value' => '-',
                    'attr' => ['class' => 'custom-select']
                ]
            )
            ->add('address', null, ['label' => 'form.address', 'translation_domain' => 'FOSUserBundle', 'required' => false])
        ;
    }

    /**
     * @param array $cities
     * @return $this
     */
    public function setAvailableCities($cities)
    {
        $this->availableCities = $cities;

        return $this;
    }
}
