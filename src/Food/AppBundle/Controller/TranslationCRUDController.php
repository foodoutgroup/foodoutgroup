<?php

namespace Food\AppBundle\Controller;

use Doctrine\ORM\Query;
use Exporter\Handler;
use Exporter\Source\ArraySourceIterator;
use Exporter\Writer\CsvWriter;
use Exporter\Writer\JsonWriter;
use Exporter\Writer\XlsWriter;
use Gedmo\Translatable\TranslatableListener;
use Lexik\Bundle\TranslationBundle\Entity\Translation;
use Lexik\Bundle\TranslationBundle\Entity\TransUnit;
use Lexik\Bundle\TranslationBundle\Entity\TransUnitRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use XMLWriter;

class TranslationCRUDController extends CRUDController
{

    public function importAction(Request $request)
    {
        $form = $this->get('form.factory')->createNamedBuilder('import', 'form',  null, array(
            'constraints' => [], 'csrf_protection' => false,
        ))
            ->add('importFile', 'file', [ 'attr' => ['class' => 'form-control'] ])
            ->add('import', 'submit', ['label' => 'import', 'attr' => ['class' => 'form-control btn btn-primary'] ])
            ->remove('token')
            ->getForm();


        if ($request->isMethod('POST')) {

            $form->handleRequest($request);

            $file = $form->get('importFile')->getData();

            /** @var \PHPExcel $excelReader */
            $dataCollection = $this->container->get('phpexcel')->createPHPExcelObject($file)->getActiveSheet()->toArray();
            $em = $this->container->get('doctrine.orm.entity_manager');

            $localeCollection = $this->getManagedLocales();
            $fieldMap = array_flip($dataCollection[0]);

            unset($dataCollection[0]);

            $storage = $em->getRepository(TransUnit::class);
            $transUnitManager = $this->container->get('lexik_translation.trans_unit.manager');
            $em->beginTransaction();
            $imported = false;
            foreach ($dataCollection as $transData)
            {
                if (isset($transData[$fieldMap['id']])) {
                    $transEntity = $storage->find($transData[$fieldMap['id']]);

                    if (!($transEntity instanceof TransUnit)) {
                        $this->addFlash('error', 'Translation not found');
                        return $this->redirect($this->generateUrl('admin_lexik_translation_transunit_list'));
                        // $transEntity = $transUnitManager->create($key, $domain);
                    }
                    $em->persist($transEntity);
                    foreach ($localeCollection as $locale) {


                        $content = (isset($transData[$fieldMap[$locale]]) ? $transData[$fieldMap[$locale]] : '');
                        $translation = $transUnitManager->addTranslation($transEntity, $locale, $content,  null);
                        if ($translation instanceof Translation) {
                            $imported = true;
                        } elseif ($translation = $transUnitManager->updateTranslation($transEntity, $locale, $content))  {
                            $imported = true;
                        }
                        else {
                            $this->addFlash('error', 'Import failed');
                            return $this->redirect($this->generateUrl('admin_lexik_translation_transunit_list'));
                        }
                    }
                }

            }


            $em->flush();
            $em->commit();


            $this->addFlash('success', 'Imported successfully');
            return $this->redirect($this->generateUrl('admin_lexik_translation_transunit_list'));


        }

        return $this->render('@FoodApp/Admin/Translation/import_action.html.twig', array(
            'form'           => $form->createView(),
//            'base_template'   => $this->getBaseTemplate(),
//            'admin_pool'      => $this->container->get('sonata.admin.pool'),
//            'blocks'          => $this->container->getParameter('sonata.admin.configuration.dashboard_blocks')
        ));
    }


    protected function exportFiles()
    {
//        $kernel = $this->get('kernel');
//        $application = new Application($kernel);
//        $application->setAutoExit(false);
//
//        $input = new ArrayInput(array(
//            'command' => 'lexik:translations:export'
//        ));
//
//        $output = new BufferedOutput();
//        $application->run($input, $output);
//
//        $content = $output->fetch();
//        return $content;
    }

    /**
     * @param string $id
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\Response|Ambigous <\Symfony\Component\HttpFoundation\Response, \Symfony\Component\HttpFoundation\RedirectResponse>
     */
    public function editAction($id = null)
    {
        $request = $this->getRequest();
        if (!$request->isMethod('POST')) {
            return $this->redirect($this->admin->generateUrl('list'));
        }

        /* @var $transUnit \Lexik\Bundle\TranslationBundle\Model\TransUnit */
        $transUnit = $this->admin->getObject($id);
        if (!$transUnit) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        if (false === $this->admin->isGranted('EDIT', $transUnit)) {
            return $this->renderJson(array(
                    'message' => 'access denied'
            ), 403);
        }

        $this->admin->setSubject($transUnit);

        /* @var $transUnitManager \Lexik\Bundle\TranslationBundle\Manager\TransUnitManager */
        $transUnitManager = $this->get('lexik_translation.trans_unit.manager');
        $parameters = $this->getRequest()->request;

        $locale = $parameters->get('locale');
        $content = $parameters->get('value');

        if (!$locale) {
            return $this->renderJson(array(
                    'message' => 'locale missing'
            ), 422);
        }

        /* @var $translation \Lexik\Bundle\TranslationBundle\Model\Translation */
        if ($parameters->get('pk')) {
            $translation = $transUnitManager->updateTranslation($transUnit, $locale, $content, true);
        } else {
            $translation = $transUnitManager->addTranslation($transUnit, $locale, $content, null, true);
        }

        if ($request->query->get('clear_cache')) {
            $this->get('translator')->removeLocalesCacheFiles(array($locale));
        }

        return $this->renderJson(array(
                'key' => $transUnit->getKey(),
                'domain' => $transUnit->getDomain(),
                'pk' => $translation->getId(),
                'locale' => $translation->getLocale(),
                'value' => $translation->getContent()
        ));
    }

