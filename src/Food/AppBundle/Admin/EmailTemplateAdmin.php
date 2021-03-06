<?php namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\OrderService;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class EmailTemplateAdmin extends FoodAdmin
{
    function configureListFields(ListMapper $list)
    {
        $list->add('templateId', null, ['label' => 'admin.email.text'])
            ->add('name', 'text', ['label' => 'admin.email.name'])
            ->add('status', 'text', ['label' => 'admin.email.status'])
            ->add('preorder', 'boolean', ['label' => 'admin.email.preorder'])
            ->add('type', 'text', ['label' => 'admin.email.type'])
            ->add('source', 'text', ['label' => 'admin.email.source'])
            ->add('active', 'boolean', ['label' => 'admin.email.active', 'editable' => true])
            ->add('selfDelivery', 'boolean', ['label' => 'admin.email.self_delivery', 'editable' => true])
            ->add('_action', 'actions', ['actions' => ['edit' => [], 'delete' => [],], 'label' => 'admin.actions']);
    }

    function configureFormFields(FormMapper $form)
    {

        $sourceCollection = ['All' => $this->trans('admin.email.use_for_all')];

        foreach (Order::$sourceCollection as $source) {
            $sourceCollection[$source] = $source;
        }

        $typeCollection = ['pickup' => 'Pickup', 'deliver' => 'Simple Order'];
        $orderStatusCollection = [];
        foreach (OrderService::getOrderStatuses() as $os) {
            $orderStatusCollection[$os] = $os;
        }

        $templateHelp = $this->getContainer()->get('templating')->render('@FoodApp/Admin/Custom/email_template_help.html.twig', []);

        $form->add('translations', 'a2lix_translations_gedmo', [
            'translatable_class' => 'Food\AppBundle\Entity\EmailTemplate',
            'cascade_validation' => true,
            'fields' => [
                'templateId' => ['required' => true, 'label' => 'Template ID', 'attr' => ['help' => $templateHelp]]
            ]
        ])
            ->add('status', 'choice', ['label' => 'admin.email.status', 'choices' => $orderStatusCollection])
            ->add('name', 'text', ['label' => 'admin.email.name'])
            ->add('preorder', 'boolean', ['label' => 'admin.email.preorder'])
            ->add('type', 'boolean', ['label' => 'admin.email.type', 'choices' => $typeCollection])
            ->add('source', 'choice', ['label' => 'admin.email.source', 'choices' => $sourceCollection])
            ->add('active', 'boolean')
            ->add('selfDelivery', 'boolean', ['label' => 'admin.email.self_delivery']);

    }

}