<?php namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\OrderService;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class SmsTemplateAdmin extends FoodAdmin
{
    function configureListFields(ListMapper $list)
    {
        $list->add('text', null, ['label' => 'admin.sms.text'])
            ->add('status', 'text', ['label' => 'admin.sms.status'])
            ->add('preorder', 'boolean', ['label' => 'admin.sms.preorder'])
            ->add('type', 'text', ['label' => 'admin.sms.type'])
            ->add('source', 'text', ['label' => 'admin.sms.source'])
            ->add('active', 'boolean', ['label' => 'admin.sms.active', 'editable' => true])
            ->add('_action', 'actions', ['actions' => ['edit' => [], 'delete' => [],], 'label' => 'admin.actions']);
    }

    function configureFormFields(FormMapper $form)
    {

        $sourceCollection = [];
        foreach (Order::$sourceCollection as $source) {
            $sourceCollection[$source] = $source;
        }

        $typeCollection = ['pickup' => 'Pickup', 'deliver' => 'Simple Order'];
        $orderStatusCollection = [];
        foreach (OrderService::getOrderStatuses() as $os) {
            $orderStatusCollection[$os] = $os;
        }

        $templateHelp = $this->getContainer()->get('templating')->render('@FoodApp/Admin/Custom/sms_template_help.html.twig', []);


        $form->add('translations', 'a2lix_translations_gedmo', [
                'translatable_class' => 'Food\AppBundle\Entity\SmsTemplate',
                'cascade_validation' => true,
            'fields' => [
                'text' => ['required' => true, 'attr' => ['help' => $templateHelp]]
            ]
        ])
        ->add('status', 'choice', ['label' => 'admin.sms.status', 'choices' => $orderStatusCollection])
        ->add('preorder', 'boolean', ['label' => 'admin.sms.preorder'])
        ->add('type', 'boolean', ['label' => 'admin.sms.type', 'choices' => $typeCollection])
        ->add('source', 'choice', ['label' => 'admin.sms.source', 'choices' => $sourceCollection])
        ->add('active', 'boolean');
    }

}