    public function createTransUnitAction()
    {
        $request = $this->getRequest();
        $parameters = $this->getRequest()->request;
        if (!$request->isMethod('POST')) {
            return $this->renderJson(array(
                    'message' => 'method not allowed'
            ), 403);
        }
        $admin = $this->admin;
        if (false === $admin->isGranted('EDIT')) {
            return $this->renderJson(array(
                    'message' => 'access denied'
            ), 403);
        }
        $keyName = $parameters->get('key');
        $domainName = $parameters->get('domain');
        if (!$keyName || !$domainName) {
            return $this->renderJson(array(
                    'message' => 'missing key or domain'
            ), 422);
        }

        /* @var $transUnitManager \Lexik\Bundle\TranslationBundle\Manager\TransUnitManager */
        $transUnitManager = $this->get('lexik_translation.trans_unit.manager');
        $transUnit = $transUnitManager->create($keyName, $domainName, true);

        return $this->editAction($transUnit->getId());
    }


    /**
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function exportAction(Request $request)
    {
        if (false === $this->admin->isGranted('EXPORT')) {
            throw new AccessDeniedException();
        }

        $source = $this->getDataSourceIterator($request);
        $format = $request->get('format');

        $allowedExportFormats = (array)$this->admin->getExportFormats();

        if (!in_array($format, $allowedExportFormats)) {
            throw new \RuntimeException(sprintf('Export in format `%s` is not allowed for class: `%s`. Allowed formats are: `%s`', $format, $this->admin->getClass(), implode(', ', $allowedExportFormats)));
        }

        $filename = sprintf('export_%s_%s.%s',
            strtolower(substr($this->admin->getClass(), strripos($this->admin->getClass(), '\\') + 1)),
            date('Y_m_d_H_i_s', strtotime('now')),
            $format
        );

        switch ($format) {
            case 'xls':
                $writer = new XlsWriter('php://output');
                $contentType = 'application/vnd.ms-excel';
                break;
            case 'xml':
                $writer = new XmlWriter('php://output');
                $contentType = 'text/xml';
                break;
            case 'json':
                $writer = new JsonWriter('php://output');
                $contentType = 'application/json';
                break;
            case 'csv':
                $writer = new CsvWriter('php://output', ',', '"', "", true, true);
                $contentType = 'text/csv';
                break;
            default:
                throw new \RuntimeException('Invalid format');
        }

        $callback = function () use ($source, $writer) {
            Handler::create($source, $writer)->export();
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type'        => $contentType,
            'Content-Disposition' => sprintf('attachment; filename=%s', $filename)
        ]);
    }

    /**
     * @param Request $request
     *
     * @return DoctrineDBALConnectionSourceIterator
     */
    public function getDataSourceIterator(Request $request)
    {
        @ini_set('memory_limit', 1024 * 1024 * 1024);
        $em = $this->container->get('doctrine.orm.entity_manager');


        $stringCollection = $em->createQueryBuilder()
            ->from(TransUnit::class, 'tu')
            ->select(['tu.id','tu.key','tu.domain', 'te.locale', 'te.content'])
            ->leftJoin('tu.translations', 'te')
            ->getQuery()
            ->getArrayResult();

        $localeCollection = $this->getManagedLocales();

        foreach ($stringCollection as $k=>$trans)
        {
            $co[$trans['locale']][$trans['id']]['id'] = $trans['id'];
            $co[$trans['locale']][$trans['id']]['key'] = $trans['key'];
            $co[$trans['locale']][$trans['id']]['domain'] = $trans['domain'];
            $co[$trans['locale']][$trans['id']]['content'] = $trans['content'];

            $returnData[$trans['id']]['id'] = $trans['id'];
            $returnData[$trans['id']]['key'] = $trans['key'];
            $returnData[$trans['id']]['domain'] =   $trans['domain'];
        }

        foreach ($localeCollection as $locale)
        {
            foreach ($returnData as $key => $data) {
                $returnData[$key][$locale] = (isset($co[$locale][$key]) ? $co[$locale][$key]['content'] : '');
            }
        }


        return new ArraySourceIterator($returnData);
    }

    public function clearCacheAction()
    {

        $this->get('translator')->removeLocalesCacheFiles($this->getManagedLocales());

        /** @var $session Session */
        $session = $this->get('session');
        $session->getFlashBag()->set('sonata_flash_success', 'translations.cache_removed');

        return $this->redirect($this->admin->generateUrl('list'));
    }

    protected function getManagedLocales()
    {
        return $this->container->getParameter('lexik_translation.managed_locales');
    }
}
