<?php

namespace Food\OrderBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Service\BaseService;
use Food\OrderBundle\Entity\OrderFieldChangelog;
use PHPExcel_IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\TypeValidator;
use Symfony\Component\Validator\Validator;

class OrderDataImportService extends BaseService
{
    private $orderFieldsMapping;
    private $securityContext;
    private $validator;
    private $importObject;

    public function __construct(EntityManager $em, SecurityContextInterface $securityContext, Validator $validator)
    {
        parent::__construct($em);
        $this->securityContext = $securityContext;
        $this->orderFieldsMapping = $this->getOrderFieldsMapping();
        $this->validator = $validator;
    }

    /**
     * @param UploadedFile $uploadedFile
     */
    public function importData($uploadedFile, $importObject = null)
    {
        if ($importObject) {
            $this->importObject = $importObject;
        }
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
                $importLogData = $this->updateOrder($excelOrder);
                if (!empty($importLogData)) {
                    $importLog[] = $importLogData;
                }
            }
        }
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
            foreach ($this->orderFieldsMapping as $mapKey => $mapIndex) {
                $valueChanged = false;
                if ($excelData[$mapIndex]) {
                    switch ($mapKey) {
                        case 'order_date':
                            $oldValue = $realOrder->getOrderDate()->format('Y-m-d');
                            $errorList = $this->validator->validateValue($oldValue, array(
                                new Date(),
                                new NotNull()
                            ));
                            if ($oldValue != date('Y-m-d', strtotime($excelData[$mapIndex])) && count($errorList) == 0) {
                                $valueChanged = true;
                                // validate
                                $realOrder->setOrderDate(new \DateTime($excelData[$mapIndex]));
                            }
                            break;
                        case 'place_id':
                            $oldValue = $realOrder->getPlace()->getId();
                            $errorList = $this->validator->validateValue($oldValue, array(
                                new Type(array('type' => 'int')),
                                new NotNull()
                            ));
                            if ($oldValue != $excelData[$mapIndex] && count($errorList) == 0) {
                                $valueChanged = true;
                                // validate
                                $realOrder->setPlace($this->em->getRepository('FoodDishesBundle:Place')->find($excelData[$mapIndex]));
                            }
                            break;
                        case 'driver_id':
                            $oldValue = $realOrder->getDriver()->getId();
                            $errorList = $this->validator->validateValue($oldValue, array(
                                new Type(array('type' => 'int')),
                                new NotNull()
                            ));
                            if ($oldValue != $excelData[$mapIndex] && count($errorList) == 0) {
                                $valueChanged = true;
                                // validate
                                $realOrder->setDriver($this->em->getRepository('FoodAppBundle:Driver')->find($excelData[$mapIndex]));
                            }
                            break;
                        case 'payment_method':
                            $oldValue = $realOrder->getPaymentMethod();
                            $errorList = $this->validator->validateValue($oldValue, array(
                                new Type(array('type' => 'string')),
                                new NotNull()
                            ));
                            if ($oldValue != $excelData[$mapIndex] && count($errorList) == 0) {
                                $valueChanged = true;
                                // validate
                                $realOrder->setPaymentMethod($excelData[$mapIndex]);
                            }
                            break;
                        case 'delivery_price':
                            $oldValue = $realOrder->getDeliveryPrice();
                            $errorList = $this->validator->validateValue($oldValue, array(
                                new Type(array('type' => 'double')),
                                new NotNull()
                            ));
                            if ($oldValue != $excelData[$mapIndex] && count($errorList) == 0) {
                                $valueChanged = true;
                                // validate
                                $realOrder->setDeliveryPrice($excelData[$mapIndex]);
                            }
                            break;
                        case 'total':
                            $oldValue = $realOrder->getTotal();
                            $errorList = $this->validator->validateValue($oldValue, array(
                                new Type(array('type' => 'double')),
                                new NotNull()
                            ));
                            if ($oldValue != $excelData[$mapIndex] && count($errorList) == 0) {
                                $valueChanged = true;
                                // validate
                                $realOrder->setTotal($excelData[$mapIndex]);
                            }
                            break;
                        case 'discount_sum':
                            $oldValue = $realOrder->getDiscountSum();
                            $errorList = $this->validator->validateValue($oldValue, array(
                                new Type(array('type' => 'double')),
                                new NotNull()
                            ));
                            if ($oldValue != $excelData[$mapIndex] && count($errorList) == 0) {
                                $valueChanged = true;
                                $realOrder->setDiscountSum($excelData[$mapIndex]);
                            }
                            break;
                    }
                    if ($valueChanged) {
                        $updateLog[] = (string)$this->logChange($realOrder, $mapKey, $oldValue, $excelData[$mapIndex]);
                        $this->em->persist($realOrder);
                    }
                }
            }
        }

        return $updateLog;
    }

    private function logChange($realOrder, $fieldname, $oldValue, $newValue)
    {
        $change = new OrderFieldChangelog();
        $currentUser = $this->securityContext->getToken()->getUser();
        $change->setUser($currentUser);
        $now = new \DateTime();
        $change->setDate($now);
        $change->setOrder($realOrder);
        $change->setFieldname($fieldname);
        $change->setOldValue($oldValue);
        $change->setNewValue($newValue);
        if ($this->importObject) {
            $change->setDataImport($this->importObject);
        }
        $this->em->persist($change);

        return $change;
    }

    private function getMapIndex($fieldname)
    {
        return $this->orderFieldsMapping[$fieldname];
    }

    private function getOrderFieldsMapping()
    {
        return array(
            'sf_number' => 0,
            'id' => 1,
            'order_date' => 2,
            /*'place_id' => 3,
            'place_name' => 4,
            'driver_id' => 5,*/
            'payment_method' => 10,
            'delivery_price' => 13,
            'total' => 14,
            'discount_sum' => 15,
        );
    }
}