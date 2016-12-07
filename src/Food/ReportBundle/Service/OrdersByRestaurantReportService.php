<?php

namespace Food\ReportBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Service\BaseService;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\OrderService;
use Food\ReportBundle\Entity\OrdersByRestaurantFile;
use PHPExcel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Translation\TranslatorInterface;

class OrdersByRestaurantReportService extends BaseService
{
    private $securityContext;
    private $translator;
    private $saveDirectory;
    private $fileSystem;

    public function __construct(EntityManager $em, SecurityContextInterface $securityContext, TranslatorInterface $translator, Filesystem $fileSystem)
    {
        parent::__construct($em);
        $this->securityContext = $securityContext;
        $this->translator = $translator;
        $this->fileSystem = $fileSystem;
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
            if (!$this->fileSystem->exists($this->saveDirectory)) {
                $this->fileSystem->mkdir($this->saveDirectory);
            }
            foreach ($restaurants as $restaurant) {
                $query = $this->em->getRepository('FoodOrderBundle:Order')->createQueryBuilder('o')
                    ->where('o.order_date BETWEEN :dateFrom AND :dateTo')
                    ->andWhere('o.place = :place')
                    ->andWhere('o.order_status = :orderStatus')
                    ->setParameter('dateFrom', date('Y-m-d', strtotime($dateFrom)))
                    ->setParameter('dateTo', date('Y-m-d', strtotime($dateTo)))
                    ->setParameter('place', $restaurant)
                    ->setParameter('orderStatus', OrderService::$status_completed)
                    ->getQuery();
                $orders = $query->getResult();
                $xlsData = array();
                if (!empty($orders)) {
                    $totalSumWithVat = 0;
                    $totalSumWithoutVat = 0;
                    $totalDeliverySum = 0;
                    $totalCommissionsSum30 = 0;
                    $totalCommissionsSum15 = 0;
                    $totalCommissionsSum2 = 0;

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
                            $this->translator->trans('admin.report.restaurant_placepoint_address'),
                            $this->translator->trans('admin.report.restaurant_placepoint_code'),
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
                                $order->getId(),
                                $order->getSfSeries() . $order->getSfNumber(),
                                $order->getOrderDate(),
                                ($order->getPlace() ? $order->getPlace()->getId() : ''),
                                $order->getPlaceName(),
                                $order->getPlacePointAddress(),
                                ($order->getPlacePoint() ? $order->getPlacePoint()->getId() : ''),
                                ($order->getUser() ? $order->getUser()->getFullName() : ''),
                                ($order->getAddressId() ? $order->getAddressId()->getAddress() : ''),
                                $order->getPlacePointCity(),
                                $order->getPaymentMethod(),
                                $order->getPaymentMethodCode(),
                                ($order->getDriver() ? $order->getDriver()->getId() : ''),
                                ($order->getTotal() - $order->getDeliveryPrice()),
                                ($order->getTotal() - $order->getDeliveryPrice())/1.21,
                                $order->getDiscountSum(),
                                $order->getDeliveryPrice(),
                                $order->getTotal(),
                                $order->getTotal() * 0.35,
                                $order->getTotal() * 0.015,
                                $order->getTotal() * 0.02,
                            ];
                        } else {
                            $xlsData[] = [
                                $order->getId(),
                                $order->getOrderDate(),
                                $order->getSfSeries().$order->getSfNumber(),
                                $order->getPlaceName(),
                                $order->getPlacePointAddress(),
                                ($order->getPlacePoint() ? $order->getPlacePoint()->getId() : ''),
                                ($order->getTotal() - $order->getDeliveryPrice()),
                                ($order->getPaymentMethod() == 'local' ? 'Taip' : 'Ne'),
                                ($order->getTotal() - $order->getDeliveryPrice())/1.21,
                                $order->getDeliveryPrice(),
                                $order->getTotal() * 0.35,
                                $order->getTotal() * 0.015,
                                $order->getTotal() * 0.02,
                            ];
                            $totalSumWithVat += $order->getTotal();
                            $totalSumWithoutVat += $order->getTotalWithoutVat();
                            $totalDeliverySum += $order->getDeliveryPrice();
                            $totalCommissionsSum30 += $order->getTotal() * 0.35;
                            $totalCommissionsSum15 += $order->getTotal() * 0.015;
                            $totalCommissionsSum2 += $order->getTotal() * 0.02;
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
                            $totalCommissionsSum30,
                            $totalCommissionsSum15,
                            $totalCommissionsSum2,
                        ];
                    }

                    $objPHPExcel->getActiveSheet()->fromArray($xlsData, null, 'A1');
                    $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
                    $filename = $restaurant . '_' . date('Ymd', strtotime($dateFrom)) . '_' . date('Ymd', strtotime($dateTo)) . '.xlsx';
                    $objWriter->save($this->saveDirectory . '/' . $filename);

                    $ordersByRestaurantFile = new OrdersByRestaurantFile();
                    $now = new \DateTime('now');
                    $ordersByRestaurantFile->setCreatedAt($now);
                    $ordersByRestaurantFile->setCreatedBy($this->securityContext->getToken()->getUser());

                    $dateFromDateTime = new \DateTime($dateFrom);
                    $ordersByRestaurantFile->setDateFrom($dateFromDateTime);

                    $dateToDateTime = new \DateTime($dateTo);
                    $ordersByRestaurantFile->setDateTo($dateToDateTime);

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