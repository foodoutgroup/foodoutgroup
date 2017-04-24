<?php

namespace Food\AppBundle\Controller\Admin;

use Sonata\AdminBundle\Controller\CoreController;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

class SettingsController extends CoreController
{

    private $keywordMapCollection = [
        'page_banned',
        'page_email_banned',
        'page_help',
        'page_best_offer',
        'page_blog',
        'use_admin_fee_globally',
        'admin_fee_size',
        'page_b2b_rules',
        'blog_link_active',
        'disabled_preorder_days',
        'zaval_on',
        'showMobilePopup',
        'enable_free_delivery_for_big_basket',
        'free_delivery_price',
        'beta_code_on',
        'delfiBanner',
        'delfiJs',
        'possible_delivery_delay',
        'late_time_to_delivery',
        'sf_next_number',
        'footer_scripts',
        'extra_group',
        'free_delivery_discount_code_generation_enable',
        'free_delivery_discount_code_generation_after_completed_orders',
        'placepoint_prepare_times',
    ];

    public function indexAction(Request $request)
    {
        $paramService = $this->get('food.app.utils.misc');
        $session = $this->get('session');
        $data = [];
        foreach ($this->keywordMapCollection as $keyword) {
            $data[$keyword] = $paramService->getParam($keyword, true);
        }

        $form = $this->get('form.factory')->createNamedBuilder('settings', 'form', $data, ['csrf_protection' => false]);

        $this->formFields($form);
        $form = $form->getForm();
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            foreach($form->getData() as $keyword => $value) {

                if(is_object($value) && method_exists($value, 'getId')) {
                    $value = $value->getId();
                }

                $paramService->setParam($keyword, $value);

            }
            $session->getFlashBag()->add('success', 'Settings was saved successfully');
            return $this->redirect($this->generateUrl('food_admin_settings'));
        }

        return $this->render('@FoodApp/Admin/Settings/index.html.twig', array(
            'form'          => $form->createView(),
            'base_template' => $this->getBaseTemplate(),
            'flash_messages' => $session->getFlashBag(),
            'admin_pool'    => $this->container->get('sonata.admin.pool'),
            'blocks'        => $this->container->getParameter('sonata.admin.configuration.dashboard_blocks')
        ));
    }

    private function formFields(FormBuilderInterface &$form)
    {


        $static = $this->getDoctrine()->getRepository('FoodAppBundle:StaticContent');
        $pageCollection = [];

        foreach ($static->findAll() as $page) {
            $pageCollection[$page->getId()] = $page->getTitle();
        }

        $form->add('page_banned', 'choice', [
            'label' => 'Banned page',
            'choices' => $pageCollection,
            'attr' => [
                'group' => 'Info pages',
                'style' => 'margin-bottom:10px',
            ]
        ]);

        $form->add('page_email_banned', 'choice', [
            'label' => 'Banned email page',
            'choices' => $pageCollection
        ]);

        $form->add('page_help', 'choice', [
            'label' => 'Help page',
            'choices' => $pageCollection
        ]);

        $form->add('page_best_offer', 'choice', [
            'label' => 'Best offer page',
            'choices' => $pageCollection
        ]);
        $form->add('page_b2b_rules', 'choice', [
            'label' => 'B2B rules page',
            'choices' => $pageCollection
        ]);

        $form->add('page_blog', 'choice', [
            'label' => 'Blog page',
            'choices' => array_merge(['0' => ''], $pageCollection),
        ]);

        $form->add('use_admin_fee_globally', 'boolean', [
            'label' => 'Use globally',
            'attr' => ['group' => 'Admin fee']
        ]);

        $form->add('admin_fee_size', 'number', [
            'label' => 'Fee size',
        ]);

        $form->add('blog_link_active', 'boolean', [
            'label' => 'Blog activated',
            'attr' => ['group' => 'Blog']
        ]);



        $form->add('disabled_preorder_days', 'text', [
            'attr' => ['group' => 'Order']
        ]);

        $form->add('zaval_on', 'boolean', [
            'label' => 'Rush hours activated',
        ]);

        $form->add('enable_free_delivery_for_big_basket', 'boolean');

        $form->add('free_delivery_price', 'number');

        $form->add('possible_delivery_delay', 'number');

        $form->add('late_time_to_delivery', 'number');

        $form->add('sf_next_number', 'number', [
            'label' => 'SF next number'
        ]);

        $form->add('delfiBanner', 'textarea', [
            'label' => 'Delfi banner',
            'attr' => ['group' => 'Integrations','style' => 'width:100%;', 'rows' => 10]
        ]);

        $form->add('delfiJs', 'textarea', [
            'label' => 'Delfi JS',
            'attr' => ['style' => 'width:100%;', 'rows' => 4]
        ]);


        $form->add('showMobilePopup', 'boolean', [
            'label' => 'Show mobile popup',
        ]);

        $form->add('beta_code_on', 'choice', [
            'label' => 'Beta code',
            'choices' => ['on' => 'On', 'off' => 'Off'],
        ]);

        $form->add('footer_scripts', 'textarea', [
            'label' => 'Footer Scripts',
            'attr' => ['style' => 'width:100%;', 'rows' => 20]
        ]);

        $form->add('extra_group', 'boolean');

        $form->add('free_delivery_discount_code_generation_enable', 'boolean');

        foreach ($this->keywordMapCollection as $keyword) {
            if(!$form->has($keyword)) {
                $form->add($keyword, 'text', ['required' => false]);
            }
        }

        $form->add('submit', 'submit', ['label' => 'Update', 'attr' => ['class' => 'btn btn-primary']]);
//        $form->add('sex', 'sonata_block_service_choice');

    }

}