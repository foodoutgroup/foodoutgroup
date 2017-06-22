<?php
// src/AppBundle/Form/Type/CityType.php
namespace Food\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BooleanType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {

        $resolver->setDefaults([
            'choices' => ['No', 'Yes']
        ]);

        parent::setDefaultOptions($resolver);

    }

    public function getParent()
    {
        return 'choice';
    }

    public function getName()
    {
        return 'boolean';
    }


}