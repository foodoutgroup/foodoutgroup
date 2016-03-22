<?php

namespace Food\MonitoringBundle\Service;

use Food\OrderBundle\Entity\InvoiceToSend;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\OrderService;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Class MonitoringService
 * @package Food\SmsBundle\Service
 */
class MonitoringService extends ContainerAware {


    /**
     * @var null
     */
    private $manager = null;

    /**
     * @param null $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getManager()
    {
        if (empty($this->manager)) {
            $this->manager = $this->container->get('doctrine')->getManager();
        }
        return $this->manager;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array
     */
    public function getUnfinishedOrdersForRange(\DateTime $from, \DateTime $to)
    {
        $repository = $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order');

        $query = $repository->createQueryBuilder('o')
            ->where('o.order_status IN (:order_status)')
            ->andWhere('o.paymentStatus = :payment_status')
            ->andWhere('o.order_date >= :from_date')
            ->andWhere('o.order_date <= :to_date')
            ->andWhere('o.deliveryTime < :max_delivery_date')
            ->andWhere('o.deliveryType = :delivery_type')
            ->andWhere('o.place_point_self_delivery != 1')
            ->andWhere('o.orderFromNav != 1')
            ->orderBy('o.order_date', 'ASC')
            ->setParameters(
                [
                    'order_status' => array(
                        OrderService::$status_accepted,
                        OrderService::$status_delayed,
                        OrderService::$status_finished,
                        OrderService::$status_assiged,
                    ),
                    'payment_status' => OrderService::$paymentStatusComplete,
                    'from_date' => $from,
                    'to_date' => $to,
                    'max_delivery_date' => new \DateTime('-4 hour'),
                    'delivery_type' => OrderService::$deliveryDeliver
                ]
            )
            ->getQuery();

        $orders = $query->getResult();
        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @return Order[]|array
     */
    public function getUnacceptedOrders()
    {
        $repository = $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order');

        $query = $repository->createQueryBuilder('o')
            ->where('o.order_status = :order_status')
            ->andWhere('o.paymentStatus = :payment_status')
            ->andWhere('o.order_date <= :date')
            ->andWhere('o.order_date > :oldest_date')
            ->andWhere('o.orderFromNav != 1')
            ->orderBy('o.order_date', 'ASC')
            ->setParameters(
                [
                    'order_status' => OrderService::$status_new,
                    'payment_status' => OrderService::$paymentStatusComplete,
                    'date' => new \DateTime("-8 minute"),
                    'oldest_date' => new \DateTime("-1 day")
                ]
            )
            ->getQuery();

        $orders = $query->getResult();
        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @return array|InvoiceToSend[]
     */
    public function getUnsentInvoices()
    {
        $repository = $this->container->get('doctrine')->getRepository('FoodOrderBundle:InvoiceToSend');

        $query = $repository->createQueryBuilder('i')
            ->where('i.status = :invoice_status')
            ->andWhere('i.dateAdded > :added_date_start')
            ->andWhere('i.dateAdded < :added_date_end')
            ->setParameters(
                [
                    'invoice_status' => 'unsent',
                    'added_date_start' => new \DateTime("-1 hour"),
                    'added_date_end' => new \DateTime("-14 minute")
                ]
            )
            ->getQuery();

        $invoices = $query->getResult();
        if (!$invoices) {
            return array();
        }

        return $invoices;
    }

    /**
     * @return Order[]|array
     */
    public function getUnassignedOrders()
    {
        $repository = $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order');

        $query = $repository->createQueryBuilder('o')
            ->where('o.order_status IN (:order_status)')
            ->andWhere('o.paymentStatus = :payment_status')
            ->andWhere('o.deliveryTime <= :date')
            ->andWhere('o.deliveryTime > :oldest_date')
            ->andWhere('o.place_point_self_delivery != 1')
            ->andWhere('o.deliveryType != :delivery_type')
            ->andWhere('o.orderFromNav != 1')
            ->orderBy('o.order_date', 'ASC')
            ->setParameters(
                [
                    'order_status' => array(
                        OrderService::$status_accepted,
                        OrderService::$status_delayed,
                        OrderService::$status_forwarded,
                    ),
                    'payment_status' => OrderService::$paymentStatusComplete,
                    'date' => new \DateTime("+27 minute"),
                    'oldest_date' => new \DateTime("-1 day"),
                    'delivery_type' => OrderService::$deliveryPickup,
                ]
            )
            ->getQuery();

        $orders = $query->getResult();
        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @return array
     */
    public function getLogisticsSyncProblems()
    {
        $return = array(
            'unsent' => 0,
            'error' =>
                array(
                    'count' => 0,
                    'lastError' => '',
                )
        );

        $dateStart = new \DateTime("-4 minute");
        $dateEnd = new \DateTime("-90 minute");

        $dateStart = $dateStart->format("Y-m-d H:i:s");
        $dateEnd = $dateEnd->format("Y-m-d H:i:s");

        $query = "SELECT
          SUM(IF (status = 'unsent', 1, 0)) AS unsent,
          SUM(IF (status = 'error', 1, 0)) AS error,
          MAX(last_error) AS last_error
        FROM orders_to_logistics
        WHERE
          status IN ('unsent', 'error')
          AND date_added <= '{$dateStart}'
          AND date_added > '{$dateEnd}'
        ORDER BY
          last_error DESC
        ";

        /**
         * @var \Doctrine\DBAL\Driver\Statement $stmt
         */
        $stmt = $this->container->get('doctrine')->getManager()->getConnection()
            ->prepare($query);

        $stmt->execute();

        $problems = $stmt->fetch();
        if (!$problems) {
            return $return;
        }

        $return['unsent'] = (int)$problems['unsent'];
        $return['error']['count'] = (int)$problems['error'];
        if ($problems['error'] > 0) {
            $return['error']['lastError'] = $problems['last_error'];
        }

        return $return;
    }

    /**
     * @return array
     */
    public function getFewOrdersFromNav()
    {
        $navService = $this->container->get('food.nav');

        $query = sprintf(
            'SELECT TOP 1 [Order No_], [Order Status], [Delivery Status]
            FROM %s',
            $navService->getHeaderTable()
        );

        $result = $navService->initSqlConn()
            ->query($query);

        if( $result === false) {
            return array();
        }

        $return = array();
        while ($rowRez = $this->container->get('food.mssql')->fetchArray($result)) {
            $return[] = $rowRez;
        }

        return $return;
    }
}