<?php
namespace Food\AppBundle\Service;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\Entity;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Exporter\Handler;
use Exporter\Source\ArraySourceIterator;
use Exporter\Writer\XlsWriter;
use Food\AppBundle\Entity\City;
use Food\AppBundle\Entity\Slug;
use Food\AppBundle\Utils\Language;
use Food\DishesBundle\Entity\DishOption;
use Food\DishesBundle\Entity\FoodCategory;
use Food\DishesBundle\Entity\Place;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;
use PHPExcel_Cell;
use PHPExcel_Style_Fill;
use PHPExcel_Style_Protection;
use PHPExcel_Worksheet;
use PHPExcel_Writer_CSV;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;

class ImportExportService extends BaseService
{
    protected $locale;
    protected $language;
    protected $request;
    protected $fieldMap;
    protected $container;

    protected $excelWriter;

    protected $importFile;
    /**
     * @var array
     */
    private $importFields;
    private $slugService;
    private $saveDirectory;
    private $fileSystem;

    public function __construct(EntityManager $em, Language $language, Container $container, Filesystem $filesystem)
    {
        parent::__construct($em);
        $this->fileSystem = $filesystem;
        $this->language = $language;
        $this->request = $container->get('request');
        $this->container = $container;
        $this->excelWriter = $container->get('phpexcel');
        $this->locale = $container->getParameter('locale');
        $this->slugService = $container->get('slug');
    }


    public function process($action, $form = null)
    {
        return $this->{$action}($form);
    }

    public function doImport()
    {

        $file = $this->saveDirectory . '/' . 'tmpForImport.xls';


        /** @var \PHPExcel $excelReader */
        $excelReader = $this->excelWriter->createPHPExcelObject($file);

//        if (!$this->setImportFields($fields)) {
//            return ['flashMsgType' => 'error', 'failed' => true, 'flashMsg' => 'No import fields selected'];
//        }
        $data = [];

        foreach ($this->getImportFields() as $k => $table) {
            try {
                $sheet = $excelReader->setActiveSheetIndexByName($k);
            } catch (\PHPExcel_Exception $e) {
                return ['flashMsgType' => 'error', 'success' => false, 'flashMsg' => $e->getMessage()];
            }

            $row = 2;

            $fieldColumns = $this->mapFirstRow($sheet);
            for ($row; $row <= $sheet->getHighestRow(); $row++) {
                foreach ($table as $field) {
                    $id = $sheet->getCell('A' . $row)->getValue();
                    $data[$k][$id]['fields'][$field] = $sheet->getCell($fieldColumns[$field] . $row)->getValue();

                }
            }
        }

        $hasErrors = $this->updateRecords($data);
        if (count($hasErrors) < 1) {
            return ['flashMsgType' => 'success', 'flashMsg' => 'Your changes were saved successfully'];
        } else {
            return ['flashMsgType' => 'error', 'failed' => $hasErrors, 'flashMsg' => $hasErrors['msg']];
        }


    }

    private function import(Form $form)
    {

        /** @var File $file */
        $file = $form->get('importFile')->getData();
        $this->setImportFields($form->get('fields')->getData());

        if (!$this->fileSystem->exists($this->saveDirectory)) {
            $this->fileSystem->mkdir($this->saveDirectory);
        }
        $file->move($this->saveDirectory, 'tmpForImport.xls');

        $root_dir = $this->container->get('kernel')->getRootDir();
        $logName = $this->container->get('kernel')->getEnvironment() . 'log';
        $cmd = sprintf('%s/console %s', $root_dir, 'import:process --locale=' . $this->getLocale());
        $cmd = sprintf("%s --env=%s >> %s 2>&1 & echo $!", $cmd, $this->container->get('kernel')->getEnvironment(), sprintf('%s/logs/%s.log', $root_dir, $logName));
        $process = new Process($cmd);
        $process->start();


        $response['flashMsgType'] = 'success';
        $response['flashMsg'] = $process->getOutput() . ' process running';

        return $response;

    }

