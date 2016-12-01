<?php

namespace Food\ReportBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Service\BaseService;
use Food\ReportBundle\Entity\OrdersByRestaurantFile;
use PHPExcel;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Translation\Translator;

class OrdersByRestaurantReportService extends BaseService
{
    private $securityContext;
    private $translator;
    private $saveDirectory;

    public function __construct(EntityManager $em, SecurityContextInterface $securityContext, Translator $translator)
    {
        parent::__construct($em);
        $this->securityContext = $securityContext;
        $this->translator = $translator;
    }

    /**
     * @param int $type
     * @param array $restaurants
     * @param string $dateFrom
     * @param string $dateTo
     */
    public function generateDocument($type, $restaurants, $dateFrom, $dateTo)
    {

        if (!empty($restaurants)) {
            foreach ($restaurants as $restaurant) {
                $query = $this->em->getRepository('FoodOrderBundle:Order')->createQueryBuilder('o')
                    ->where('o.order_date BETWEEN :dateFrom AND :dateTo')
                    ->andWhere('o.place = :place')
                    ->setParameter('dateFrom', date('Y-m-d', strtotime($dateFrom)))
                    ->setParameter('dateTo', date('Y-m-d', strtotime($dateTo)))
                    ->setParameter('place', $restaurant)
                    ->getQuery();
                $orders = $query->getResult();
                $xlsData = array();
                if (!empty($orders)) {
                    $totalSumWithVat = 0;
                    $totalSumWithoutVat = 0;
                    $totalDeliverySum = 0;

                    $objPHPExcel = new PHPExcel();
                    $objPHPExcel->setActiveSheetIndex(0);

                    if ($type == OrdersByRestaurantFile::TYPE_FOR_RESTAURANT) {
                        $xlsData[] = [
                            $this->translator->trans('admin.report.order_id'),
                            $this->translator->trans('admin.report.foodout_sf_number'),
                            $this->translator->trans('admin.report.order_date'),
                            $this->translator->trans('admin.report.restaurant_id'),
                            $this->translator->trans('admin.report.restaurant_name'),
                            $this->translator->trans('admin.report.restaurant_placepoint_address'),
                            $this->translator->trans('admin.report.restaurant_placepoint_code'),
                            $this->translator->trans('admin.report.client_name'),
                            $this->translator->trans('admin.report.delivery_address'),
                            $this->translator->trans('admin.report.city'),
                            $this->translator->trans('admin.report.payment_type'),
                            $this->translator->trans('admin.report.payment_type_code'),
                            $this->translator->trans('admin.report.driver_id'),
                            $this->translator->trans('admin.report.food_sum_with_vat'),
                            $this->translator->trans('admin.report.food_sum_without_vat'),
                            $this->translator->trans('admin.report.discount_sum'),
                            $this->translator->trans('admin.report.delivery_sum'),
                            $this->translator->trans('admin.report.total_sum_with_vat'),
                            $this->translator->trans('admin.report.comissions_10_35'),
                            $this->translator->trans('admin.report.comissions_15'),
                            $this->translator->trans('admin.report.comissions_2'),
                        ];
                    } else {
                        $xlsData[] = [
                            $this->translator->trans('admin.report.nr'),
                            $this->translator->trans('admin.report.order_date'),
                            $this->translator->trans('admin.report.foodout_sf_number'),
                            $this->translator->trans('admin.report.restaurant_name'),
                            $this->translator->trans('admin.report.total_sum_with_vat'),
                            $this->translator->trans('admin.report.paid_cash'),
                            $this->translator->trans('admin.report.total_sum_without_vat'),
                            $this->translator->trans('admin.report.delivery_sum'),
                            $this->translator->trans('admin.report.comissions_10_35'),
                            $this->translator->trans('admin.report.comissions_15'),
                            $this->translator->trans('admin.report.comissions_2'),
                        ];
                    }
                    foreach ($orders as $order) {
                        if ($type == OrdersByRestaurantFile::TYPE_FOR_RESTAURANT) {
                            $xlsData[] = [
                                'a',
                                'b'
                            ];
                        } else {
                            $xlsData[] = [
                                $order->getId(),
                                $order->getOrderDate()->format("Y-m-d H:i:s"),
                                'd',
                                'd',
                                'd',
                                'd',
                                'd',
                                'd',
                                'd',
                                'd',
                                'd',
                            ];
                        }
                    }

                    if ($type == OrdersByRestaurantFile::TYPE_FOR_ADMINISTRATION) {
                        $xlsData[] = [
                            '',
                            '',
                            '',
                            $this->translator->trans('admin.report.total'),
                            $totalSumWithVat,
                            '',
                            $totalSumWithoutVat,
                            $totalDeliverySum,
                            '',
                            '',
                            '',
                        ];
                    }

                    $objPHPExcel->getActiveSheet()->fromArray($xlsData, null, 'A1');
                    $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
                    $filename = time() . '.xlsx';
                    $objWriter->save($this->saveDirectory . '/' . $filename);

                    $ordersByRestaurantFile = new OrdersByRestaurantFile();
                    $now = new \DateTime('now');
                    $ordersByRestaurantFile->setCreatedAt($now);
                    $ordersByRestaurantFile->setCreatedBy($this->securityContext->getToken()->getUser());
                    $ordersByRestaurantFile->setDateFrom($now); // @TODO fix date
                    $ordersByRestaurantFile->setDateTo($now); // @TODO Fix date
                    $ordersByRestaurantFile->setType($type);
                    $ordersByRestaurantFile->setFilename($filename);
                    $place = $this->em->getRepository('FoodDishesBundle:Place')->find($restaurant);
                    $ordersByRestaurantFile->addRestaurant($place);

                    $this->em->persist($ordersByRestaurantFile);
                    $this->em->flush($ordersByRestaurantFile);
                }
            }
        }
    }

    public function setSaveDirectory($saveDirectory)
    {
        $this->saveDirectory = $saveDirectory;
    }
}