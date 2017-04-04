<?php
namespace Food\AppBundle\Controller\Admin;



use Sonata\AdminBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Request;

class ImportExportController extends CoreController
{

    public function indexAction(Request $request)
    {

        $serviceResp = '';
        $t = $this->get('translator');
        $localeCollection = $this->container->getParameter('available_locales');

        $importExportService = $this->container->get('food.import_export_service');
        $fieldMap = $importExportService->getFieldMapForField();

        $forms = [];


        $forms['import'] = $this->get('form.factory')->createNamedBuilder('import', 'form',  null, array(
            'constraints' => [], 'csrf_protection' => false,
        ))
        ->add('importFile', 'file', [ 'attr' => ['class' => 'form-control'] ])
        ->add('locale', 'choice', ['choices' => $localeCollection, 'attr' => ['class' => 'form-control']  ])
        ->add('fields', 'groupped_checkbox', [
            'choices'  => $fieldMap,
            'multiple' => true, 'expanded' => true,
        ])
        ->add('import', 'submit', ['label' => 'import', 'attr' => ['class' => 'form-control btn btn-primary'] ])
        ->remove('token')
        ->getForm();




        $forms['export'] = $this->get('form.factory')->createNamedBuilder('export', 'form',  null, array(
            'constraints' => [], 'csrf_protection' => false,
        ))
        ->add('locale', 'choice', ['choices' => $localeCollection, 'attr' => ['class' => 'form-control']  ])
        ->add('export', 'submit', ['label' => 'export', 'attr' => ['class' => 'form-control btn btn-primary'] ])
        ->getForm();

        if ($request->isMethod('POST')) {
            $whichForm = array_keys($request->request->all())[0];
            $form = $forms[$whichForm];

            $form->handleRequest($request);
            $data = $form->getData();

            $serviceResp =  $importExportService->setLocale($localeCollection[(int)$data['locale']])->process($whichForm, $form);
            if (is_array($serviceResp)) {
                $this->addFlash(
                    $serviceResp['flashMsgType'],
                    $serviceResp['flashMsg']
                );
            } else {
                return $serviceResp;
            }

        }

        foreach ($forms as $k=>$form)
        {
            $forms[$k] = $form->createView();
        }

        return $this->render('@FoodApp/Admin/ImportExport/importIndex.html.twig', array(
            'resp'            => $serviceResp,
            'forms'           => $forms,
            't'               => $t,
            'base_template'   => $this->getBaseTemplate(),
            'admin_pool'      => $this->container->get('sonata.admin.pool'),
            'blocks'          => $this->container->getParameter('sonata.admin.configuration.dashboard_blocks')
        ));
    }

    /**
     * Adds a flash message for type.
     *
     * @param string $type
     * @param string $message
     */
    protected function addFlash($type, $message)
    {
        $this->get('session')
            ->getFlashBag()
            ->add($type, $message);
    }

}