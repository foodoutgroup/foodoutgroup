<?php

namespace Food\OrderBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Service\BaseService;
use Food\OrderBundle\Entity\OrderFieldChangelog;
use PHPExcel_IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\SecurityContextInterface;

class OrderDataImportService extends BaseService
{
    private $orderFieldsMapping;
    private $securityContext;

    public function __construct(EntityManager $em, SecurityContextInterface $securityContext)
    {
        parent::__construct($em);
        $this->securityContext = $securityContext;
        $this->orderFieldsMapping = $this->getOrderFieldsMapping();
    }

    /**
     * @param UploadedFile $uploadedFile
     */
    public function importData($uploadedFile)
    {
        $importLog = array();
        $headers = array();
        $objPHPExcel = PHPExcel_IOFactory::load($uploadedFile->getRealPath());
        foreach ($objPHPExcel->getAllSheets() as $sheet) {
            $excelOrders = $sheet->toArray();
            if (!$headers) {
                $headers = $excelOrders[0];
            }
            unset($excelOrders[0]);
            foreach ($excelOrders as $excelOrder) {
                $importLog[] = $this->updateOrder($excelOrder);
            }
        }
        die;
        return $importLog;
    }

    /**
     * @param array $excelData
     */
    private function updateOrder($excelData)
    {
        $updateLog = array();
        $realOrder = $this->em->getRepository('FoodOrderBundle:Order')->findOneBy(array(
            'id' => $excelData[$this->getMapIndex('id')]
        ));

        if (count($realOrder)) {
            foreach ($this->orderFieldsMapping as $mapKey => $mapValue) {
                if ($excelData[$mapValue['index']]) {
                    $oldValue = $realOrder->$mapValue['getter']();
                    if ($oldValue != $excelData[$mapValue]) {
                        $updateLog[] = (string)$this->logChange($realOrder, $mapKey, $oldValue, $excelData[$mapValue['index']]);
                        $realOrder->$mapValue['setter']($excelData[$mapValue['index']]);
                        $this->em->persist($realOrder);
                    }
                    break;
                }
            }
        }

        return $updateLog;
    }

    private function logChange($realOrder, $fieldname, $oldValue, $newValue)
    {
        $change = new OrderFieldChangelog();
        $currentUser = $this->getContainer()->get('security.context')->getToken()->getUser();
        $change->setUser($currentUser);
        $now = new \DateTime();
        $change->setDate($now);
        $change->setOrder($realOrder);
        $change->setFieldname($fieldname);
        $change->setOldValue($oldValue);
        $change->setNewValue($newValue);
        $change->setDataImport($realOrder);
        $this->em->persist($change);

        return $change;
    }

    private function getMapIndex($fieldname)
    {
        return $this->orderFieldsMapping[$fieldname]['index'];
    }

    private function getOrderFieldsMapping()
    {
        return array(
            'sf_number' => array('index' => 0, 'getter' => 'getSfNumber', 'setter' => 'setSfNumber'),
            'id' => 1,
            'order_date' => 2,
            'place_id' => 3,
            'place_name' => 4,
            'driver_id' => 5,
            'point_id' => 16,
        );
    }
}