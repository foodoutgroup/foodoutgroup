<?php
namespace Food\AppBundle\Controller\Admin;



use Food\AppBundle\Form\Fictional\Field;
use Food\AppBundle\Form\Fictional\Import;
use Food\AppBundle\Form\Fictional\Table;
use Sonata\AdminBundle\Controller\CoreController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class ImportExportController extends CoreController
{

    public function indexAction(Request $request)
    {

        $import = new Import();
        $import->setLocale('ru');

        $table = new Table('test');
        $table->addField(new Field('test',$table));
        $table->setName('bled');
        $import->addTable($table);
        $t = $this->get('translator');
        $localeCollection = $this->container->getParameter('available_locales');

        $importExportService = $this->container->get('food.import_export_service');
        $fieldMap = $importExportService->getFieldMapForField();

        $forms = [];


        $form = $this->get('form.factory')->createNamedBuilder('import', 'form',  $import, array(
            'constraints' => [], 'csrf_protection' => false,
        ))
            ->add('file', 'file', [ 'attr' => ['class' => 'form-control'] ])
            ->add('locale', 'text'/*, ['choices' => $localeCollection, 'attr' => ['class' => 'form-control']]*/ )
//            ->add('tables', 'groupped_checkbox',['by_reference' =>false, 'allow_add' => true, 'allow_delete' => true, 'data_class' => 'Food\AppBundle\Form\Fictional\Table'])
            ->add('tables', 'collection')
//            ->add('fields', 'groupped_checkbox', [
//                'choices'  => $fieldMap,
//                'multiple' => true, 'expanded' => true,
//            ])
            //   ->add('import', 'submit', ['label' => 'import', 'attr' => ['class' => 'form-control'] ])
            // ->remove('token')
            ->getForm();

        $form->handleRequest($request);

        $forms[] =$form;

//        $forms['export'] = $this->get('form.factory')->createNamedBuilder('export', 'form',  null, array(
//            'constraints' => [], 'csrf_protection' => false,
//        ))
//            ->add('locale', 'choice', ['choices' => $localeCollection, 'attr' => ['class' => 'form-control']  ])
//            ->add('export', 'submit', ['label' => 'export', 'attr' => ['class' => 'form-control'] ])
//            ->getForm();

        if ($request->isMethod('POST')) {

            $data = $form->getData();
            var_dump($data);die();
//            $whichForm = array_keys($request->request->all())[0];
//            $form = $forms[$whichForm];
//
//            $form->handleRequest($request);
//            $data = $form->getData();
//
//            return $importExportService->setLocale($localeCollection[(int)$data['locale']])->process($whichForm, $data);
//            //return $ser;

        }

        foreach ($forms as $k=>$form)
        {
            $forms[$k] = $form->createView();
        }

        return $this->render('@FoodApp/Admin/ImportExport/importIndex.html.twig', array(
            'forms'           => $forms,
            't'               => $t,
            'base_template'   => $this->getBaseTemplate(),
            'admin_pool'      => $this->container->get('sonata.admin.pool'),
            'blocks'          => $this->container->getParameter('sonata.admin.configuration.dashboard_blocks')
        ));
    }


}