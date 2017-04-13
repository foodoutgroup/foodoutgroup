<?php
// src/AppBundle/Form/Type/CityType.php
namespace Food\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BestOfferType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {

        $resolver->setDefaults(array(
            'class'=> 'FoodAppBundle:City',
            'multiple'  => true
        ));
        parent::setDefaultOptions($resolver);

    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }

    public function getParent()
    {
        return 'entity';
    }

    public function getName()
    {
        return 'offer_city';
    }


}