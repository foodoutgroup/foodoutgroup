<?php
namespace Food\AppBundle\Service;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Exporter\Handler;
use Exporter\Source\ArraySourceIterator;
use Exporter\Writer\XlsWriter;
use Food\AppBundle\Entity\City;
use Food\AppBundle\Utils\Language;
use Food\DishesBundle\Entity\DishOption;
use Food\DishesBundle\Entity\Place;
use Gedmo\Translatable\TranslatableListener;
use PHPExcel_Style_Fill;
use PHPExcel_Style_Protection;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function __construct(EntityManager $em, Router $router, Language $language, $container)
    {
        parent::__construct($em);
        $this->router = $router;
        $this->language = $language;
        $this->request = $container->get('request');
        $this->container = $container;
        $this->excelWriter = $container->get('phpexcel');
        $this->locale = $container->getParameter('locale');
    }

    public function process($action, $formData = null)
    {
        if ($action === 'export') {
            return $this->export();
        }
        else {
            return $this->import($formData);
        }
    }

    private function import($formData)
    {
        var_dump($formData);die();
        $this->setImportFile($formData);
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
                    $f = Inflector::camelize("get_" . $field);

                    if (method_exists($item, "$f")) {
                        $sheet->setCellValue($col . $row, $item->{$f}());
                    }
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
                        'fields' => ['title', 'meta_title', 'meta_description']
                    ],
                'dish' =>
                    [
                        'entity' => 'Food\DishesBundle\Entity\Dish',
                        'fields' => ['name', 'description']
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
                        'entity' => 'Food\DishesBundle\Entity\DishUnit',
                        'fields' => ['name']
                    ],
                'kitchen' =>
                    [
                        'entity' => 'Food\DishesBundle\Entity\Kitchen',
                        'fields' => ['name']
                    ],
                'place' =>
                    [
                        'entity' => 'Food\DishesBundle\Entity\Place',
                        'fields' => [ 'slogan', 'description', 'notification_content']
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
            $return[$k] = $field['fields'];
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


}