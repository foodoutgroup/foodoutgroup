<?php

namespace Food\OrderBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

class SebBanklinkType extends AbstractType
{
    protected $options;

    /**
     * 'snd_id' => string (e.g.: EM0373)
     * 'curr' => string (e.g.: LTL)
     * 'acc' => string (e.g.: LT737044060007823386)
     * 'name' => string (e.g.: 'UAB SAU.LT')
     * 'lang' => string (e.g.: 'LIT')
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('VK_SERVICE', 'text', ['data' => '1001'])
            ->add('VK_VERSION', 'text', ['data' => '008'])
            ->add('VK_SND_ID', 'text', ['data' => $this->options['snd_id']])
            ->add('VK_STAMP', 'text')
            ->add('VK_AMOUNT', 'text')
            ->add('VK_CURR', 'text', ['data' => $this->options['curr']])
            ->add('VK_ACC', 'text', ['data' => $this->options['acc']])
            ->add('VK_NAME', 'text', ['data' => $this->options['name']])
            ->add('VK_REF', 'text')
            ->add('VK_MSG', 'text')
            ->add('VK_MAC', 'text')
            ->add('VK_RETURN', 'text')
            ->add('VK_LANG', 'text', ['data' => $this->options['lang']])
            ->add('VK_CHARSET', 'text', ['data' => 'utf-8']);
    }

    public function getName()
    {
        return '';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
        ));
    }
}
