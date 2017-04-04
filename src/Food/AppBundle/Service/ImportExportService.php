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
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Translation\Writer\TranslationWriter;

class ImportExportService extends BaseService
{
    protected $router;
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


    public function __construct(EntityManager $em, Router $router, Language $language, Container $container)
    {
        parent::__construct($em);
        $this->router = $router;
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

    private function import(Form $form)
    {
        $file = $form->get('importFile')->getData();
        $file->getPathName();

        /** @var \PHPExcel $excelReader */
        $excelReader = $this->excelWriter->createPHPExcelObject($file);

        if (!$this->setImportFields($form->get('fields')->getData()))
        {
            return  ['flashMsgType' => 'error', 'failed' => true, 'flashMsg' => 'No import fields selected'];
        }

       foreach ($this->getImportFields() as $k=>$table)
       {
           try {
               $sheet = $excelReader->setActiveSheetIndexByName($k);
           } catch (\PHPExcel_Exception $e)
           {
               return  ['flashMsgType' => 'error', 'failed' => true, 'flashMsg' => $e->getMessage()];
           }

           $row = 2;

           $fieldColumns = $this->mapFirstRow($sheet);
           $data = [];
           for($row; $row<=$sheet->getHighestRow(); $row++) {
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

    private function updateRecords($data)
    {
        $qb = $this->em->createQueryBuilder();

        $errorCollection = [];
        foreach ($data as $table=>$items)
        {
            $this->em->beginTransaction();
            $ids =  array_keys($items);
            $entity = $this->getFieldMap();
            $entity = $entity[$table]['entity'];


            $itemsToTranslate = $qb
                ->from($entity, $table)
                ->select($table)
                ->where($table.'.deletedAt IS NULL')
                ->andWhere($qb->expr()->in($table . '.id', $ids))
                ->getQuery()->setHint(
                    Query::HINT_CUSTOM_OUTPUT_WALKER,
                    'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
                )->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $this->getLocale())->execute();


            $objCollection = [];
            foreach ($itemsToTranslate as $itemToTranslate)
            {
                $itemToTranslate->setTranslatableLocale($this->getLocale());
                $objCollection[$itemToTranslate->getId()] = $itemToTranslate;

            }


            foreach ($items as $itemId=>$item) {


                $changed = false;
                if (!is_int($itemId)) {
                   return ['msg' => $itemId . ' is not an integer'];
                }

                foreach ($item['fields'] as $fieldName => $dataToSet) {
                    $generateSlug = false;
                    if (strpos($fieldName, '~') !== false) {
                        $fieldName = str_replace('~', '', $fieldName);
                        $generateSlug = true;

                    }
                        $setter = Inflector::camelize('set_' . $fieldName);
                        $getter = Inflector::camelize('get_' . $fieldName);
                        if (method_exists($objCollection[$itemId], $setter)) {
                            if ($dataToSet != $objCollection[$itemId]->{$getter}()) {
                                $changed = true;
                                $objCollection[$itemId]->{$setter}($dataToSet);

                            }
                        } else {
                            $errorCollection[$table][$itemId][$fieldName] = $dataToSet;
                            return ['msg' => 'Setter function ' . $setter . " doesn't exist"];


                            // todo Apdoroti ir patikrinti
                        }

                    if ($generateSlug) {
                        $this->slugService->generateForLocale($this->getLocale(), $objCollection[$itemId], $fieldName, null);
                    }
                }


                if ($changed == true) {
                    $this->em->persist($objCollection[$itemId]);
                }
            }

            $this->em->flush();
            $this->em->commit();

        }

        return $errorCollection;
    }

    private function mapFirstRow(PHPExcel_Worksheet  $sheet)
    {
        $columns = [];
        $row = $sheet->getRowIterator(1)->current();

        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        foreach ($cellIterator as $cell) {
            if ($val = $cell->getValue()) {
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
        foreach ($this->fieldMap as $k=>$sheet)
        {
            $fields = ['id'];
            $fields = array_merge($fields, $sheet['fields']);

            $phpExcelObject->createSheet($sheetId)->setTitle($k);

            $collection = $this->em->createQueryBuilder()
               ->from($sheet['entity'], $k)
               ->select($k)
               ->where($k.'.deletedAt IS NULL')
               ->getQuery()->setHint(
                   Query::HINT_CUSTOM_OUTPUT_WALKER,
                   'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
               )->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $this->getLocale())->execute();
            $sheet = $phpExcelObject->setActiveSheetIndex($sheetId);

            foreach ($fields as $field)
            {
                $sheet->setCellValue($col.$row, $field );
                $col++;
            }


            foreach ($collection as $item)
            {
                $row++;
                $col = 'A';
                foreach ($fields as $field) {
                    $field = str_replace('~','',$field);
                    $f = Inflector::camelize("get_" . $field);

                    if (method_exists($item, $f)) {
                        $sheet->setCellValue($col . $row, $item->{$f}());
                    } else {die($f);}
                    $col++;
                }

            }


            $col = 'A';
            $row=1;
            $highestCol = $sheet->getHighestDataColumn();
            $highestRow = $sheet->getHighestDataRow();


            $sheet->protectCells('A1:'.$highestCol.'1', 'PHP');
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

            $sheet->getStyle('A1:A'.$highestRow)->applyFromArray(
                array(
                    'fill' => array(
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => 'FF0000')
                    )
                ));

            $sheet->getStyle('B2:'.$highestCol.$highestRow)->getProtection()
                ->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
            $sheet->getProtection()->setSheet(true);

            $sheetId++;

        }

        $writer = $this->excelWriter->createWriter($phpExcelObject, 'Excel5');
        $response = $this->excelWriter->createStreamedResponse($writer);


        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            date('Ymd_Hi').'_'.$this->getLocale().'_export.xls'
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);


        return $response;

    }


    public function setLocale($locale)
    {
       $availableLocales = array_flip($this->container->getParameter('available_locales'));

        if (!in_array($locale, $availableLocales))
        {
            throw  new \Exception('Locale not found');
        }

        $this->locale = $locale;
        return $this;
    }



    public function setFieldMap($fieldMapArray = null)
    {
        if ($fieldMapArray != null)
        {
            $this->fieldMap = $fieldMapArray;
        }
        else {
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
                        'fields' => ['name','alias', '~slug']
                    ],
                'place' =>
                    [
                        'entity' => 'Food\DishesBundle\Entity\Place',
                        'fields' => [ 'name', 'slogan', 'description', 'notification_content', '~slug']
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
        foreach ($this->fieldMap as $k=>$field)
        {
            $fields = [];
            foreach ($field['fields'] as $key => $field)
            {
                $fieldVal = str_replace('~','',$field);
                $fields[$k.'["'.$field.'"]'] = $fieldVal;
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

        return $this->importFields;
    }

    /**
     * @param array $importFields
     */
    public function setImportFields($importFields)
    {

        $re = '/^([A-Za-z0-9_]+)\[\"(.*)\"\]$/';

        $fields = [];


        if (count($importFields) < 1)
        {
            return false;
        }

        foreach ($importFields as $field)
        {
            preg_match_all($re, $field, $matches, PREG_SET_ORDER, 0);
            $fields[$matches[0][1]][] = $matches[0][2];
        }

        $this->importFields = $fields;
        return $this;
    }


}