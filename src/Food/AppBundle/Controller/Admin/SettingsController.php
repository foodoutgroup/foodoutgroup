<?php
/**
 * Created by PhpStorm.
 * User: aleksas
 * Date: 17.3.27
 * Time: 11.58
 */

namespace Food\AppBundle\Controller\Admin;

use Sonata\AdminBundle\Controller\CoreController;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;

class SettingsController extends CoreController
{

    private $keywordMapCollection = [
        'page_banned',
        'page_help',
    ];

    public function indexAction(Request $request)
    {
        $paramService = $this->get('food.app.utils.misc');

        $data = [];

        foreach ($this->keywordMapCollection as $keyword) {
            $data[$keyword] = $paramService->getParam($keyword, true);
        }

        $form = $this->get('form.factory')->createNamedBuilder('settings', 'form', $data);

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

            return $this->redirect($this->generateUrl('food_admin_settings'));
        }

        return $this->render('@FoodApp/Admin/Settings/index.html.twig', array(
            'form'          => $form->createView(),
            'base_template' => $this->getBaseTemplate(),
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
            'choices' => $pageCollection
        ]);

        $form->add('page_help', 'choice', [
            'label' => 'Help page',
            'choices' => $pageCollection
        ]);



        $form->add('submit', 'submit', ['label' => 'Update', 'attr' => ['class' => 'btn btn-primary']]);
//        $form->add('sex', 'sonata_block_service_choice');

        foreach ($this->keywordMapCollection as $keyword) {
            if(!$form->has($keyword)) {
                throw new \Exception('Field '.$keyword.' not found at settings list');
            }
        }

    }


}