    private function updateRecords($data)
    {

        $flushed = 0;
        $errorCollection = [];
        foreach ($data as $table => $items) {
            $qb = $this->em->createQueryBuilder();

            $ids = null;

            $ids = array_keys($items);
            $entity = $this->getFieldMap();
            $entity = $entity[$table]['entity'];
            echo 'started query build' .  PHP_EOL;
            $itemsToTranslate = $qb
                ->from($entity, $table)
                ->select($table)
                ->where($table . '.deletedAt IS NULL')
                ->andWhere($qb->expr()->in($table . '.id', $ids))
                ->getQuery()->setHint(
                    Query::HINT_CUSTOM_OUTPUT_WALKER,
                    'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
                )->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $this->getLocale())->execute();
            echo 'ended query build' .  PHP_EOL;

            $objCollection = [];
            echo 'started translatable build' .  PHP_EOL;

            foreach ($itemsToTranslate as $itemToTranslate) {
                $itemToTranslate->setTranslatableLocale($this->getLocale());
                $objCollection[$itemToTranslate->getId()] = $itemToTranslate;

            }

            echo 'ended translatable build' .  PHP_EOL;
            foreach ($items as $itemId => $item) {
                $qb = $this->em->createQueryBuilder();



                $changed = false;
                if (!is_int($itemId)) {
                    return ['msg' => $itemId . ' is not an integer'];
                }
                echo 'started fields build' .  PHP_EOL;

                foreach ($item['fields'] as $fieldName => $dataToSet) {
                    $fromField = '';
                    $generateSlug = false;
                    if (strpos($fieldName, '~') !== false) {

                        $fieldName = str_replace('~', '', $fieldName);
                        $fromField = $fieldName;
                        $generateSlug = true;

                    }
                    $setter = Inflector::camelize('set_' . $fieldName);
                    $getter = Inflector::camelize('get_' . $fieldName);

                    if ($generateSlug) {

                        if (strlen($item['fields']['~' . $fromField]) < 1) {
                            if (@strlen($item['fields']['title']) > 1) {
                                $fromField = 'title';
                            } else {
                                $fromField = 'name';
                            }
                        }

                        $this->slugService->generateForLocale($this->getLocale(), $objCollection[$itemId], $fromField, null);
                        $dataToSet = $this->slugService->get($itemId, $objCollection[$itemId]::SLUG_TYPE, $this->getLocale());
                        $objCollection[$itemId]->setSlug($dataToSet);
                    }

                    if (method_exists($objCollection[$itemId], $setter)) {
                        $current = $objCollection[$itemId]->{$getter}();
                        if (!is_null($dataToSet) && !is_null($current)){
                            if ($dataToSet != $objCollection[$itemId]->{$getter}()) {
                                $changed = true;

                                $objCollection[$itemId]->{$setter}($dataToSet);
                            }
                        }
                    } else {
                        $errorCollection[$table][$itemId][$fieldName] = $dataToSet;
                        return ['msg' => 'Setter function ' . $setter . " doesn't exist"];
                    }


                }
                echo 'ended fields build' .  PHP_EOL;

                if ($changed == true) {
                    $this->em->persist($objCollection[$itemId]);
                    unset($objCollection[$itemId]);
                    try {
                        echo 'started flushing ' . $table .  PHP_EOL;
                        echo $fieldName . ' - ' . $current . ' - ' . $dataToSet . PHP_EOL;
                        $this->em->flush();
                        $flushed++;
                        echo 'ended flushing ' . $table .  PHP_EOL;
                    } catch (\Exception $e) {
                        $this->container->get('logger')->addError($e->getMessage());
//                        $this->em->rollback();
//                        $this->em->clear();
                    }
                }
            }
        }




