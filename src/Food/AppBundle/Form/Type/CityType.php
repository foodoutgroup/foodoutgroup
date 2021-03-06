<?php
// src/AppBundle/Form/Type/CityType.php
namespace Food\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CityType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {

        $resolver->setDefaults(array(
            'class'=> 'FoodAppBundle:City'
        ));

        parent::setDefaultOptions($resolver);

    }

    public function getParent()
    {
        return 'entity';
    }

    public function getName()
    {
        return 'city';
    }


}