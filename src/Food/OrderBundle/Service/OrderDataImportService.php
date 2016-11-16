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
        $importLog = array(
            'infodata' => array(),
            'orders' => array(),
        );
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
                if (!empty($importLogData['infodata'])) {
                    $importLog['infodata'][] = $importLogData['infodata'];
                    $importLog['orders'] = array_merge($importLog['orders'], $importLogData['orders']);
                }
            }
        }
        $importLog['orders'] = array_unique($importLog['orders']);
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
                $oldValue = null;
                $newValue = null;
                if ($excelData[$mapIndex]) {
                    switch ($mapKey) {
                        case 'order_date':
                            $oldValue = $realOrder->getOrderDate()->format('Y-m-d');
                            $newValue = $excelData[$mapIndex];
                            $errorList = $this->validator->validateValue($newValue, array(
                                new Date(),
                                new NotNull()
                            ));
                            if ($oldValue != date('Y-m-d', strtotime($newValue)) && count($errorList) == 0) {
                                $valueChanged = true;
                                $realOrder->setOrderDate(new \DateTime($newValue));
                            }
                            break;
                        case 'place_id':
                            $oldValue = $realOrder->getPlace()->getId();
                            $newValue = $excelData[$mapIndex];
                            $errorList = $this->validator->validateValue($newValue, array(
                                new Type(array('type' => 'int')),
                                new NotNull()
                            ));
                            if ($oldValue != $newValue && count($errorList) == 0) {
                                $valueChanged = true;
                                $realOrder->setPlace($this->em->getRepository('FoodDishesBundle:Place')->find($newValue));
                            }
                            break;
                        case 'driver_id':
                            if ($realOrder->getDriver()) {
                                $oldValue = $realOrder->getDriver()->getId();
                            }
                            $newValue = $excelData[$mapIndex];
                            $errorList = $this->validator->validateValue($newValue, array(
                                new Type(array('type' => 'int')),
                                new NotNull()
                            ));
                            if ($oldValue != $newValue && count($errorList) == 0) {
                                $valueChanged = true;
                                $realOrder->setDriver($this->em->getRepository('FoodAppBundle:Driver')->find($newValue));
                            }
                            break;
                        case 'payment_method':
                            $oldValue = $realOrder->getPaymentMethod();
                            $newValue = $excelData[$mapIndex];
                            $errorList = $this->validator->validateValue($newValue, array(
                                new Type(array('type' => 'string')),
                                new NotNull()
                            ));
                            if ($oldValue != $newValue && count($errorList) == 0) {
                                $valueChanged = true;
                                $realOrder->setPaymentMethod($newValue);
                            }
                            break;
                        case 'delivery_price':
                            $oldValue = $realOrder->getDeliveryPrice();
                            $newValue = $excelData[$mapIndex];
                            $errorList = $this->validator->validateValue($newValue, array(
                                new Type(array('type' => 'double')),
                                new NotNull()
                            ));
                            if ($oldValue != $newValue && count($errorList) == 0) {
                                $valueChanged = true;
                                $realOrder->setDeliveryPrice($newValue);
                            }
                            break;
                        case 'total':
                            $oldValue = $realOrder->getTotal();
                            $newValue = $excelData[$mapIndex];
                            $errorList = $this->validator->validateValue($newValue, array(
                                new Type(array('type' => 'double')),
                                new NotNull()
                            ));

                            if ($oldValue != $newValue && count($errorList) == 0) {
                                $valueChanged = true;
                                $realOrder->setTotal($newValue);
                            }
                            break;
                        case 'discount_sum':
                            $oldValue = $realOrder->getDiscountSum();
                            $newValue = $excelData[$mapIndex];
                            $errorList = $this->validator->validateValue($newValue, array(
                                new Type(array('type' => 'double')),
                                new NotNull()
                            ));
                            if ($oldValue != $newValue && count($errorList) == 0) {
                                $valueChanged = true;
                                $realOrder->setDiscountSum($newValue);
                            }
                            break;
                    }
                    if ($valueChanged) {
                        $updateLog['infodata'][] = $this->logChange($realOrder, $mapKey, $oldValue, $newValue);
                        $updateLog['orders'][] = $realOrder->getId();
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

        return $change->getChangeData();
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
            'place_id' => 3,
            'place_name' => 4,
            'driver_id' => 5,
            'payment_method' => 10,
            'delivery_price' => 13,
            'total' => 14,
            'discount_sum' => 15,
        );
    }
}