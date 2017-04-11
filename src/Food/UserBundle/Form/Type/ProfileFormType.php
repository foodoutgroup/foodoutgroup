<?php

namespace Food\UserBundle\Form\Type;

use Food\UserBundle\Form\Type\UserAddressFormType;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;


class ProfileFormType extends BaseType
{
    private $class;
    private $phoneCodes;
    private $country;
    private $user;

    /**
     * @param string $class The User class name
     */
    public function __construct($class, $phoneCodes, $country, $user)
    {
        $this->class = $class;
        $this->phoneCodes = $phoneCodes;
        $this->country = $country;
        $this->user = $user;
    }

    public function getName()
    {
        return 'food_user_profile';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class,
            'csrf_protection' => false,
            'validation_groups' => array(
                'FoodProfile'
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
                array('required' => true,
                    'error_bubbling' => false,
                    'label' => 'form.firstname',
                    'translation_domain' => 'FOSUserBundle',
                    'attr' => $attributes))
            ->add('lastname',
                null,
                array('required' => false,
                    'error_bubbling' => false,
                    'label' => 'form.lastname',
                    'translation_domain' => 'FOSUserBundle',
                    'attr' => $attributes))
            ->add('email',
                'email',
                array('required' => true,
                    'error_bubbling' => false,
                    'label' => 'form.email',
                    'translation_domain' => 'FOSUserBundle',
                    'disabled' => true,
                    'attr' => $attributes))
            ->add('phone',
                null,
                array('required' => true,
                    'error_bubbling' => false,
                    'label' => 'form.phone',
                    'translation_domain' => 'FOSUserBundle',
                    'data' => $this->phoneCodes->changePhoneFormat($this->user),
                    'attr' => array_merge(array('placeholder' => '3706xxxxxxx'), $attributes)))// FIXME:

            ->add('countryCode', 'choice', array(
                'choices' => $this->phoneCodes->getActiveDropdown(),
                'data' => $this->getCountryCode(),
                'attr' => ['class' => 'custom-select phone-code-profile']
            ))
            ->add('regeneratePassword',
                null,
                array('required' => false,
                    'error_bubbling' => false,
                    'label' => 'form.regenerate_password',
                    'translation_domain' => 'FOSUserBundle',
                    'attr' => $attributes));
    }

    public function getPhoneArray()
    {

        return $this->em->get('food.phones_code_service')->getDropdownCodes();
    }

    public function getCountryCode()
    {

        $userCode = $this->user->getCountryCode();

        if (empty($userCode)) {
            $userCode = $this->country;
        }

        return $userCode;
    }

}
