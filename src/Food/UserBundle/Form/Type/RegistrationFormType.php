<?php

namespace Food\UserBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RegistrationFormType extends BaseType
{
    private $class;

    /**
     * @param string $class The User class name
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    public function getName()
    {
        return 'food_user_registration';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class,
            'csrf_protection' => false,
            'validation_groups' => array(
                'Registration'
            )
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $attributes = array('rel' => 'tooltip',
                            'data-toggle' => 'tooltip',
                            'data-placement' => 'right',
                            'data-trigger' => 'focus');

        $builder
            ->add('firstname',
                  null,
                  array('error_bubbling' => false,
                        'required' => true,
                        'label' => 'form.firstname',
                        'translation_domain' => 'FOSUserBundle',
                        'attr' => $attributes))
            ->add('lastname',
                  null,
                  array('error_bubbling' => false,
                        'required' => false,
                        'label' => 'form.lastname',
                        'translation_domain' => 'FOSUserBundle',
                        'attr' => $attributes))
            ->add('email',
                  'email',
                  array('error_bubbling' => false,
                        'label' => 'form.email',
                        'translation_domain' => 'FOSUserBundle',
                        'attr' => $attributes))
            ->add('username',
                  null,
                  array('error_bubbling' => false,
                        'label' => 'form.username',
                        'translation_domain' => 'FOSUserBundle',
                        'attr' => $attributes))
            ->add('isBussinesClient',
                'checkbox',
                array(
                    'error_bubbling' => false
                )
            )
            ->add('businessAgreement',
                'checkbox',
                array(
                    'label' => 'form.businessAgreement',
                    'error_bubbling' => false,
                    'mapped' => false,
                    'attr' => $attributes
                )
            )
            ->add('companyName',
                'text',
                array(
                    'error_bubbling' => false,
                    'label' => 'form.company_name',
                    'attr' => $attributes
                )
            )
            ->add('companyCode',
                'text',
                array(
                    'error_bubbling' => false,
                    'label' => 'form.company_code',
                    'attr' => $attributes
                )
            )
            ->add('vatCode',
                'text',
                array(
                    'error_bubbling' => false,
                    'label' => 'form.vat_code'
                )
            )
            ->add('companyAddress',
                'text',
                array(
                    'error_bubbling' => false,
                    'label' => 'form.company_address',
                    'attr' => $attributes
                )
            )
            ->add('checkingAccount',
                'text',
                array(
                    'error_bubbling' => false,
                    'label' => 'form.checking_account',
                    'attr' => $attributes
                )
            )
            ->add('workersCount',
                'text',
                array(
                    'error_bubbling' => false,
                    'label' => 'form.workers_count'
                )
            )
            ->add('plainPassword',
                  'repeated',
                  array('error_bubbling' => false,
                        'type' => 'password',
                        'options' => array('translation_domain' => 'FOSUserBundle'),
                        'first_options' => array('label' => 'form.password', 'attr' => $attributes),
                        'second_options' => array('label' => 'form.password_confirmation', 'attr' => $attributes),
                        'invalid_message' => 'fos_user.password.mismatch'))
            ->remove('username')
            ->remove('phone')
        ;

        $businessValidator = function (FormEvent $event) {
            $form = $event->getForm();
            if ($form->get('isBussinesClient')->getData()) {
                $companyNameField = $form->get('companyName')->getData();
                if (empty($companyNameField) || mb_strlen($companyNameField, 'UTF-8') < 3) {
                    $form['companyName']->addError(new FormError("errors.companyName"));
                }
                $companyCodeField = $form->get('companyCode')->getData();
                if (empty($companyCodeField) || mb_strlen($companyCodeField, 'UTF-8') < 3) {
                    $form['companyCode']->addError(new FormError("errors.companyCode"));
                }
                $companyAddressField = $form->get('companyAddress')->getData();
                if (empty($companyAddressField) || mb_strlen($companyAddressField, 'UTF-8') < 3) {
                    $form['companyAddress']->addError(new FormError("errors.companyAddress"));
                }
                $checkingAccountField = $form->get('checkingAccount')->getData();
                if (empty($checkingAccountField) || mb_strlen($checkingAccountField, 'UTF-8') < 3) {
                    $form['checkingAccount']->addError(new FormError("errors.checkingAccount"));
                }
                $businessAgreementField = $form->get('businessAgreement')->getData();
                if (empty($businessAgreementField)) {
                    $form['businessAgreement']->addError(new FormError("errors.businessAgreement"));
                }
            }
        };

        $builder->addEventListener(FormEvents::POST_SUBMIT, $businessValidator);

    }
}
