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
        $objPHPExcel = PHPExcel_IOFactory::load($uploadedFile->getRealPath());
        $excelOrders = $objPHPExcel->getActiveSheet()->toArray();
        $headers = $excelOrders[0];
        unset($excelOrders[0]);

        var_dump($headers);
        foreach ($excelOrders as $excelOrder) {
            $this->updateOrder($excelOrder);
        }
        die;
    }

    /**
     * @param array $excelData
     */
    private function updateOrder($excelData)
    {
        $realOrder = $this->em->getRepository('FoodOrderBundle:Order')->findOneBy(array(
            'id' => $excelData[$this->orderFieldsMapping['id']]
        ));
        if (count($realOrder)) {
            foreach ($this->orderFieldsMapping as $mapField) {
                //$realOrder->getId()
                if ($excelData[$mapField]) {
                    switch ($mapField) {
                        case 'sf_number':
                            $this->logChange($realOrder, 'old', 'new');
                            break;
                    }
                }
            }
        }
        var_dump($excelData);
        var_dump($realOrder);
        die;
    }

    private function logChange($realOrder, $oldValue, $newValue)
    {
        $change = new OrderFieldChangelog();
        $currentUser = $this->getContainer()->get('security.context')->getToken()->getUser();
        $change->setUser($currentUser);
        $now = new \DateTime();
        $change->setDate($now);
        $change->setOrder();
        $change->setOldValue($oldValue);
        $change->setNewValue($newValue);
        $change->setDataImport($realOrder);
    }

    private function getOrderFieldsMapping()
    {
        return array(
            'sf_number' => 2,
            'id' => 2,
        );
    }
}