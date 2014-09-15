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
     * 'stamp' => string (e.g.: '8856')
     * 'amount' => string (e.g.: '9999')
     * 'ref' => string (e.g.: '8856')
     * 'msg' => string
     * 'return_url' => string
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('VK_SERVICE', 'hidden', ['data' => '1001'])
            ->add('VK_VERSION', 'hidden', ['data' => '008'])
            ->add('VK_SND_ID', 'hidden', ['data' => $this->options['snd_id']])
            ->add('VK_STAMP', 'hidden', ['data' => $this->options['stamp']])
            ->add('VK_AMOUNT', 'hidden', ['data' => $this->options['amount']])
            ->add('VK_CURR', 'hidden', ['data' => $this->options['curr']])
            ->add('VK_ACC', 'hidden', ['data' => $this->options['acc']])
            ->add('VK_NAME', 'hidden', ['data' => $this->options['name']])
            ->add('VK_REF', 'hidden', ['data' => $this->options['ref']])
            ->add('VK_MSG', 'hidden', ['data' => $this->options['msg']])
            ->add('VK_MAC', 'hidden')
            ->add('VK_RETURN', 'hidden', ['data' => $this->options['return_url']])
            ->add('VK_LANG', 'hidden', ['data' => $this->options['lang']])
            ->add('VK_CHARSET', 'hidden', ['data' => 'utf-8']);
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