        echo $flushed . PHP_EOL;
        die();
        return $errorCollection;
    }

    private function mapFirstRow(PHPExcel_Worksheet $sheet)
    {
        $columns = [];
        $row = $sheet->getRowIterator(1)->current();

        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        foreach ($cellIterator as $cell) {
            if ($val = strtolower($cell->getValue())) {
                $columns[$val] = $cell->getColumn();
            }
        }

        return $columns;
    }


    private function export()
    {
        $phpExcelObject = $this->excelWriter->createPHPExcelObject();

        $sheetId = 0;
        $col = 'A';
        $row = 1;
        foreach ($this->fieldMap as $k => $sheet) {
            $fields = ['id'];
            $fields = array_merge($fields, $sheet['fields']);

            $phpExcelObject->createSheet($sheetId)->setTitle($k);

            $collection = $this->em->createQueryBuilder()
                ->from($sheet['entity'], $k)
                ->select($k)
                ->where($k . '.deletedAt IS NULL')
                ->getQuery()->setHint(
                    Query::HINT_CUSTOM_OUTPUT_WALKER,
                    'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
                )->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $this->getLocale())->execute();
            $sheet = $phpExcelObject->setActiveSheetIndex($sheetId);

            foreach ($fields as $field) {
                $sheet->setCellValue($col . $row, $field);
                $col++;
            }


            foreach ($collection as $item) {
                if (method_exists($item, 'getActive')) {
                    if (!$item->getActive()){ continue; }
                } elseif (method_exists($item, 'isActive')) {
                    if (!$item->isActive()){ continue; }
                }
                $row++;
                $col = 'A';
                foreach ($fields as $field) {
                    $field = str_replace('~', '', $field);
                    $f = Inflector::camelize("get_" . $field);

                    if (method_exists($item, $f)) {
                        $sheet->setCellValue($col . $row, $item->{$f}());
                    } else {
                        die($f);
                    }
                    $col++;
                }

            }


            $col = 'A';
            $row = 1;
            $highestCol = $sheet->getHighestDataColumn();
            $highestRow = $sheet->getHighestDataRow();


            $sheet->protectCells('A1:' . $highestCol . '1', 'PHP');
            $sheet->freezePane('A1');
            $sheet->getStyle('A1:Z1')->applyFromArray(
                array(
                    'fill' => array(
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => 'FF0000')
                    )
                ));

            $sheet->getDefaultRowDimension()->setRowHeight(-1);
            $sheet->getDefaultColumnDimension()->setWidth(60);
            $sheet->getColumnDimension('A')->setWidth(10);

            $sheet->getStyle('A1:A' . $highestRow)->applyFromArray(
                array(
                    'fill' => array(
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => 'FF0000')
                    )
                ));

            $sheet->getStyle('B2:' . $highestCol . $highestRow)->getProtection()
                ->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
            $sheet->getProtection()->setSheet(true);

            $sheetId++;

        }

        $writer = $this->excelWriter->createWriter($phpExcelObject, 'Excel5');
        $writer->setPreCalculateFormulas(false);

        $response = $this->excelWriter->createStreamedResponse($writer);


        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            date('Ymd_Hi') . '_' . $this->getLocale() . '_export.xls'
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);


        return $response;

    }


    public function setLocale($locale)
    {
        $availableLocales = array_flip($this->container->getParameter('locales'));

        if (!in_array($locale, $availableLocales)) {
            throw  new \Exception('Locale not found');
        }

        $this->locale = $locale;
        return $this;
    }


    public function setFieldMap($fieldMapArray = null)
    {
        if ($fieldMapArray != null) {
            $this->fieldMap = $fieldMapArray;
        } else {
            $this->fieldMap = [
                'city' =>
                    [
                        'entity' => 'Food\AppBundle\Entity\City',
                        'fields' => ['title', 'meta_title', 'meta_description', '~slug']
                    ],
                'dish' =>
                    [
                        'entity' => 'Food\DishesBundle\Entity\Dish',
                        'fields' => ['name', 'description', '~slug']
                    ],
                'dish_option' =>
                    [
                        'entity' => 'Food\DishesBundle\Entity\DishOption',
                        'fields' => ['name', 'description']
                    ],
                'dish_unit' =>
                    [
                        'entity' => 'Food\DishesBundle\Entity\DishUnit',
                        'fields' => ['name', 'short_name']
                    ],
                'food_category' =>
                    [
                        'entity' => 'Food\DishesBundle\Entity\FoodCategory',
                        'fields' => ['name', '~slug']
                    ],
                'kitchen' =>
                    [
                        'entity' => 'Food\DishesBundle\Entity\Kitchen',
                        'fields' => ['name', 'alias', '~slug']
                    ]
            ];
        }
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return mixed
     */
    public function getFieldMap()
    {
        return $this->fieldMap;
    }

    public function getFieldMapForField()
    {
        $return = [];
        foreach ($this->fieldMap as $k => $field) {
            $fields = [];
            foreach ($field['fields'] as $key => $field) {
                $fieldVal = str_replace('~', '', $field);
                $fields[$k . '["' . $field . '"]'] = $fieldVal;
            }
            $return[$k] = $fields;
        }
        return $return;
    }

    /**
     * @return mixed
     */
    public function getImportFile()
    {
        return $this->importFile;
    }

    /**
     * @param mixed $importFile
     */
    public function setImportFile($importFile)
    {
        $this->importFile = $importFile;
    }

    /**
     * @return array
     */
    public function getImportFields()
    {
        return json_decode('{"city":["title","meta_title","meta_description","~slug"],"dish":["name","description","~slug"],"dish_option":["name","description"],"dish_unit":["name","short_name"],"food_category":["name","~slug"],"kitchen":["name","alias","~slug"]}');


//          return $this->importFields;
    }

    /**
     * @param array $importFields
     */
    public function setImportFields($importFields)
    {

        $re = '/^([A-Za-z0-9_]+)\[\"(.*)\"\]$/';

        $fields = [];


        if (count($importFields) < 1) {
            return false;
        }

        foreach ($importFields as $field) {
            preg_match_all($re, $field, $matches, PREG_SET_ORDER, 0);
            $fields[$matches[0][1]][] = $matches[0][2];
        }

        $this->importFields = $fields;

        $this->container->get('session')->set('importFields', $fields);

        return $this;
    }

    public function setSaveDirectory($saveDirectory)
    {
        $this->saveDirectory = $saveDirectory;
    }
}