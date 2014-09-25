<?php

namespace Food\OrderBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

class NordeaBanklinkType extends AbstractType
{
    protected $options;

    /**
     * 'rcv_id' => string (e.g.: 1234567)
     * 'amount' => string (e.g.: 99.99)
     * 'ref' => string (e.g.: 8856)
     * 'msg' => string
     * 'return_url' => string
     * 'cancel_url' => string
     * 'reject_url' => string
     * 'mac' => string (e.g.: abcdefghijklmon123)
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('VERSION', 'hidden', ['data' => '0003'])
            ->add('STAMP', 'hidden', ['data' => $this->options['stamp']])
            ->add('RCV_ID', 'hidden', ['data' => $this->options['rcv_id']])
            ->add('RCV_ACCOUNT', 'hidden')
            ->add('LANGUAGE', 'hidden', ['data' => '7'])
            ->add('AMOUNT', 'hidden', ['data' => $this->options['amount']])
            ->add('REF', 'hidden', ['data' => $this->options['ref']])
            ->add('DATE', 'hidden', ['data' => 'EXPRESS'])
            ->add('MSG', 'hidden', ['data' => $this->options['msg']])
            ->add('RETURN', 'hidden', ['data' => $this->options['return_url']])
            ->add('CANCEL', 'hidden', ['data' => $this->options['cancel_url']])
            ->add('REJECT', 'hidden', ['data' => $this->options['reject_url']])
            ->add('MAC', 'hidden')
            ->add('CONFIRM', 'hidden', ['data' => 'YES'])
            ->add('KEYVERS', 'hidden', ['data' => '0001'])
            ->add('CUR', 'hidden', ['data' => 'LTL'])
        ;
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
