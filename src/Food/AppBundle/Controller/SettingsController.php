<?php

namespace Food\AppBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Form\FormBuilderInterface;

class SettingsController extends CRUDController
{

    private $keywordMapCollection = [
        'site_logo_url',
        'change_phone_position',
        'page_banned',
        'page_sitemap',
        'page_email_banned',
        'page_help',
        'page_best_offer',
        'page_blog',
        'page_restaurant_list',
        'use_admin_fee_globally',
        'admin_fee_size',
        'page_b2b_rules',
        'page_privacy',
        'disabled_preorder_days',
        'zaval_on',
        'pedestrian_delivery_time',
        'showMobilePopup',
        'enable_free_delivery_for_big_basket',
        'free_delivery_price',
        'beta_code_on',
        'possible_delivery_delay',
        'late_time_to_delivery',
        'sf_next_number',
        'header_scripts',
        'footer_scripts',
        'fb_meta_tag',
        'extra_group',
        'free_delivery_discount_code_generation_enable',
        'free_delivery_discount_code_generation_after_completed_orders',
        'placepoint_prepare_times',
        'facebook_pixel_code',
        'reviews_enabled',
        'optin_code',
        'game_revive_zone_id',
        'footer_social',
        'pedestrian_filter_show',
        'placepoint_prepare_times_pedestrian'
    ];

    public function listAction( )
    {
        $request = $this->get('request');

        $paramService = $this->get('food.app.utils.misc');
        $session = $this->get('session');
        $data = [];
        foreach ($this->keywordMapCollection as $keyword) {
            $data[$keyword] = $paramService->getParam($keyword, NULL);
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

                if($data[$keyword] != $value) {
                    $paramService->setParam($keyword, $value);
                }
            }
            $session->getFlashBag()->add('success', 'Settings was saved successfully');
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
        $pageCollection[] = ' - ';
        foreach ($static->findAll() as $page) {
            $pageCollection[$page->getId()] = $page->getTitle();
        }

        $form->add('site_logo_url', 'text');

        if($this->container->getParameter('country') != 'EE') {
            $form->add('change_phone_position', 'boolean', [
                'label' => 'Move phone to footer',
            ]);
        }

        $form->add('page_banned', 'choice', [
            'label' => 'Banned page',
            'choices' =>  $pageCollection,
            'attr' => [
                'group' => 'Info pages',
                'style' => 'margin-bottom:10px',
            ]
        ]);

        $form->add('page_email_banned', 'choice', [
            'label' => 'Banned email page',
            'choices' => $pageCollection
        ]);

        $form->add('page_sitemap', 'choice', [
            'choices' => $pageCollection
        ]);

        $form->add('page_help', 'choice', [
            'label' => 'Help page',
            'choices' =>  $pageCollection
        ]);

        $form->add('page_best_offer', 'choice', [
            'label' => 'Best offer page',
            'choices' =>  $pageCollection
        ]);
        $form->add('page_b2b_rules', 'choice', [
            'label' => 'B2B rules page',
            'choices' =>  $pageCollection
        ]);

        $form->add('page_privacy', 'choice', [
            'label' => 'Privacy page',
            'choices' =>  $pageCollection
        ]);

        $form->add('page_blog', 'choice', [
            'label' => 'Blog page',
            'choices' =>  $pageCollection
        ]);


        $form->add('page_restaurant_list', 'choice', [
            'label' => 'Restaurant list',
            'choices' =>  $pageCollection
        ]);

        $form->add('use_admin_fee_globally', 'boolean', [
            'label' => 'Use globally',
            'attr' => ['group' => 'Admin fee']
        ]);

        $form->add('admin_fee_size', 'number', [
            'label' => 'Fee size',
        ]);


        $form->add('disabled_preorder_days', 'text', [
            'attr' => ['group' => 'Order']
        ]);

        $form->add('zaval_on', 'boolean', [
            'label' => 'Rush hours activated',
        ]);

        $form->add('pedestrian_filter_show', 'boolean', [
            'label' => 'Enable pedestrian filter',
        ]);

        $form->add('pedestrian_delivery_time', 'number', [
            'label' => 'Pedestrian delivery time'
        ]);

        $form->add('placepoint_prepare_times_pedestrian', 'text');

        $form->add('enable_free_delivery_for_big_basket', 'boolean');

        $form->add('free_delivery_price', 'number');

        $form->add('possible_delivery_delay', 'number');

        $form->add('late_time_to_delivery', 'number');

        $form->add('sf_next_number', 'number', [
            'label' => 'SF next number'
        ]);

        $form->add('showMobilePopup', 'boolean', [
            'label' => 'Show mobile popup',
        ]);

        $form->add('beta_code_on', 'choice', [
            'label' => 'Beta code',
            'choices' => ['on' => 'On', 'off' => 'Off'],
        ]);

        $form->add('free_delivery_discount_code_generation_enable', 'boolean');
        $form->add('extra_group', 'boolean');


        $form->add('placepoint_prepare_times');
        $form->add('free_delivery_discount_code_generation_after_completed_orders');

        $form->add('reviews_enabled', 'boolean');

        $form->add('header_scripts', 'textarea', [
            'label' => 'Header Scripts',
            'required' => false,
            'attr' => ['style' => 'width:100%;', 'group' => 'Content', 'rows' => 20]
        ]);

        $form->add('footer_scripts', 'textarea', [
            'label' => 'Footer Scripts',
            'required' => false,
            'attr' => ['style' => 'width:100%;', 'rows' => 20]
        ]);

        $form->add('fb_meta_tag', 'textarea', [
            'label' => 'Facebook meta tag',
            'required' => false,
            'attr' => ['style' => 'width:100%;', 'rows' => 20]
        ]);

        $form->add('optin_code', 'textarea', [
            'label' => 'Optin Code',
            'required' => false,
            'attr' => ['style' => 'width:100%;',  'rows' => 20]
        ]);

        $form->add('footer_social', 'textarea', [
            'label' => 'Footer Social',
            'required' => false,
            'attr' => ['style' => 'width:100%;', 'rows' => 20]
        ]);

        foreach ($this->keywordMapCollection as $keyword) {
            if(!$form->has($keyword)) {
                $form->add($keyword, 'text', ['required' => false]);
            }
        }
        
        $form->add('submit', 'submit', ['label' => 'Update', 'attr' => ['class' => 'btn btn-primary']]);

    }

}